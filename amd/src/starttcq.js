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
 * Allows the teacher to start tcquiz
 *
 * The actual form for typing in the new joincode and starting a new quiz is in
 * /mod/quiz/accessrule/tcquiz/classes/form/tcq_start_form.php
 * and is validated by the validation method
 *
 * @module     quizaccess_tcquiz
 * @copyright  2024 Capilano University
 * @author     Tamara Dakic <tdakic@capilanou.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// import $ from 'jquery';
import Notification from 'core/notification';
import {get_string as getString} from 'core/str';

const Selectors = {
    actions: {
        startTCQButton: '[data-action="quizaccess_tcquiz/starttcq-startnew_button"]',
        endButton: '[data-action="quizaccess_tcquiz/starttcq-end_button"]',
        rejoinButton: '[data-action="quizaccess_tcquiz/starttcq-rejoin_button"]',
    },

};

const registerEventListeners = (sessionid, joincode, timestamp, currentpage, status, attemptid, existingsession, quizid, cmid) => {

    // Handle the teacher clicking on the End button to end the session (if there is one).
    const endTCQAction = document.querySelector(Selectors.actions.endButton);
    if (endTCQAction !== null) {
      endTCQAction.addEventListener('click', async(e) => {

            e.preventDefault();

            var url = M.cfg.wwwroot + "/mod/quiz/accessrule/tcquiz/end_session.php?id=" + e.target.name + "&cmid=" + cmid;

            // Response.status will be 404 even if the fetched url throws exception.
            await fetch(url, {method: "POST"}).then((response) => {
                  if (!response.ok) {
                    Notification.addNotification({
                        message: getString('errorclosingsession', 'quizaccess_tcquiz'),
                        type: 'error'
                    });

                  } else {
                    location.reload();
                  }
                  return null;
          });
      }, {once: true});
    }

    // Handle the teacher clicking on the Rejoin button to rejoin the running session (if there is one).
    const rejoinTCQAction = document.querySelector(Selectors.actions.rejoinButton);
    if (endTCQAction !== null) {
      rejoinTCQAction.addEventListener('click', async(e) => {
            e.preventDefault();
            // Constants defined in tcq_constants.json - get them!
            const response = await fetch(M.cfg.wwwroot + "/mod/quiz/accessrule/tcquiz/tcq_constants.json");
            const tcqConsts = await response.json();

            var url = "";
            if (status == tcqConsts.TCQUIZ_STATUS_READYTOSTART) {
              url = M.cfg.wwwroot + "/mod/quiz/accessrule/tcquiz/wait_for_students.php?quizid=" + quizid + "&cmid=" + cmid +
              "&attemptid=" + attemptid + "&joincode=" + joincode + "&sessionid=" + sessionid;
              window.location.replace(url);

            } else if (status == tcqConsts.TCQUIZ_STATUS_PREVIEWQUESTION || status == tcqConsts.TCQUIZ_STATUS_SHOWQUESTION) {
              url = M.cfg.wwwroot + "/mod/quiz/accessrule/tcquiz/attempt.php?showall=0&quizid=" + quizid + "&cmid=" + cmid +
              "&attempt=" + attemptid + "&joincode" + joincode + "&sessionid=" + sessionid + "&page=" + currentpage;
              window.location.replace(url);

            } else if (status == tcqConsts.TCQUIZ_STATUS_SHOWRESULTS) {
              url = M.cfg.wwwroot + "/mod/quiz/accessrule/tcquiz/review_tcq.php?showall=false&quizid=" + quizid + "&cmid=" +
              cmid + "&attempt=" + attemptid + "&joincode" + joincode + "&sessionid=" + sessionid + "&page=" + currentpage;
              window.location.replace(url);

            } else if (status == tcqConsts.TCQUIZ_STATUS_FINALRESULTS) {
              url = M.cfg.wwwroot + "/mod/quiz/accessrule/tcquiz/report_final_results.php?mode=overview&quizid=" + quizid +
              "&id=" + cmid + "&tcqsid=" + sessionid;
              window.location.replace(url);

            } else {
              Notification.addNotification({
                  message: getString('errorrejoining', 'quizaccess_tcquiz'),
                  type: 'error'
              });
            }
      }, {once: true});
    }


    // Trying to prevent double clicking here.
    document.addEventListener('click', (e) => {
          if (e.target.closest(Selectors.actions.startTCQButton)) {
            e.preventDefault();
          }
    }, {once: true});

};

export const init = (sessionid, joincode, timestamp, currentpage, status, attemptid, existingsession, quizid, cmid) => {

  registerEventListeners(sessionid, joincode, timestamp, currentpage - 1, status, attemptid, existingsession, quizid, cmid);

};
