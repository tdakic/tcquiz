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
 * This page deals with processing responses during an attempt at a quiz.
 *
 * People will normally arrive here from a form submission on attempt.php or
 * summary.php, and once the responses are processed, they will be redirected to
 * attempt.php or summary.php.
 *
 * This code used to be near the top of attempt.php, if you are looking for CVS history.
 *
 * @package   mod_quiz
 * @copyright 2009 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//global $CFG;

use accessrule_tcquiz\tcquiz_attempt;

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/tcquiz/classes/tcquiz_attempt.php');

// Remember the current time as the time any responses were submitted
// (so as to make sure students don't get penalized for slow processing on this page).
$timenow = time();

// Get submitted parameters.
$attemptid     = required_param('attempt',  PARAM_INT);
$thispage      = optional_param('thispage', 0, PARAM_INT);
$nextpage      = optional_param('nextpage', 0, PARAM_INT);
$previous      = optional_param('previous',      false, PARAM_BOOL);
$next          = optional_param('next',          false, PARAM_BOOL);
$finishattempt = optional_param('finishattempt', false, PARAM_BOOL);
$timeup        = optional_param('timeup',        0,      PARAM_BOOL); // True if form was submitted by timer.
$mdlscrollto   = optional_param('mdlscrollto', '', PARAM_RAW);
$cmid          = optional_param('cmid', null, PARAM_INT);
$sessionid  = required_param('sessionid',  PARAM_INT);
//$quizid  = required_param('quizid',  PARAM_INT);

//require_once($CFG->dirroot . '/mod/quiz/accessrule/tcquiz/classes/tcquiz_attempt.php');

//$attemptobj = quiz_create_attempt_handling_errors($attemptid, $cmid);
//$attemptobj = tcquiz_attempt::create($attemptid);



try {
    $attemptobj = tcquiz_attempt::create($attemptid);
} catch (moodle_exception $e) {
    if (!empty($cmid)) {
        list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'quiz');
        $continuelink = new moodle_url('/mod/quiz/view.php', ['id' => $cmid]);
        $context = context_module::instance($cm->id);
        if (has_capability('mod/quiz:preview', $context)) {
            throw new moodle_exception('attempterrorcontentchange', 'quiz', $continuelink);
        } else {
            throw new moodle_exception('attempterrorcontentchangeforuser', 'quiz', $continuelink);
        }
    } else {
        throw new moodle_exception('attempterrorinvalid', 'quiz');
    }
}


$quizid = $attemptobj->get_quizid();

$page = $thispage;

// Check login.
require_login($attemptobj->get_course(), false, $attemptobj->get_cm());
require_sesskey();

if (!$session = $DB->get_record('quizaccess_tcquiz_session', array('id' => $sessionid))){
  throw new moodle_exception('nosession', 'quizaccess_tcquiz', $attemptobj->view_url());
}

//if the state of the quiz is different than TCQUIZ_STATUS_SHOWQUESTION =  20 or TCQUIZ_STATUS_PREVIEWQUESTION = 15 defined in locallib.php
if ($session->status != 15  && $session->status != 20){
  throw new moodle_exception('notrightquizstate', 'quizaccess_tcquiz', $attemptobj->view_url());

}
//if they are trying to access a different page than what the DB is allowing
if ($session->currentpage != $page){
  throw new moodle_exception('notcurrentpage', 'quizaccess_tcquiz', $attemptobj->view_url());
}

// Check that this attempt belongs to this user.
if ($attemptobj->get_userid() != $USER->id) {
    throw new moodle_exception('notyourattempt', 'quiz', $attemptobj->view_url());
}

// Check capabilities.
if (!$attemptobj->is_preview_user()) {
    $attemptobj->require_capability('mod/quiz:attempt');
}

// If the attempt is already closed, send them to the review page.
if ($attemptobj->is_finished()) {
    throw new moodle_exception('attemptalreadyclosed', 'quiz', $attemptobj->view_url());
}

// If this page cannot be accessed, notify user and send them to the correct page.
if (!$finishattempt && !$attemptobj->check_page_access($thispage)) {
    throw new moodle_exception('submissionoutofsequencefriendlymessage', 'question',
            $attemptobj->attempt_url(null, $attemptobj->get_currentpage()));
}
// TTT
//make sure that the user is not trying to attempt the wrong page -- resend them to the start pages if they do
if (!$tcquizsession = $DB->get_record('quizaccess_tcquiz_session', array('id' => $sessionid))){
  throw new moodle_exception('nosession', 'quizaccess_tcquiz', $attemptobj->view_url());

}
//if the state of the quiz is different than TCQUIZ_STATUS_SHOWQUESTION =  20 or TCQUIZ_STATUS_PREVIEWQUESTION = 15 defined in locallib.php
//timing issues?
/*if ($tcquizsession->status != 15  && $tcquizsession->status != 20){
  throw new moodle_exception('notrightquizstate', 'quizaccess_tcquiz', $attemptobj->view_url());

}*/
//if they are trying to access a differnt page than what the DB is allowing
if ($tcquizsession->currentpage != $page){
  throw new moodle_exception('notcurrentpage', 'quizaccess_tcquiz', $attemptobj->view_url());
}


$PAGE->set_cacheable(false);

// Set up auto-save if required.
$autosaveperiod = get_config('quiz', 'autosaveperiod');
if ($autosaveperiod) {
    $PAGE->requires->yui_module('moodle-mod_quiz-autosave',
            'M.mod_quiz.autosave.init', [$autosaveperiod]);
}

//end TTT


// Process the attempt, getting the new status for the attempt.
//$attemptobj->process_auto_save($timenow);
$status = $attemptobj->process_attempt_tcq($timenow, $finishattempt, $timeup, $thispage);

//redirect(htmlspecialchars_decode(new moodle_url('/mod/quiz/accessrule/tcquiz/attempt.php',['page' => $page, 'sesskey' => sesskey(),'showall' => false, 'attempt' => $attemptid, 'sessionid' => $sessionid, 'cmid' => $cmid, 'quizid' => $quizid ])));
if (!$attemptobj->is_preview_user()){
  $url = htmlspecialchars_decode(new moodle_url('/mod/quiz/accessrule/tcquiz/attempt.php',['page' => $page, 'sesskey' => sesskey(),'showall' => false, 'attempt' => $attemptid, 'sessionid' => $sessionid, 'cmid' => $cmid, 'quizid' => $quizid ]));
  header("Location: ". $url);
  exit;
}
else {
  //TCQUIZ_STATUS_SHOWRESULTS defined in localib.php as 30
  $session->status = 30;
  //$DB->set_field('tcquiz', 'status', $status, array('id' => $quizid));
  $DB->update_record('quizaccess_tcquiz_session', $session);

  sleep(1); // alows everyone to submit
  $url = htmlspecialchars_decode(new moodle_url('/mod/quiz/accessrule/tcquiz/review_tcq.php',['page' => $page, 'sesskey' => sesskey(),'showall' => false, 'attempt' => $attemptid, 'sessionid' => $sessionid, 'cmid' => $cmid, 'quizid' => $quizid ]));
  header("Location: ". $url);
  exit;
}
return 1;
//die();
