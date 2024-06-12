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
 * Functions needed for tcquiz
 *
 * @package   quizaccess_tcquiz
 * @copyright 2024, Tamara Dakic @Capilano University
 * based on locallib.php from the realtimequiz module by Davo Smith <moodle@davosmith.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');
global $CFG;

/* Read the JSON file with tcq constants instead defining constants below,
   so the same values can be used in JS and php.
   See below for what should be defined */

$json = file_get_contents($CFG->dirroot.'/mod/quiz/accessrule/tcquiz/tcq_constants.json');
$constdata = json_decode($json, true);

foreach ($constdata as $key => $value) {
    define($key, $value);
}

/** Quiz not running define('TCQUIZ_STATUS_NOTRUNNING', 0); */
/** Quiz ready to start define('TCQUIZ_STATUS_READYTOSTART', 10); */
/** Quiz showing 'review question' page define('TCQUIZ_STATUS_PREVIEWQUESTION', 15); */
/** Quiz showing a question define('TCQUIZ_STATUS_SHOWQUESTION', 20); */
/** Quiz showing results define('TCQUIZ_STATUS_SHOWRESULTS', 30); */
/** Quiz showing the final results define('TCQUIZ_STATUS_FINALRESULTS', 40); */
/** Quiz is finished - can't be interacted with define('TCQUIZ_STATUS_FINISHED', 50); */

/**
 * Output the response start
 */
function tcquiz_start_response() {
    header('content-type: text/xml');
    echo '<?xml version="1.0"?><tcquiz>';
}

/**
 * Output the response end
 */
function tcquiz_end_response() {
    echo '</tcquiz>';
}

/**
 * Send the given error messsage
 * @param string $msg
 */
function tcquiz_send_error($msg) {
    echo "<status>error</status><message><![CDATA[{$msg}]]></message>";
}

/**
 * Count the number of students connected
 * @param int $quizid the id of the quiz that is being administered as tcquiz
 * @param int $sessionid the session id of the current tcq session
 */
function tcquiz_number_students($quizid, $sessionid) {

    global $DB;

    $quizid = required_param('quizid', PARAM_INT);
    $attempts = $DB->get_records_sql("SELECT id FROM {quizaccess_tcquiz_attempt} where sessionid={$sessionid}");
    $numstudents = count($attempts);
    echo "<numberstudents>";
    // The teacher attempt should not count.
    echo $numstudents - 1;
    echo "</numberstudents>";
}


/**
 * Send 'quiz running' status.
 */
function tcquiz_send_running() {
    echo '<status>waitforquestion</status>';

}

/**
 * Send 'quiz not running' status.
 */
function tcquiz_send_not_running() {
    echo '<status>quiznotrunning</status>';
}

/**
 * Send 'waiting for question to start' status.
 */
function tcquiz_send_await_question() {
    echo '<status>waitforquestion</status>';
}

/**
 * Send 'waiting for results' status.
 * @param int $timeleft
 */
function tcquiz_send_await_results($timeleft) {
    echo '<status>waitforresults</status>';
}

/**
 * Is the quiz currently running?
 * @param int $status
 * @return bool
 */
function tcquiz_is_running($status) {
    return ($status > TCQUIZ_STATUS_NOTRUNNING && $status < TCQUIZ_STATUS_FINALRESULTS);
}

/**
 * Gets the url of the script that displays the teacher's final results.
 *
 * @param int $sessionid the session id of the current tcq session
 * @param int $cmid course module id of the current quiz
 * @param int $quizid the id of the quiz that is being administered as tcquiz
 * @return xml with url of the script that displays the final results
 */
function tcquiz_get_final_results($sessionid, $cmid, $quizid) {
    sleep(2); // So everyone has time to submit.

    echo '<status>finalresults</status>';
    echo "<url>";
    echo new moodle_url('/mod/quiz/accessrule/tcquiz/report_final_results.php',
                  [ 'id' => $cmid, 'tcqsid' => $sessionid, 'quizid' => $quizid ]);
    echo "</url>";
}

/**
 * Counts the number of submitted student answers for a question.
 *
 * @param int $sessoinid the session id of the current tcq session
 * @param int $slot the slot for which we are counting the number of submitted answers
 * @return xml with the number of submitted answers for the given session of the tcquiz and the given slot
 */
function tcquiz_get_number_of_answers($sessoinid, $slot) {
    global $DB;
    // Look if the first slot on the page is submitted. Good enough?
    $sql = "SELECT COUNT(DISTINCT questionattemptid) FROM {question_attempts} tcqa
              LEFT JOIN {quiz_attempts} qata ON qata.uniqueid = tcqa.questionusageid
              LEFT JOIN {quizaccess_tcquiz_attempt} tcta ON tcta.attemptid=qata.id
              LEFT JOIN {question_attempt_steps} tctas ON tcqa.id = tctas.questionattemptid
              WHERE tcta.sessionid=:sessionid AND tcqa.slot = :slot AND
              (tctas.state = 'complete' OR tctas.state = 'gaveup' OR tctas.state = 'invalid')";

    $count = $DB->count_records_sql($sql, ['sessionid' => $sessoinid, 'slot' => $slot]);
    echo "<numanswers>".$count."</numanswers>";
}

/**
 * Insert a new tcquiz_attempt into DB
 *
 * @param int $sessid the session id of the current tcq session
 * @param int $newattemptid the attempt id of the started attemp
 * @return int the id of the new tcquiz_attempt
 */
function create_new_tcq_attempt($sessid, $newattemptid) {

    global $DB;

    $sessatempt = new stdClass();
    $sessatempt->sessionid = $sessid;
    $sessatempt->attemptid = $newattemptid;
    $sessatempt->id = $DB->insert_record('quizaccess_tcquiz_attempt', $sessatempt);

    return $sessatempt->id;

}
/**
 * creates a new quiz attempt, new tcq teacher attempt and a new tcq session.
 *
 * @param quiz_settings $quizobj quiz object
 * @param alphanum $joincode the joincode for the session of tcquiz
 * @param stdClass $lastattempt last attempt object
 * @param int $attemptnumber the attempt number
 * @param int $currentattemptid the id of the current quiz attempt
 * @return array the id of the new quiz attempt and the id of the new tcq session
 */
function validate_and_start_teacher_tcq_attempt($quizobj, $joincode, $lastattempt, $attemptnumber, $currentattemptid) {
    $quiz = $quizobj->get_quiz();
    close_running_tcq_session($quiz);
    // Finish the current teacher attempt.
    if ($currentattemptid) {
        $lastattempt->state = quiz_attempt::FINISHED;
    }
    $newattempt = quiz_prepare_and_start_new_attempt($quizobj, $attemptnumber, $lastattempt);

    $sessid = create_new_tcq_session($joincode, $quiz);

    $sessattemptid = create_new_tcq_attempt($sessid, $newattempt->id);

    return [$newattempt->id, $sessid];
}

/**
 * This function checks if the $currentattemptid corresponds to an attempt of a TCQuiz. If yes, it returns $currentattemptid
 * If no, the function closes the attempt whose id is $currentattemptid, creates a new quiz attempt and associates the
 * quiz attempt with the TCQ attempt and TCQ session and returns its id
 * PRE: $joincode is a code of running TCQ session
 * @param quiz_settings $quizobj quiz object
 * @param stdClass $session tcqsession object
 * @param int $currentattemptid the id of the current quiz attempt
 * @param alphanum $joincode the joincode for the session of tcquiz
 * @param access_manager $accessmanager accessmanager for this quiz
 * @param int $attemptnumber the attempt number
 * @param stdClass $lastattempt last attempt object
 * @return int the id of the new quiz attempt and the id of the new tcq session
 */
function setup_tcquiz_attempt($quizobj, $session, $currentattemptid, $joincode, $accessmanager, $attemptnumber, $lastattempt) {

    global $DB;

    /*  If $currentattemptid exists, there is an open student attempt. If that attempt doesn't correspond to the one with the
        joincode close it, else return the currentattemptid. */

    if ($currentattemptid) {
        // Need to check if the currentattemptid is in quizaccess_tcquiz_attempt.
        $tcqattempt = $DB->get_record("quizaccess_tcquiz_attempt", ['attemptid' => $currentattemptid]);

        if (!$tcqattempt || $tcqattempt->sessionid != $session->id) {
            // Finish that attempt.
            $unfinishedattempt = $DB->get_record("quiz_attempts", ['id' => $currentattemptid]);
            $unfinishedattempt->state = mod_quiz\quiz_attempt::FINISHED;
            $unfinishedattempt->timefinish = time();
            $DB->update_record("quiz_attempts", $unfinishedattempt);

            // Start afresh.
            $forcenew = true;
            $page = -1;
            list($currentattemptid, $attemptnumber, $lastattempt, $messages, $page) =
                quiz_validate_new_attempt($quizobj, $accessmanager, $forcenew, $page, false);
        } else {
            // Found an attempt that has the required joincode and and is a tcqattempt.
            return $currentattemptid;
        }
    }

    $attempt = quiz_prepare_and_start_new_attempt($quizobj, $attemptnumber, $lastattempt);
    create_new_tcq_attempt($session->id, $attempt->id);
    return $attempt->id;
}

/**
 * Insert a new tcquiz_session into DB.
 *
 * @param string $joincode the joinode of the current tcq session
 * @param stdClass $quiz standard moodle quiz
 * @return int the id of the new tcq_session
 */
function create_new_tcq_session($joincode, $quiz) {

    global $DB, $USER;

    // For now there should only be one open TCQ session.
    // If everything goes right, one should only need to close that one session.
    close_all_tcq_sessions();

    $session = new stdClass();
    $session->timestamp = time();
    $session->joincode = $joincode;
    $session->quizid = $quiz->id;

    $session->status = TCQUIZ_STATUS_READYTOSTART;
    $session->currentpage = -1;
    $session->currentpagestate = 1;
    $session->teacherid = $USER->id;
    $session->id = $DB->insert_record('quizaccess_tcquiz_session', $session);

    return $session->id;

}

// Function below might not be needed now but useful in some form in the future.

/**
 * Add the requesttype to xml - good for debuging.
 * @param string $requesttype - current requestype
 * @return xml that contains the requesttype
 */
function add_requesttype($requesttype) {
    echo "<requesttype>";
    echo $requesttype;
    echo "</requesttype>";
}

/**
 * Adds the number of questions in the quiz to xml.
 * @param int $quizid the id of the quiz
 */
function tcquiz_number_of_questions_in_quiz($quizid) {
    global $DB;

    $questioncount = $DB->count_records('quiz_slots', ['quizid' => $quizid]);
    echo "<questioncount>{$questioncount}</questioncount>";

}

/**
 * Close the tcqsessions associated with the given quiz.
 * @param stdClass $quiz the quiz
 */
function close_running_tcq_session($quiz) {

    global $DB;
    global $USER;
    // Check to see if an open attempt of the teacher is a tcquiz attempt.
    $sql = "SELECT * FROM {quizaccess_tcquiz_attempt} qta
                    LEFT JOIN {quiz_attempts} qa ON qta.attemptid = qa.id
                    WHERE qa.state = 'inprogress' AND qa.quiz = :quizid AND qa.userid = :uid";

    if ($attempt = $DB->get_record_sql($sql, ['quizid' => $quiz->id, 'uid' => $USER->id])) {
        // Get the session assocaited with the teacher's attempt and set its status to 50.
        // Should be between 10 and 40!!!!!!!!!!
        $sql = "SELECT * FROM {quizaccess_tcquiz_session} WHERE id = :sessid AND status BETWEEN :running and :results";
        if ($sess = $DB->get_record_sql($sql,
              ['sessid' => $attempt->sessionid, 'running' => TCQUIZ_STATUS_READYTOSTART,
               'results' => TCQUIZ_STATUS_FINALRESULTS])) {

            $sess->status = TCQUIZ_STATUS_FINISHED;
            $DB->update_record('quizaccess_tcquiz_session', $sess);
            return $attempt->sessionid;
        }
    }
    return 0;
}

/**
 * Return the list with information of the tcquiz session that is currently running
 * @param context_module $quizid the id of this quiz.
 * @return array('sessionid', 'joincode', 'timestamp', 'currentpage', 'status',  'attemptid');
 */
function get_open_session($quizid): array {

    global $DB;
    global $USER;

    // Get the session that is in progress (status between 10-40 inclusive).
    // Should be only one if the housekeeping is done right.

    $sql = "SELECT * FROM {quizaccess_tcquiz_attempt} qta
                    LEFT JOIN {quiz_attempts} qa ON qta.attemptid = qa.id
                    WHERE qa.state = 'inprogress' AND qa.quiz = :quizid AND qa.userid = :uid";

    // Check to see if an open attempt of the teacher is a tcquiz attempt.
    if (!$attempt = $DB->get_record_sql($sql, ['quizid' => $quizid, 'uid' => $USER->id])) {
        return [];
    } else {
        // Get the session assocaited with the teacher's attempt and return its data.
        // Status should be between 10 (TCQUIZ_STATUS_READYTOSTART) and 40 (TCQUIZ_STATUS_FINALRESULTS).
        $sql = "SELECT * FROM {quizaccess_tcquiz_session} WHERE id = :sessid AND status BETWEEN :running and :results";
        if (!$sess = $DB->get_record_sql($sql, ['sessid' => $attempt->sessionid, 'running' => TCQUIZ_STATUS_READYTOSTART,
            'results' => TCQUIZ_STATUS_FINALRESULTS])) {
            return [];
        } else {
            return ['sessionid' => $sess->id, 'joincode' => $sess->joincode,
              'timestamp' => date('m/d/Y H:i:s', $sess->timestamp), 'currentpage' => $sess->currentpage,
              'status' => $sess->status, 'attemptid' => $attempt->id];
        }
    }
}


/**
 * Puts all tcqsessions in TCQUIZ_STATUS_FINISHED state
 * For now there should only be one open TCQ session, so this is for development
 */
function close_all_tcq_sessions() {
    global $DB;
    $sql = "SELECT * FROM {quizaccess_tcquiz_session}";
    $allsessions = $DB->get_records_sql($sql, []);
    foreach ($allsessions as $s) {
        if ($s->status != TCQUIZ_STATUS_FINISHED) {
            $s->status = TCQUIZ_STATUS_FINISHED;
            $DB->update_record('quizaccess_tcquiz_session', $s);
        }
    }
}
