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
 * This script displays the student start page for tcquiz
 *
 * @package   quizaccess_tcquiz
 * @copyright 2024 Tamara Dakic @Capilano University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace quizaccess_tcquiz;

//use quizaccess_tcquiz\output\renderer;
//use quizaccess_tcquiz\tcquiz_attempt;
use mod_quiz\quiz_settings;

require_once(__DIR__ . '/../../../../config.php');
global $CFG, $PAGE, $USER;

require_once($CFG->dirroot.'/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/tcquiz/locallib.php');
require_once($CFG->dirroot.'/mod/quiz/accessrule/tcquiz/classes/form/tcq_student_join_form.php');

//require_sesskey();

// Get submitted parameters.
$cmid = required_param('id', PARAM_INT);

$quizobj = quiz_settings::create_for_cmid($cmid, $USER->id);

require_login($quizobj->get_course(), false, $quizobj->get_cm());
$context = $quizobj->get_context();

$quizid = $quizobj->get_quizid();

$url = new \moodle_url('/mod/quiz/view.php', ['id' => $cmid]);

if (!has_capability('mod/quiz:attempt', $context)) {
    throw new \moodle_exception('notallowedtoattempttcquiz', 'quizaccess_tcquiz', $url);
}

$PAGE->set_url($url);
$PAGE->set_cacheable(false);

$PAGE->set_title($SITE->fullname);
$PAGE->add_body_class('limitedwidth');


// Check the access rules.
$accessmanager = $quizobj->get_access_manager(time());
$messages = $accessmanager->prevent_access();

// Remove the message added by tcquiz.
$key = array_search(get_string('accesserror', 'quizaccess_tcquiz'), $messages);
unset($messages[$key]);

if ($messages) {
    throw new \moodle_exception('attempterror', 'quiz', $attemptobj->view_url(),
            $output->access_messages($messages));
}

$output = $PAGE->get_renderer('mod_quiz');

$mform = new \tcq_student_join_form(customdata:['cmid' => $cmid, 'quizid' => $quizid ]);

if ($fromform = $mform->get_data()) { // Form validated.

    $url = htmlspecialchars_decode(new \moodle_url('/mod/quiz/accessrule/tcquiz/startattemptstudent.php',
        ['joincode' => $fromform->joincode, 'cmid' => $cmid, 'quizid' => $quizid,
          'sesskey' => sesskey()]), ENT_NOQUOTES);

    redirect($url);
}

echo $output->header();

echo $OUTPUT->render_from_template('quizaccess_tcquiz/student_join_tcq', [
  'quizid' => $quizid, 'cmid' => $cmid, 'formhtml' => $mform->render()]);

echo $output->footer();
