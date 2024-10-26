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
 * This script ends a tcq session and all attempts associated with it.
 * It will put the student attempts in the FINISHED state
 *
 * @package   quizaccess_tcquiz
 * @copyright 2024 Tamara Dakic @Capilano University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_tcquiz;

use mod_quiz\quiz_settings;
use mod_quiz\quiz_attempt;

require_once(__DIR__ . '/../../../../config.php');
require_login();
// For constants only.
require_once($CFG->dirroot . '/mod/quiz/accessrule/tcquiz/locallib.php');

global $DB, $USER;

$sessionid = required_param('id', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);

$quizobj = quiz_settings::create_for_cmid($cmid, $USER->id);

// Make sure that the user has a valid sessionid.
if (!$session = $DB->get_record('quizaccess_tcquiz_session', ['id' => $sessionid])) {
    throw new moodle_exception('nosession', 'quizaccess_tcquiz', $quizobj->view_url());
}

// Make sure that the user is the owner of the session.
if (!$quizobj->is_preview_user() || $session->teacherid != $USER->id) {
      throw new moodle_exception('notyoursession', 'quizaccess_tcquiz', $quizobj->view_url());
}

// Finish the session.
$session->status = TCQUIZ_STATUS_FINISHED;
$DB->update_record('quizaccess_tcquiz_session', $session);

$sql = "SELECT attemptid FROM {quizaccess_tcquiz_attempt} qta WHERE sessionid = :sessionid";
$attemptids = $DB->get_records_sql($sql, ['sessionid' => $sessionid]);

// Close al attempts associated with the session - includes STUDENT attempts.

foreach ($attemptids as $attemptid) {
    $attempt = $DB->get_record('quiz_attempts', ['id' => intval($attemptid->attemptid)]);
    if ( $attempt && $attempt->state != \mod_quiz\quiz_attempt::FINISHED) {
        // Grade the attempt.
        try {
            $attemptobj = tcquiz_attempt::create($attemptid->attemptid);
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
        $attemptobj->process_finish_tcq(time());
        // End grade attempt.
    }
}

redirect(new \moodle_url('/mod/quiz/view.php', ['id' => $cmid ]));
die();
