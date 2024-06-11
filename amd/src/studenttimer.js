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
 * Countdown time the students have for answering the question. The timer can also be stopped by the teacher.
 *
 * @module     quizaccess_tcquiz
 * @copyright  2024 Capilano University
 * @author     Tamara Dakic <tdakic@capilanou.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_string as getString} from 'core/str';
import Notification from 'core/notification';
import $ from 'jquery';

const Selectors = {
    regions: {
        timeLeft: '[data-region="quizaccess_tcquiz/timeleft_span"]',
 },
};

const registerEventListeners = (sessionid, quizid, cmid, attemptid, page, timeForQuestion, POLLING_INTERVAL) => {
// The timer can be stoped either by the teacher or expired time -- handle both events.
// Enough to check when the state of the quiz has changed to show results (30).

    $('#responseform').on('submit', function() {
        $('#responseformsubmit').attr('disabled', 'disabled');
    });

    // This should prevent "Unsaved changes" pop-up which might happen if the student typed something
    // but didn't click submit.
    window.addEventListener('beforeunload', function(event) {
      event.stopImmediatePropagation();
    });

    var timeLeft = timeForQuestion; // +1 to wait for everyone?
    var timeLeftHTML = document.querySelector(Selectors.regions.timeLeft);
    var teacherEndedQuestion = false;

    // Timer
    var timer = setInterval(function() {
        timeLeft--;
        timeLeftHTML.innerHTML = timeLeft;
        if (timeLeft <= 0 || teacherEndedQuestion) {
          clearInterval(timer);
          timer = null;
          clearInterval(tecaherEndedQuestionEvent);
          tecaherEndedQuestionEvent = null;
          timeLeftHTML.innerHTML = 0;
          document.goToCurrentQuizPageEvent = setInterval(async() => {
              await goToCurrentQuizPage(sessionid, quizid, cmid, attemptid);
          }, POLLING_INTERVAL);
        }
    }, 1000);

    // Checks for teacher ending the question event.
    var tecaherEndedQuestionEvent = setInterval(async function() {
      teacherEndedQuestion = await checkQuestionState(sessionid, quizid, cmid, attemptid);
    }, POLLING_INTERVAL); // 1000 means 1 sec

};

/**
 * Checks if the teacher stopped the question
 * @param {sessionid} sessionid The id of the current session.
 * @param {quizid} quizid The quizid of the current quiz.
 * @param {cmid} cmid Course module id of the current quiz.
 * @param {attemptid} attemptid The attemptid of the teacher's attempt.
 * @return {boolean} - true if the question was stopped by the teacher, false otherwise
 */
async function checkQuestionState(sessionid, quizid, cmid, attemptid) {

  var result = await fetch(M.cfg.wwwroot + '/mod/quiz/accessrule/tcquiz/get_question_state.php?quizid='
    + quizid + '&sessionid=' + sessionid + '&cmid=' + cmid + '&attempt=' + attemptid
    + '&sesskey=' + M.cfg.sesskey, {method: 'POST'});

  var responseXMLText = await result.text();

  return responseXMLText == "0";

}

/**
 * When time is up or the teacher stopped the question, go to the next page of the quiz.
 * That page should only be the result's page or the final result's page
 * but the method is coded more generally in case of teacher control improvements
 * @param {sessionid} sessionid The id of the current session.
 * @param {quizid} quizid The quizid of the current quiz.
 * @param {cmid} cmid Course module id of the current quiz.
 * @param {attemptid} attemptid The attemptid of the teacher's attempt.
 */
async function goToCurrentQuizPage(sessionid, quizid, cmid, attemptid) {

  var result = await fetch(M.cfg.wwwroot + '/mod/quiz/accessrule/tcquiz/quizdatastudent.php?quizid='
    + quizid + '&sessionid=' + sessionid + '&cmid=' + cmid + '&attempt=' + attemptid
    + '&sesskey=' + M.cfg.sesskey, {method: 'POST'});

  var responseXMLText = await result.text();

  await updateQuizPage(responseXMLText);

}

/**
 * Helper function to parse a response from the server and go to the specified url.
 * same function is in waitforquestion.js - leave for now in case more events added
 * @param {string} responseXMLText The XML returned by quizdatastudent.php
 */
function updateQuizPage(responseXMLText) {

  const parser = new DOMParser();
  const responseXML = parser.parseFromString(responseXMLText, 'text/html');

  var quizresponse = responseXML.getElementsByTagName('tcquiz').item(0);

  if (quizresponse === null) {
    Notification.addNotification({
        message: getString('invalidserverresponse', 'quizaccess_tcquiz'),
        type: 'error'
    });
    return;

  } else {

    var quizstatus = quizresponse.getElementsByTagName('status').item(0).textContent;

    if (quizstatus == 'showquestion') {

        // You should be on this page, so do nothing

    } else if (quizstatus == 'showresults') {

        clearInterval(document.goToCurrentQuizPageEvent);
        document.goToCurrentQuizPageEvent = null;
        var resultURL = quizresponse.getElementsByTagName('url').item(0).textContent;
        window.location.replace(resultURL);

    } else if (quizstatus == 'finalresults') {
      // This could potentially only happen if the teacher clicks through the question results very fast.

      clearInterval(document.goToCurrentQuizPageEvent);
      document.goToCurrentQuizPageEvent = null;
      var finalResultURL = quizresponse.getElementsByTagName('url').item(0).textContent;
      window.location.replace(finalResultURL);

    } else if (quizstatus == 'quiznotrunning' || quizstatus == 'waitforquestion' || quizstatus == 'waitforresults'
            || quizstatus == 'noaction') {
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

export const init = (sessionid, quizid, cmid, attemptid, page, timeForQuestion, POLLING_INTERVAL) => {

  registerEventListeners(sessionid, quizid, cmid, attemptid, page, timeForQuestion, POLLING_INTERVAL);
};
