// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Go to the current quiz page. Used in the tcq_review_page (for student)
 * in tcquiz renderer.
 *
 * @module     quizaccess_tcquiz
 * @copyright  2024 Capilano University
 * @author     Tamara Dakic <tdakic@capilanou.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_string as getString} from 'core/str';
import Notification from 'core/notification';

const registerEventListeners = (sessionid, quizid, cmid, attemptid, page, POLLING_INTERVAL) => {

  document.goToCurrentQuizPageEvent = setInterval(async() => {
      await goToCurrentQuizPage(sessionid, quizid, cmid, attemptid, page);
  }, POLLING_INTERVAL);
};

/**
 * Go to the next page of the quiz.
 * @param {sessionid} sessionid The id of the current session.
 * @param {quizid} quizid The quizid of the current quiz.
 * @param {cmid} cmid Course module id of the current quiz.
 * @param {attemptid} attemptid The attemptid of the teacher's attempt.
 * @param {page} page The current quiz page
 */
async function goToCurrentQuizPage(sessionid, quizid, cmid, attemptid, page) {

  var result = await fetch(M.cfg.wwwroot + '/mod/quiz/accessrule/tcquiz/quizdatastudent.php?quizid='
    + quizid + '&sessionid=' + sessionid + '&cmid=' + cmid + '&attempt=' + attemptid
    + '&sesskey=' + M.cfg.sesskey + '&page=' + page, {method: 'POST'});

  var responseXMLText = await result.text();

  await updateQuizPage(responseXMLText);

}

/**
 * Helper function to parse a response from the server and go to the specified url.
 * @param {string} responseXMLText The XML returned by quizdatastudent.php
 */
function updateQuizPage(responseXMLText) {

  const parser = new DOMParser();
  const responseXML = parser.parseFromString(responseXMLText, 'text/html');

  var quizresponse = responseXML.getElementsByTagName('tcquiz').item(0);

  // ERROR handling?

  if (quizresponse === null) {
    Notification.addNotification({
        message: getString('invalidserverresponse', 'quizaccess_tcquiz'),
        type: 'error'
    });
    return;

  } else {

    var quizstatus = quizresponse.getElementsByTagName('status').item(0).textContent;

    // When results are being shown, the only options for the next page for students are
    // show next question or show final results.
    if (quizstatus == 'showquestion' || quizstatus == 'finalresults') {

        clearInterval(document.goToCurrentQuizPageEvent);
        var nextURL = quizresponse.getElementsByTagName('url').item(0).textContent;
        window.location.replace(nextURL);

    } else if (quizstatus == 'showresults') {
        // You should be on this page, so do nothing.

    } else if (quizstatus == 'quiznotrunning' || quizstatus == 'waitforquestion' || quizstatus == 'waitforresults' ||
            quizstatus == 'noaction') {
            // Keep trying.

    } else if (quizstatus == 'error') {
      var errmsg = quizresponse.getElementsByTagName('message').item(0).textContent;

      Notification.addNotification({
          message: errmsg,
          type: 'error'
      });

    } else {
      Notification.addNotification({
          message: getString('unknownserverresponse', 'quizaccess_tcquiz') + quizstatus,
          type: 'error'
      });
    }
  }

}

export const init = (sessionid, quizid, cmid, attemptid, page, POLLING_INTERVAL) => {
  registerEventListeners(sessionid, quizid, cmid, attemptid, page, POLLING_INTERVAL);
};
