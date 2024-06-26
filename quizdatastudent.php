<?php
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
 * Polls the DB and sends the appropriate page of a tcquiz to students
 *
 * @copyright 2024, Tamara Dakic @Capilano University
 * based on quizdatastudent.php from the realtimequiz module by Davo Smith <moodle@davosmith.co.uk>
 * @package quizaccess_tcquiz
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

define('AJAX_SCRIPT', true);

require_once('../../../../config.php');
global $CFG, $DB, $USER, $PAGE;

require_once($CFG->dirroot.'/mod/quiz/accessrule/tcquiz/locallib.php');
require_once($CFG->dirroot.'/mod/quiz/locallib.php');
require_once($CFG->libdir.'/filelib.php');

require_login();

$quizid = required_param('quizid', PARAM_INT);
$attempt = required_param('attempt', PARAM_INT );
$cmid = required_param('cmid', PARAM_INT);
$sessionid = required_param('sessionid', PARAM_INT);

// In case the user crashed.
if (!confirm_sesskey()) {
    redirect(new \moodle_url('/mod/quiz/view.php', ['id' => $cmid, 'forceview' => 1]));
}


/***********************************************************
 * start of main code
 ***********************************************************/

tcquiz_start_response();

if (!$tcquiz = $DB->get_record('quizaccess_tcquiz_session', ['id' => $sessionid])) {
    tcquiz_send_error("TCQuiz Session incorrect");
    tcquiz_end_response();
    die();
}

$status = $tcquiz->status;

if ($status === false) {
    tcquiz_send_error(get_string('badquizid', 'tcquiz').$quizid);
} else {
    switch ($status) {

        case TCQUIZ_STATUS_FINISHED:

        case TCQUIZ_STATUS_NOTRUNNING:   // Quiz is not running.
            tcquiz_send_not_running(); // We don't care what they asked for.
            break;

        case TCQUIZ_STATUS_READYTOSTART: // Quiz is ready to start.
            tcquiz_send_await_question();
            break;

        case TCQUIZ_STATUS_PREVIEWQUESTION: // Previewing question (not used, but maybe useful later).

        case TCQUIZ_STATUS_SHOWQUESTION: // Question being displayed.

            echo '<status>showquestion</status>';
            echo '<url>';
            echo new moodle_url('/mod/quiz/accessrule/tcquiz/attempt.php', ['r' => rand(), 'page' => $tcquiz->currentpage,
              'showall' => false, 'attempt' => $attempt, 'quizid' => $quizid, 'cmid' => $cmid, 'sessionid' => $tcquiz->id]);
            echo '</url>';
            break;

        case TCQUIZ_STATUS_SHOWRESULTS: // Results being displayed.

            echo '<status>showresults</status>';
            echo '<url>';
            echo new moodle_url('/mod/quiz/accessrule/tcquiz/review_tcq.php', ['r' => rand(), 'page' => $tcquiz->currentpage,
              'showall' => 'false', 'attempt' => $attempt, 'quizid' => $quizid, 'cmid' => $cmid, 'sessionid' => $tcquiz->id]);
            echo '</url>';
            break;

        case TCQUIZ_STATUS_FINALRESULTS: // Showing the final totals, etc.

            // Submit the attempt first.
            include($CFG->dirroot.'/mod/quiz/accessrule/tcquiz/submitattempt.php');

            echo '<status>finalresults</status>';
            echo '<url>';
            echo new moodle_url('/mod/quiz/accessrule/tcquiz/report_student_final_results.php', ['r' => rand(),
              'attemptid' => $attempt, 'quizid' => $quizid, 'cmid' => $cmid, 'tcqsid' => $sessionid]);
            echo '</url>';
            break;

        default:
            echo '<status>error</status>';
            tcquiz_send_error(get_string('incorrectstatus', 'tcquiz').$status.'\'');
            break;
    }
}

tcquiz_end_response();
