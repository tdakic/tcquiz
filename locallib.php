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
 * Internal functions
 *
 * @package   mod_tcquiz
 * @copyright 2014 Davo Smith
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/** Quiz not running */
define('TCQUIZ_STATUS_NOTRUNNING', 0);
/** Quiz ready to start */
define('TCQUIZ_STATUS_READYTOSTART', 10);
/** Quiz showing 'review question' page */
define('TCQUIZ_STATUS_PREVIEWQUESTION', 15);
/** Quiz showing a question */
define('TCQUIZ_STATUS_SHOWQUESTION', 20);
/** Quiz showing results */
define('TCQUIZ_STATUS_SHOWRESULTS', 30);
/** Quiz showing the final results */
define('TCQUIZ_STATUS_FINALRESULTS', 40);
define('TCQUIZ_STATUS_FINISHED', 50);

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
 * @param int $quizid
 * @throws coding_exception
 * @throws dml_exception
 */
function tcquiz_number_students($quizid) {

    global $CFG, $DB, $USER;

    $quizid = required_param('quizid', PARAM_INT);
    $attempts = $DB->get_records_sql("SELECT id FROM {quiz_attempts} where quiz={$quizid} AND state='inprogress' AND preview=0");
    $num_students = sizeof($attempts);
    echo "<numberstudents>";
    echo $num_students;
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
 * @throws dml_exception
 */
function tcquiz_send_await_question() {
    //TTT
    $waittime = get_config('tcquiz', 'awaittime');
    //$waittime = 10;
    $waittime = 2;
    echo '<status>waitforquestion</status>';
    echo "<waittime>{$waittime}</waittime>";
    //echo add_requesttype($requesttype);
}

/**
 * Send 'waiting for results' status.
 * @param int $timeleft
 * @throws dml_exception
 */
function tcquiz_send_await_results($timeleft) {
    $waittime = (int)get_config('tcquiz', 'awaittime');
    // We need to randomise the waittime a little, otherwise all clients will
    // start sending 'waitforquestion' simulatiniously after the first question -
    // it can cause a problem is there is a large number of clients.
    // If waittime is 1 sec, there is no point to randomise it.
    $waittime = 2;
    // TTT
    //$waittime = mt_rand(1, $waittime) + $timeleft;
    echo '<status>waitforresults</status>';
    echo "<waittime>{$waittime}</waittime>";
}



/**
 * Is the quiz currently running?
 * @param int $status
 * @return bool
 */
function tcquiz_is_running($status) {
    return ($status > TCQUIZ_STATUS_NOTRUNNING && $status < TCQUIZ_STATUS_FINALRESULTS);
}

function tcquiz_get_final_results($sessionid,$cmid,$quizid){
  global $CFG;
  //$quiz->status = TCQUIZ_STATUS_FINALRESULTS;
  //$DB->update_record('tcquiz', $quiz); // FIXME - not update all fields?

  //tcquiz_send_final_results($quizid);
  //$url = htmlspecialchars_decode(new moodle_url('/mod/quiz/accessrule/tcquiz/attempt.php',['page' => $session->currentpage, 'showall' => 0, 'attempt' => $attempt, 'quizid' => $quizid, 'cmid' => $cmid, 'sessionid' => $session->id, 'sesskey' => $USER->sesskey ]));

  sleep(2);
  tcquiz_start_response();
  echo '<status>finalresults</status>';
  echo '<classresult>';
  $_GET["id"]=$cmid;
  $_GET["tcqsid"]=$sessionid;
  //$_GET["mode"]="no-cors";
  $_GET["mode"]="overview";
  echo "<url>";
  echo htmlspecialchars_decode(new moodle_url('/mod/quiz/accessrule/tcquiz/report_final_results.php',['mode' => 'overview', 'id' =>$cmid, 'tcqsid' => $sessionid, 'quizid'=>$quizid ]));
  //echo $CFG->dirroot.'/mod/quiz/accessrule/tcquiz/report_final_results.php?mode=overview&id='.$cmid.'&tcquiz='.$sessionid;
  echo "</url>";
  //$tmp = include($CFG->dirroot.'/mod/tcquiz/report_final_results.php');
  //$tmp = include('./report_final_results.php');
  //echo $tmp;
  echo '</classresult>';
  tcquiz_end_response();

}


function tcquiz_get_number_of_answers($sessoinid, $slot){
  global $DB;
  // look if the first slot on the page is submitted. Good enough?

  $sql = "SELECT COUNT(*) FROM {question_attempts} tcqa
              LEFT JOIN {quiz_attempts} qata ON qata.uniqueid = tcqa.questionusageid
              LEFT JOIN {quizaccess_tcquiz_attempt} tcta ON tcta.attemptid=qata.id
              LEFT JOIN {question_attempt_steps} tctas ON tcqa.id = tctas.questionattemptid
              WHERE tcta.sessionid=:sessionid AND tcqa.slot = :slot AND tctas.state = 'complete'" ;

  $count = $DB->count_records_sql($sql, array('sessionid'=>$sessoinid, 'slot'=>$slot));

  echo "<numanswers>".$count."</numanswers>";

}

//***************************************************************************
//might not be needed now but useful in some form in the future

function add_requesttype($requesttype)
{
  echo "<requesttype>";
  echo $requesttype;
  echo "</requesttype>";
}

/**
 * Check the question requested matches the current question.
 * @param int $quizid
 * @param int $questionnumber
 * @return bool
 * @throws dml_exception
 */
function tcquiz_current_question($quizid, $questionnumber) {
    global $DB;


    $questionid = $DB->get_field('quizaccess_tcquiz_session', 'currentquestion', array('id' => $quizid));
    if (!$questionid) {
        return false;
    }
  if ($questionnumber != $questionid) {
        return false;
    }


    return true;
}



function tcquiz_number_of_questions_in_quiz($quizid){
  global $DB;

  $questioncount = $DB->count_records('quiz_slots', ['quizid' => $quizid]);
  echo "<questioncount>{$questioncount}</questioncount>";

}


//************************************************************************
