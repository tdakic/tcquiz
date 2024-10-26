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
 * Allows the teacher to control the polling of one question.
 * Currently, the teacher can only stop the question.
 *
 * @module     quizaccess_tcquiz
 * @copyright  2024 Capilano University
 * @author     Tamara Dakic <tdakic@capilanou.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Notification from 'core/notification';
import {get_string as getString} from 'core/str';

const Selectors = {
    actions: {
        endquestionButton: '[data-action="quizaccess_tcquiz/end-question_button"]',
        // For upadating teacher controls later: nextquestionButton: '[data-action="quizaccess_tcquiz/next-question_button"]',
    },
    regions: {
        numAnswers: '[data-region="quizaccess_tcquiz/numberanswers_span"]',
        timeLeft: '[data-region="quizaccess_tcquiz/timeleft_span"]',
 },
};

const registerEventListeners = (sessionid, quizid, cmid, attemptid, page, timeForQuestion, teacherTimer, POLLING_INTERVAL) => {

  // This should prevent "Unsaved changes" pop-up which might happen if the teacher interacts with the
  // question and then clicks on the End question button.
  window.addEventListener('beforeunload', function(event) {
    event.stopImmediatePropagation();
  });

  var timeLeftHTML = document.querySelector(Selectors.regions.timeLeft);
  const endQuestionAction = document.querySelector(Selectors.actions.endquestionButton);

  teacherTimer.onmessage = function(event) {
      timeLeftHTML.innerHTML = event.data;
      if (event.data == 0) {
        // The teacher should not click on the End button if the question ended.
        endQuestionAction.disabled = true;
        timeLeftHTML.innerHTML = 0;
        clearInterval(updateNumAnswersEvent);
        updateNumAnswersEvent = null;
        teacherTimer.terminate();
        document.getElementById('responseform').submit();
      }
  };

  // Handles teacher clicking on the End question button
  endQuestionAction.addEventListener('click', async(e) => {
        e.preventDefault();
        endQuestionAction.disabled = true;
        clearInterval(updateNumAnswersEvent);
        updateNumAnswersEvent = null;
        timeLeftHTML.innerHTML = 0;
        teacherTimer.terminate();
        const req = new XMLHttpRequest();
        req.open("POST", M.cfg.wwwroot +
          '/mod/quiz/accessrule/tcquiz/change_question_state.php?sessionid=' + sessionid + '&cmid=' + cmid +
          '&sesskey=' + M.cfg.sesskey);
        req.send();

        req.onload = () => {
          document.getElementById('responseform').submit();
        };
    }, {once: true});

    // Updates the number of received student answers.
    var updateNumAnswersEvent = setInterval(async() => {
      await updateNumberOfAnswers(sessionid, quizid, cmid, attemptid);
    }, POLLING_INTERVAL);


    /*  Left here to use when a better teacher controls are added - for adding next question button
    document.addEventListener('click', async(e) => {
            if (e.target.closest(Selectors.actions.nextquestionButton)) {
              e.preventDefault();
              page++;
              clearInterval(updateNumAnswersEvent);
              updateNumAnswersEvent = null;
              document.querySelector(Selectors.regions.timeLeft).innerHTML = 0; //will this stop setInterval?

              var  result = await fetch(M.cfg.wwwroot+'/mod/quiz/accessrule/tcquiz/quizdatateacher.php?'
                + 'requesttype=getquestion&quizid='
                +quizid+'&joincode='+joincode+'&cmid='+ cmid +'&attempt='+attemptid
              +'&sessionid='+sessionid+'&rejoin=false&page='+page+'&sesskey='+ M.cfg.sesskey,{method: 'POST'});

              var responseXMLText = await result.text();
              await  parse_next_url(responseXMLText);

            }
      },{once: true} );
    */

};

/**
 * Retrieves and updates the number of received student answers
 * @param {sessionid} sessionid The id of the current session.
 * @param {quizid} quizid The quizid of the current quiz.
 * @param {cmid} cmid Course module id of the current quiz.
 * @param {attemptid} attemptid The attemptid of the teacher's attempt.
 */
async function updateNumberOfAnswers(sessionid, quizid, cmid, attemptid) {

  var result = await fetch(M.cfg.wwwroot + '/mod/quiz/accessrule/tcquiz/quizdatateacher.php?requesttype=getnumberanswers&quizid='
    + quizid + '&sessionid=' + sessionid + '&cmid=' + cmid + '&attempt=' + attemptid
    + '&sesskey=' + M.cfg.sesskey, {method: 'POST'});

  var responseXMLText = await result.text();

  await updateNumAnswersHtml(responseXMLText);

}

/**
 * Helper function to update the html with number of submitted answers
 * @param {string} responseXMLText
 */
function updateNumAnswersHtml(responseXMLText) {

  var parser = new DOMParser();
  var responseXML = parser.parseFromString(responseXMLText, 'text/html');

  var quizresponse = responseXML.getElementsByTagName('tcquiz').item(0);

  var numberOfAnswers = quizresponse.getElementsByTagName('numanswers').item(0).textContent;
  document.querySelector(Selectors.regions.numAnswers).innerHTML = numberOfAnswers;
}

/**
 * Helper function to replace the current page with the attempt page specified in the responseXMLText
 * @param {string} responseXMLText
 */
/* For when the teacher controls are improved and the Next button is also on this page.
 function parse_next_url(responseXMLText){

  var parser = new DOMParser();
  var responseXML = parser.parseFromString(responseXMLText, 'text/html');

  var quizresponse = responseXML.getElementsByTagName('tcquiz').item(0);
  var next_url = quizresponse.getElementsByTagName('url').item(0).textContent;

  window.location.replace(next_url);

}*/

export const init = (sessionid, quizid, cmid, attemptid, page, timeForQuestion, POLLING_INTERVAL) => {

  var teacherTimer = new Worker('./timer.js');

  // Figure out how much time is left before the question closes. The question html should have been displayed since
  // the question was started, but it might have taken time to set up the js?

  const URL_Q_TIME = M.cfg.wwwroot + '/mod/quiz/accessrule/tcquiz/get_question_time_left.php?quizid='
    + quizid + '&sessionid=' + sessionid + '&cmid=' + cmid + '&attempt=' + attemptid
    + '&sesskey=' + M.cfg.sesskey;

  fetch(URL_Q_TIME, {method: 'POST'}).then((response) => {
          if (!response.ok) {
            throw new Error(getString('questiontimeretriveerror', 'quizaccess_tcquiz'));
          }
          return response.text();
      }).then((text) => {
          teacherTimer.postMessage({'timeForQuestion': Math.max(0, text)});
          return text;
      }).catch((error) => {
          // It is better to have the timer going then not. The teacher might ge more time than the
          // students, but that's OK.
          teacherTimer.postMessage({'timeForQuestion': timeForQuestion});
          Notification.addNotification({
            message: error,
            type: 'error'
          });
      });

  registerEventListeners(sessionid, quizid, cmid, attemptid, page, timeForQuestion, teacherTimer, POLLING_INTERVAL);
};
