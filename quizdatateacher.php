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
 * This dynamically sends quiz data to clients
 *
 * @copyright Davo Smith <moodle@davosmith.co.uk>
 * @package mod_tcquiz
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

Adapted for tcquiz by Tamara Dakic, Feb 2024
 **/

define('AJAX_SCRIPT', true);

use mod_tcquiz\tcquiz_settings;
use accessrule_tcquiz\tcquiz_attempt;


global $CFG, $DB, $USER, $PAGE;

require_once('../../../../config.php');
require_once($CFG->dirroot.'/mod/quiz/accessrule/tcquiz/locallib.php');
require_once($CFG->dirroot.'/mod/quiz/locallib.php');
require_once($CFG->libdir.'/filelib.php');

require_login();
require_sesskey();
$requesttype = required_param('requesttype', PARAM_ALPHA);
$quizid = required_param('quizid', PARAM_INT);
$attempt = optional_param('attempt', -1, PARAM_INT );
$joincode = required_param('joincode', PARAM_ALPHA);
$cmid = optional_param('cmid', 24, PARAM_INT);
//there is also PARAM_TEXT
/***********************************************************
 * start of main code
 ***********************************************************/

tcquiz_start_response();

if (!$quiz = $DB->get_record("quiz", array('id' => $quizid))) {
    tcquiz_send_error("Quiz ID incorrectt");
    tcquiz_end_response();
    die();
}

if (!$tcquiz = $DB->get_record("quizaccess_tcquiz", array('quizid' => $quizid))){
  echo "<quizid>".$quizid."</quizid>";
  tcquiz_send_error("TCQuiz ID incorrectt");
  tcquiz_end_response();
  die();

}

if (!$session = $DB->get_record('quizaccess_tcquiz_session', array('quizid' => $quizid,'joincode' => $joincode))){
  echo "<code>".$joincode."</code>";
  echo "<quizid>".$quizid."</quizid>";

  tcquiz_send_error("TCQuiz Session incorrect");
  tcquiz_end_response();
  die();
}

if (!$course = $DB->get_record("course", array('id' => $quiz->course))) {
    tcquiz_send_error("Course is misconfigured");
    tcquiz_end_response();
    die();
}
if (!$cm = get_coursemodule_from_instance("quiz", $quiz->id, $course->id)) {
    tcquiz_send_error("Course Module ID was incorrect");
    tcquiz_end_response();
    die();
}
if ($CFG->version < 2011120100) {
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
} else {
    $context = context_module::instance($cm->id);
}
$PAGE->set_context($context);

if (!has_capability('mod/quiz:preview', $context)) {
    tcquiz_send_error(get_string('notallowedattempt', 'tcquiz'));
    tcquiz_end_response();
    die();
}


if ($requesttype == 'startquiz') {

      $session->status = TCQUIZ_STATUS_READYTOSTART;
      $session->currentquestion = 0;
      $session->currentpage = -1;
      $session->currentquestionstate = 1;

      $DB->update_record('quizaccess_tcquiz_session', $session);


      tcquiz_send_running();
      tcquiz_number_students($quizid);
      tcquiz_number_of_questions_in_quiz($quizid);
      //for debugging purposes only
      echo "<tcq_session_id> $session->id </tcq_session_id>";
      echo "<quizedit>".has_capability('mod/quiz:edit', $context)."</quizedit>";
      echo "<quizpreview>".has_capability('mod/quiz:preview', $context)."</quizpreview>";
      echo "<quizmanage>".has_capability('mod/quiz:manage', $context)."</quizmanage>";
      echo "<tcqcontrol>".has_capability('quizaccess/tcquiz:control', $context)."</tcqcontrol>";
      echo "<tcqt>".$tcquiz->questiontime."</tcqt>";

} else if ($requesttype == 'getquestion'){

      $rejoin = required_param('rejoin', PARAM_BOOL);
      $tcqsid = required_param('tcqsid', PARAM_INT);

      $page_slot =  $session->currentpage;

      require_once($CFG->dirroot . '/mod/quiz/accessrule/tcquiz/classes/tcquiz_attempt.php');
      $attemptobj = tcquiz_attempt::create($attempt);
      if ($attemptobj->is_last_page($page_slot))
      {
        $session->status = TCQUIZ_STATUS_FINALRESULTS;
        $DB->update_record('quizaccess_tcquiz_session', $session); // FIXME - not update all fields?
        tcquiz_get_final_results($session);
        return;
      }

      if (!$rejoin){

        $session->currentpage = $page_slot + 1; //The only way to move to the next page
        $page_slots = $attemptobj->get_slots($session->currentpage);
        $session->currentquestion = $page_slots[0];
        $session->currentquestionstate = 1; //running
        $session->status = TCQUIZ_STATUS_PREVIEWQUESTION;

        $session->nextendtime = time() + $tcquiz->questiontime;
        $DB->update_record('quizaccess_tcquiz_session', $session); // FIXME - not update all fields?
      }


      echo '<status>showquestion</status>';
      echo '<url>';
      echo htmlspecialchars_decode(new moodle_url('/mod/quiz/accessrule/tcquiz/attempt.php',['page' => $session->currentpage, 'showall' => false, 'attempt' => $attempt, 'quizid' => $quizid, 'cmid' => $cmid, 'sessionid' => $session->id ]));
      echo '</url>';
}

else if ($requesttype == 'getresults') {

      $session->status = TCQUIZ_STATUS_SHOWRESULTS;
      //$DB->set_field('tcquiz', 'status', $status, array('id' => $quizid));
      $DB->update_record('quizaccess_tcquiz_session', $session);

      sleep(2); // alows everyone to submit

      echo '<status>showresults</status>';
      echo '<url>';
      echo htmlspecialchars_decode(new moodle_url('/mod/quiz/accessrule/tcquiz/review_tcq.php',['page' => $session->currentpage, 'showall' => 'false', 'attempt' => $attempt, 'quizid' => $quizid, 'cmid' => $cmid, 'sessionid' => $session->id ]));
      echo '</url>';

}

else if ($requesttype == 'getnumberstudents') {
    echo '<status>updatenumberstudents</status>';
    tcquiz_number_students($quizid);
}

else if ($requesttype == 'getnumberanswers') {
    echo '<status>updatenumberanswers</status>';
    tcquiz_get_number_of_answers($session->id, $session->currentquestion);
}

else if ($requesttype == 'getfinalresults'){
    $tcqsid = required_param('tcqsid', PARAM_INT);
    $session->status = TCQUIZ_STATUS_FINALRESULTS;
    $DB->update_record('quizaccess_tcquiz_session', $session); // FIXME - not update all fields?
    tcquiz_get_final_results($session);
}

else if ($requesttype == 'endquiz'){
    $tcqsid = required_param('tcqsid', PARAM_INT);
    $session->status = TCQUIZ_STATUS_FINISHED;
    $session->currentpage = $session->currentpage + 1; //for preventing going back?
    $DB->update_record('quizaccess_tcquiz_session', $session); // FIXME - not update all fields?
}

tcquiz_end_response();
