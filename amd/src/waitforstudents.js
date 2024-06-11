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
 * Wait for the students to connect to tcquiz session.
 * Redisplay the number of connected students every POLLING_INTERVAL miliiseconds.
 *
 * @module     quizaccess_tcquiz
 * @copyright  2024 Capilano University
 * @author     Tamara Dakic <tdakic@capilanou.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const Selectors = {
    actions: {
        nextButton: '[data-action="quizaccess_tcquiz/waitforstudents-next_button"]',
        },
        regions: {
            numStudentsSpan: '[data-region="quizaccess_tcquiz/numberstudents_span"]',
        },
};

const registerEventListeners = (sessionid, quizid, cmid, attemptid, POLLING_INTERVAL) => {

  var updateNumStudentsEvent = setInterval(async() => {
    await updateNumberOfStudents(sessionid, quizid, cmid, attemptid);
  }, POLLING_INTERVAL);

  /* Teacher clicks the next button when they are ready to display the first question. */
  const nextQuestionAction = document.querySelector(Selectors.actions.nextButton);
  nextQuestionAction.addEventListener('click', async(e) => {
          e.preventDefault();
          clearInterval(updateNumStudentsEvent);

          var result = await fetch(M.cfg.wwwroot + '/mod/quiz/accessrule/tcquiz/quizdatateacher.php?requesttype=getquestion&quizid='
            + quizid + '&cmid=' + cmid + '&attempt=' + attemptid
            + '&sessionid=' + sessionid + '&rejoin=0&sesskey=' + M.cfg.sesskey, {method: 'POST'});

          var responseXMLText = await result.text();

          await goToNextUrl(responseXMLText);

    }, {once: true});

};

/**
 * Update the number of students who connected to tcquiz
 * @param {sessionid} sessionid The id of the current session.
 * @param {quizid} quizid The quizid of the current quiz.
 * @param {cmid} cmid Course module id of the current quiz.
 * @param {attemptid} attemptid The attemptid of the teacher's preview. Needed for displaying the first question.
 */
async function updateNumberOfStudents(sessionid, quizid, cmid, attemptid) {

  var result = await fetch(M.cfg.wwwroot + '/mod/quiz/accessrule/tcquiz/quizdatateacher.php?requesttype=getnumberstudents&quizid='
    + quizid + '&sessionid=' + sessionid + '&cmid=' + cmid + '&attempt=' + attemptid
    + '&currentquestion=0&sesskey=' + M.cfg.sesskey, {method: 'POST'});

  var responseXMLText = await result.text();

  await updateNumberOfStudentsHTML(responseXMLText);
}

/**
 * Helper function to update the html with fetched number of connected students.
 * @param {string} responseXMLText The XML with the number of connected students returned from the server
 */
function updateNumberOfStudentsHTML(responseXMLText) {

  var parser = new DOMParser();
  var responseXML = parser.parseFromString(responseXMLText, 'text/html');

  var quizresponse = responseXML.getElementsByTagName('tcquiz').item(0);

  var numberOfStudents = quizresponse.getElementsByTagName('numberstudents').item(0).textContent;

  document.querySelector(Selectors.regions.numStudentsSpan).innerHTML = numberOfStudents;
}

/**
 * Helper function to parse a response from the server and go to the specified url.
 * The url should either be of the next quiz page of the final results.
 * The only responses should have the url field.
 * @param {string} responseXMLText The XML returned by quizdatateacher.php
 */
function goToNextUrl(responseXMLText) {

  var parser = new DOMParser();
  var responseXML = parser.parseFromString(responseXMLText, 'text/html');

  var quizresponse = responseXML.getElementsByTagName('tcquiz').item(0);

  var nextUrl = quizresponse.getElementsByTagName('url').item(0).textContent;
  window.location.replace(nextUrl);
}

export const init = (sessionid, quizid, cmid, attemptid, POLLING_INTERVAL) => {
  registerEventListeners(sessionid, quizid, cmid, attemptid, POLLING_INTERVAL);
};
