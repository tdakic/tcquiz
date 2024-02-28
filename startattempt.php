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
 * This script deals with starting a new attempt at a tcquiz.
 *
 *
 * @package   mod_quiz
 * @copyright 2009 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/*  Adapted for tcquiz frpm mod_quiz startattempt.php by Tamara Dakic, Feb 12, 2024

    If a tcquiz attempt cannot be created the following errors are returned:

    -1  -the teacher tried to create a tcquiz session, but the session with the same name
         already exists

    -2  -the student tried joining a tcquiz session using the joincode, but a tcquiz_session with the
         joincode doesn't exist

    -3  -the student tried joining a tcquiz session using the joincode, but a tcquiz_session with the
         joincode has either not started or is finished

    If there is a current attempt that doesn't belong to the tcq session, the attempt is finished.
    (I assume that a student can have at most one open attempt)

*/

use mod_quiz\quiz_attempt;
use mod_quiz\quiz_settings;

global $DB, $CFG, $PAGE;


require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
//for constants only (to finish a student attempt if needed)
require_once($CFG->dirroot . '/mod/quiz/classes/quiz_attempt.php');

// Get submitted parameters.
$id = required_param('cmid', PARAM_INT); // Course module id
$forcenew = optional_param('forcenew', false, PARAM_BOOL); // Used to force a new preview
$page = optional_param('page', -1, PARAM_INT); // Page to jump to in the attempt.


$quizobj = quiz_settings::create_for_cmid($id, $USER->id);


if ($quizobj->is_preview_user()){
    $joincode = optional_param('joincode','', PARAM_ALPHANUM);
}
else{
    $joincode = required_param('joincode', PARAM_ALPHANUM);
}

// This script should only ever be posted to, so set page URL to the view page.
//$PAGE->set_url($quizobj->view_url());
// During quiz attempts, the browser back/forwards buttons should force a reload.
$PAGE->set_cacheable(false);

// Check login and sesskey.
require_login($quizobj->get_course(), false, $quizobj->get_cm());
require_sesskey();
//$PAGE->set_heading($quizobj->get_course()->fullname);

// If no questions have been set up yet redirect to edit.php or display an error.
if (!$quizobj->has_questions()) {
    if ($quizobj->has_capability('mod/quiz:manage')) {
        redirect($quizobj->edit_url());
    } else {
        throw new \moodle_exception('cannotstartnoquestions', 'quiz', $quizobj->view_url());
    }
}


// Create an object to manage all the other (non-roles) access rules.
$timenow = time();
$accessmanager = $quizobj->get_access_manager($timenow);

$context = $quizobj->get_context();
$quiz = $quizobj->get_quiz();

$tcquiz = $DB->get_record("quizaccess_tcquiz_session", ['quizid' => $quiz->id,'joincode'=>$joincode]);


//teacher tried creating a new session,but a session with the same name exists
if ($quizobj->is_preview_user() && $tcquiz )
{
  echo -1;
  return;
}

//student tried to join but either there was no tcquiz with such joincode, or the quiz was not running
if (!$quizobj->is_preview_user()){
  if (!$tcquiz ){
    echo -2;
    return;
  }
  else if ($tcquiz->status == 0 || $tcquiz->status == 50) {
    echo -3;
    return;
  }
}



//will happen automatically for the teacher?
/*if ($forcenew){
quiz_delete_previews($quiz, $userid = USER->id) ;
}*/

// Validate permissions for creating a new attempt and start a new preview attempt if required.
list($currentattemptid, $attemptnumber, $lastattempt, $messages, $page) =
    quiz_validate_new_attempt($quizobj, $accessmanager, $forcenew, $page, true);


// Check access.
if (!$quizobj->is_preview_user() && $messages) {
    throw new \moodle_exception('attempterror', 'quiz', $quizobj->view_url(),
            $output->access_messages($messages));
}


//this should be the case of the teacher rejoining the quiz - the tecaher can have only one preview
if ($currentattemptid){
  if ($quizobj->is_preview_user()){
    //this should be the case of the teacher rejoining the quiz - the tecaher can have only one preview
    echo $currentattemptid;
    return;
  }
  else{ // need to check if the currentattemptid is in quizaccess_tcquiz_attempt
    //$tcquiz = $DB->get_record("quizaccess_tcquiz_session", ['quizid' => $quiz->id,'joincode'=>$joincode]);
    $tcqattempt = $DB->get_record("quizaccess_tcquiz_attempt", ['attemptid' => $currentattemptid]);

    if (!$tcqattempt || $tcqattempt-> sessionid != $tcquiz->id){
      //finish that attempt
      $unfinishedattempt = quiz_get_user_attempt_unfinished($quiz->id, $USER->id);
      $unfinishedattempt->state = quiz_attempt::FINISHED;
    }
    else {
      // found an attempt that has the required joincode and and is a tcqattempt
      //echo $currentattemptid." ".$tcqattempt-> sessionid;
      echo $currentattemptid;
      return;
    }
  }
}



// new attemps are required for teacher or student

//teacher creates a new record in quizaccess_tcquiz_session
if ($quizobj->is_preview_user()){
  $session = new stdClass();
  $session->timestamp = time();
  $session->joincode = $joincode;
  $session->quizid = $quiz->id;
  $session->id = $DB->insert_record('quizaccess_tcquiz_session', $session);
}
else{
  $session = $DB->get_record("quizaccess_tcquiz_session", ['quizid' => $quiz->id,'joincode'=>$joincode]);
}



$attempt = quiz_prepare_and_start_new_attempt($quizobj, $attemptnumber, $lastattempt);

$sessatempt = new stdClass();
$sessatempt->sessionid = $session->id;
$sessatempt->attemptid = $attempt->id;
$sessatempt->id = $DB->insert_record('quizaccess_tcquiz_attempt', $sessatempt);



echo $attempt->id;
//echo " ";
//echo $session->id;
