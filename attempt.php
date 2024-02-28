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
 * This script displays a particular page of a quiz attempt that is in progress.
 *
 * @package   mod_quiz
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_quiz\output\navigation_panel_attempt;
use mod_quiz\output\renderer;
use mod_quiz\quiz_attempt;



global $CFG, $DB, $PAGE;

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot.'/mod/quiz/locallib.php');
require_once($CFG->dirroot.'/mod/quiz/accessrule/tcquiz/classes/output/renderer.php');

//Look for old-style URLs, such as may be in the logs, and redirect them to startattemtp.php.
if ($id = optional_param('id', 0, PARAM_INT)) {
    redirect($CFG->wwwroot . '/mod/quiz/startattempt.php?cmid=' . $id . '&sesskey=' . sesskey());
} else if ($qid = optional_param('q', 0, PARAM_INT)) {
    if (!$cm = get_coursemodule_from_instance('quiz', $qid)) {
        throw new \moodle_exception('invalidquizid', 'quiz');
    }
    redirect(new moodle_url('/mod/quiz/startattempt.php',
            ['cmid' => $cm->id, 'sesskey' => sesskey()]));
}

// Get submitted parameters.
$attemptid = required_param('attempt', PARAM_INT);
$page = required_param('page', PARAM_INT);
$cmid = optional_param('cmid', 24, PARAM_INT);
$quizid = required_param('quizid', PARAM_INT);
$sessionid = required_param('sessionid', PARAM_INT);

$attemptobj = quiz_create_attempt_handling_errors($attemptid, $cmid);

//make sure that the quiz is set up as a tcquiz
if (!$tcquiz = $DB->get_record('quizaccess_tcquiz', array('quizid' => $quizid))){
  //Add new exceptions
  throw new moodle_exception('nottcquiz', 'quizaccess_tcquiz', $attemptobj->view_url());
}

//make sure that the user is not trying to attempt the wrong page -- resend them to the start pages if they do
if (!$tcquizsession = $DB->get_record('quizaccess_tcquiz_session', array('id' => $sessionid))){
  throw new moodle_exception('nosession', 'quizaccess_tcquiz', $attemptobj->view_url());

}
//if the state of the quiz is different than TCQUIZ_STATUS_SHOWQUESTION =  20 or TCQUIZ_STATUS_PREVIEWQUESTION = 15 defined in locallib.php
if ($tcquizsession->status != 15  && $tcquizsession->status != 20){
  throw new moodle_exception('notrightquizstate', 'quizaccess_tcquiz', $attemptobj->view_url());

}
//if they are trying to access a different page than what the DB is allowing
if ($tcquizsession->currentpage != $page){
  throw new moodle_exception('notcurrentpage', 'quizaccess_tcquiz', $attemptobj->view_url());
}

//$page = $attemptobj->force_page_number_into_range($page);
$PAGE->set_url($attemptobj->attempt_url(null, $page));
$PAGE->set_cacheable(false);

require_login($attemptobj->get_course(), false, $attemptobj->get_cm());

// Check that this attempt belongs to this user.
if ($attemptobj->get_userid() != $USER->id) {
    if ($attemptobj->has_capability('mod/quiz:viewreports')) {
    } else {

        throw new moodle_exception('notyourattempt', 'quiz', $attemptobj->view_url());
    }
}

// Check capabilities and block settings.
if (!$attemptobj->is_preview_user()) {
    $attemptobj->require_capability('mod/quiz:attempt');
    if (empty($attemptobj->get_quiz()->showblocks)) {
        $PAGE->blocks->show_only_fake_blocks();
    }

} else {
    //navigation_node::override_active_url($attemptobj->start_attempt_url());
}


$output = $PAGE->get_renderer('quizaccess_tcquiz');

// Set up auto-save if required.
$autosaveperiod = get_config('quiz', 'autosaveperiod');
if ($autosaveperiod) {
    $PAGE->requires->yui_module('moodle-mod_quiz-autosave',
            'M.mod_quiz.autosave.init', [$autosaveperiod]);
}


// Log this page view.
$attemptobj->fire_attempt_viewed_event();

// Get the list of questions needed by this page.
$slots = $attemptobj->get_slots($page);

// Check.
if (empty($slots)) {
    throw new moodle_exception('noquestionsfound', 'quiz', $attemptobj->view_url());
}

// Initialise the JavaScript.
$headtags = $attemptobj->get_html_head_contributions($page);
//$PAGE->requires->js_init_call('M.mod_quiz.init_attempt_form', null, false, quiz_get_js_module());
//\core\session\manager::keepalive(); // Try to prevent sessions expiring during quiz attempts.

// Arrange for the navigation to be displayed in the first region on the page.
$navbc = $attemptobj->get_navigation_panel($output, navigation_panel_attempt::class, $page);
$regions = $PAGE->blocks->get_regions();
$PAGE->blocks->add_fake_block($navbc, reset($regions));

$headtags = $attemptobj->get_html_head_contributions($page);
$PAGE->set_title($attemptobj->attempt_page_title($page));
$PAGE->add_body_class('limitedwidth');
$PAGE->set_heading($attemptobj->get_course()->fullname);
$PAGE->activityheader->disable();

$time_left_for_question = $tcquizsession->nextendtime - time();

if ($attemptobj->is_preview_user()){
  echo $output->tcq_teacher_attempt_page($attemptobj, $page, $accessmanager, $messages, $slots, $id, $sessionid,sesskey(), $time_left_for_question);
}
else {
  echo $output->tcq_attempt_page($attemptobj, $page, $accessmanager, $messages, $slots, $id, $sessionid,sesskey(), $time_left_for_question);
}
