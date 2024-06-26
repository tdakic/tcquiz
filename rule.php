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

use mod_quiz\quiz_settings;

require_once(__DIR__ . '/../../../../config.php');

defined('MOODLE_INTERNAL') || die();

// This work-around is required until Moodle 4.2 is the lowest version we support.
if (class_exists('\mod_quiz\local\access_rule_base')) {
    class_alias('\mod_quiz\local\access_rule_base', '\quizaccess_tcquiz_parent_class_alias');
    class_alias('\mod_quiz\form\preflight_check_form', '\quizaccess_tcquiz_preflight_form_alias');
    class_alias('\mod_quiz\quiz_settings', '\quizaccess_tcquiz_quiz_settings_class_alias');
} else {
    require_once($CFG->dirroot . '/mod/quiz/accessrule/accessrulebase.php');
    class_alias('\quiz_access_rule_base', '\quizaccess_tcquiz_parent_class_alias');
    class_alias('\mod_quiz_preflight_check_form', '\quizaccess_tcquiz_preflight_form_alias');
    class_alias('\quiz', '\quizaccess_tcquiz_quiz_settings_class_alias');
}

/**
 * Implementaton of the quizaccess_tcquiz plugin.
 *
 * @package   quizaccess_tcquiz
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * Modified by T.Dakic, April 2024
 */
class quizaccess_tcquiz extends quizaccess_tcquiz_parent_class_alias {

    /**
     * This rule doesn not require a UI check with the user before an attempt is started
     *
     * @param int|null $attemptid the id of the current attempt, if there is one,
     *      otherwise null.
     * @return false
     */
    public function is_preflight_check_required($attemptid) {
          return false;
    }

    /**
     * Whether the user should be blocked from starting a new attempt or continuing
     * an attempt now.
     * @return string false if access should be allowed, a message explaining the
     *      reason if access should be prevented.
     */
    public function prevent_access() {
        if (empty($this->quiz->tcquizrequired)) {
            return false;
        }
        return get_string('accesserror', 'quizaccess_tcquiz');
    }

    /**
     * Return an appropriately configured instance of this rule, if it is applicable
     * to the given quiz, otherwise return null.
     *
     * @param quizaccess_tcquiz_quiz_settings_class_alias $quizobj information about the quiz in question.
     * @param int $timenow the time that should be considered as 'now'.
     * @param bool $canignoretimelimits whether the current user is exempt from
     *      time limits by the mod/quiz:ignoretimelimits capability.
     * @return self|null the rule, if applicable, else null.
     */
    public static function make(quizaccess_tcquiz_quiz_settings_class_alias $quizobj, $timenow, $canignoretimelimits) {

        if (empty($quizobj->get_quiz()->tcquizrequired)) {
            return null;
        }

        return new self($quizobj, $timenow);
    }

    /**
     * Add the pull down for specifying if a quiz should be administerd as a tcquiz
     * and add input field for a default allotted time for each question
     * @param mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param MoodleQuickForm $mform the wrapped MoodleQuickForm.
     */
    public static function add_settings_form_fields(mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {

        $mform->addElement('header', 'tcquizheader', get_string('tcquizsettings', 'quizaccess_tcquiz'));

        $mform->addElement('select', 'tcquizrequired',
                get_string('tcquizrequired', 'quizaccess_tcquiz'),
                [
                    0 => get_string('no', 'quizaccess_tcquiz'),
                    1 => get_string('yes', 'quizaccess_tcquiz'),
                ]);
        $mform->addHelpButton('tcquizrequired', 'tcquizrequired', 'quizaccess_tcquiz');

        $mform->addElement('text', 'questiontime', get_string('questiontime', 'quizaccess_tcquiz'));
        $mform->addRule('questiontime', null, 'numeric', null, 'client');
        $mform->setDefault('questiontime', 30);
        $mform->setType('questiontime', PARAM_INT);
        $mform->addHelpButton('questiontime', 'questiontime', 'quizaccess_tcquiz');
    }

    /**
     * Save any submitted settings when the quiz settings form is submitted. This
     * is called from {@see quiz_after_add_or_update()} in lib.php.
     * @param stdClass $quiz the data from the quiz form, including $quiz->id
     *      which is the id of the quiz being saved.
     */
    public static function save_settings($quiz) {
        global $DB;

        if (empty($quiz->tcquizrequired)) {
            $DB->delete_records('quizaccess_tcquiz', ['quizid' => $quiz->id]);
        } else {
            if (!$DB->record_exists('quizaccess_tcquiz', ['quizid' => $quiz->id])) {
                $record = new stdClass();
                $record->quizid = $quiz->id;
                $record->tcquizrequired = $quiz->tcquizrequired;
                $record->questiontime = $quiz->questiontime;
                $DB->insert_record('quizaccess_tcquiz', $record);
            } else {
                $tcquiz = $DB->get_record('quizaccess_tcquiz', ['quizid' => $quiz->id]);
                $tcquiz->tcquizrequired = $quiz->tcquizrequired;
                $tcquiz->questiontime = $quiz->questiontime;
                $DB->update_record('quizaccess_tcquiz', $tcquiz);
            }
        }
    }

    /**
     * Delete any rule-specific settings when the quiz is deleted. This is called
     * from {@see quiz_delete_instance()} in lib.php.
     * @param stdClass $quiz the data from the database, including $quiz->id
     *      which is the id of the quiz being deleted.
     */
    public static function delete_settings($quiz) {
        global $DB;
        $DB->delete_records('quizaccess_tcquiz', ['quizid' => $quiz->id]);
    }

    /**
     * Return the bits of SQL needed to load all the settings from all the access
     * plugins in one DB query.
     *
     * @param int $quizid the id of the quiz we are loading settings for. This
     *     can also be accessed as quiz.id in the SQL. (quiz is a table alisas for {quiz}.)
     * @return array with three elements:
     *     1. fields: any fields to add to the select list. These should be alised
     *        if neccessary so that the field name starts the name of the plugin.
     *     2. joins: any joins (should probably be LEFT JOINS) with other tables that
     *        are needed.
     *     3. params: array of placeholder values that are needed by the SQL. You must
     *        used named placeholders, and the placeholder names should start with the
     *        plugin name, to avoid collisions.
     */
    public static function get_settings_sql($quizid) {
          return [
              'tcquizrequired, questiontime',
              'LEFT JOIN {quizaccess_tcquiz} tcquiz ON tcquiz.quizid = quiz.id',
              [] ];
    }

    /**
     * This method basically hijacks the mod_quiz/view page to display the tcq starting
     * forms for students and the teacher.
     *
     * @return a message that includes tcq starting
     * forms for students and the teacher
     */
    public function description(): array {

        global $USER;
        global $CFG;
        global $DB;
        global $OUTPUT;

        $quizobj = quiz_settings::create_for_cmid($this->quiz->cmid, $USER->id);
        $context = $quizobj->get_context();

        if (!$quizobj->has_questions()) {
            $messages[] = get_string('configuredastcq', 'quizaccess_tcquiz');
        } else {

            if (has_capability('mod/quiz:preview', $context)) {

                redirect(new \moodle_url('/mod/quiz/accessrule/tcquiz/tcq_teacher_start_page.php', ['id' => $this->quiz->cmid]));
                die();

            } else {

                redirect(new \moodle_url('/mod/quiz/accessrule/tcquiz/tcq_student_start_page.php', ['id' => $this->quiz->cmid]));
                die();

            }
        }
        return $messages;
    }

    /**
     * Currently not used - left in case we allow more than one open tcqsession per tcquiz
     *
     * @param context_module $context the quiz context.
     * @return array of tcqsessions that are in progress for this quiz
     */
    private function get_open_sessions($context): array {
        global $DB;
        global $USER;
        global $CFG;

        $sql = "SELECT * FROM {quizaccess_tcquiz_session} WHERE quizid = :quizid AND status BETWEEN 10 and 40
        ORDER BY timestamp DESC";

        $messages[] = '';
        if (has_capability('mod/quiz:preview', $context) &&
            $sessions = $DB->get_records("quizaccess_tcquiz_session", ['quizid' => $this->quiz->id, 'status>=10'])) {

            $headeradded = false;

            foreach ($sessions as $session) {
                $jc = "'".$session->joincode."'";

                $sql = "SELECT qa.id FROM {quiz_attempts} qa
                              LEFT JOIN {quizaccess_tcquiz_attempt} qta ON qa.id = qta.attemptid
                              WHERE qta.sessionid =:sessionid AND qa.userid = :userid";

                $attemptid = $DB->get_record_sql($sql, ['sessionid' => $session->id, 'userid' => $USER->id]);
                if ($attemptid) {
                    if (!$headeradded) {
                        $messages[] = '<div id="availablesessions">';
                        $messages[] = '<h2>Available sessions</h2>';
                        $messages[] = '<table class="flexible table table-striped table-hover generaltable generalbox">';
                        $messages[] = '<tr><th>Code</th><th>Time started</th><th>Current question</th><th></th><th></th></tr>';
                        $headeradded = true;
                    }
                    $messages[] = '<tr><td>'.$session->joincode.'</td><td>'.date('m/d/Y H:i:s', $session->timestamp).
                      '</td><td>'.$session->currentpage.'</td><td><button type="button" class="btn btn-secondary"
                      onclick="tcquiz_teacher_rejoin('.$jc.','.$session->id.
                      ','.$session->status.','.$attemptid->id.');">Rejoin</button></td>
                      <td><button type="button" class="btn btn-secondary" onclick="tcquiz_end_session('.$session->id.
                      ')">End</button></td></tr>';
                }
            }
            if ($headeradded) {
                $messages[] = '</table></div><br /><br />';
            }
        }
        return $messages;
    }
}
