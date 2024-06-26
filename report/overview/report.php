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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/report/overview/overview_options.php');
require_once($CFG->dirroot . '/mod/quiz/report/overview/report.php');

/**
 * Adds to quiz_overview_report in order to display the final quiz statistics graph
 * with the grades of the specified TCQ session
 *
 * @package   quizaccess_tcquiz
 * @copyright 2024 Tamara Dakic @ Capilano University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tcquiz_overview_report extends \quiz_overview_report {

    /**
     * displays the final graph with the grades from the requested tcq session only
     * @param stdClass $quiz - the quiz
     * @param stdClass $cm - the course module being of the quiz
     * @param stdClass $course - the course that contains the quiz
     * @param int $sessionid - the id of a tcqsession
     */
    public function tcq_display_final_graph($quiz, $cm, $course, $sessionid) {
        global $DB, $PAGE, $OUTPUT;

        list($currentgroup, $studentsjoins, $groupstudentsjoins, $allowedjoins) = $this->init(
                'overview', 'quiz_overview_settings_form', $quiz, $cm, $course);

        $options = new quiz_overview_options('overview', $quiz, $cm, $course);
        $options->process_settings_from_params();

        // Load the required questions.
        $questions = quiz_report_get_significant_questions($quiz);

        $this->hasgroupstudents = false;
        if (!empty($groupstudentsjoins->joins)) {
            $sql = "SELECT DISTINCT u.id
                      FROM {user} u
                    $groupstudentsjoins->joins
                     WHERE $groupstudentsjoins->wheres";
            $this->hasgroupstudents = $DB->record_exists_sql($sql, $groupstudentsjoins->params);
        }
        $hasstudents = false;
        if (!empty($studentsjoins->joins)) {
            $sql = "SELECT DISTINCT u.id
                    FROM {user} u
                    $studentsjoins->joins
                    WHERE $studentsjoins->wheres";
            $hasstudents = $DB->record_exists_sql($sql, $studentsjoins->params);
        }

        if ($options->attempts == self::ALL_WITH) {
            // This option is only available to users who can access all groups in
            // groups mode, so setting allowed to empty (which means all quiz attempts
            // are accessible, is not a security porblem.
            $allowedjoins = new \core\dml\sql_join();
        }

        $this->process_actions($quiz, $cm, $currentgroup, $groupstudentsjoins, $allowedjoins, $options->get_url());

        $hasquestions = quiz_has_questions($quiz->id);
        $hasstudents = $hasstudents && (!$currentgroup || $this->hasgroupstudents);

        // Start output.

        // The following are the contents of print_standard_header_and_messages.
        // Seemed easier to include the code than to create more files.
        $this->print_header_and_tabs($cm, $course, $quiz, $this->mode);

        if (groups_get_activity_groupmode($cm)) {
            // Groups are being used, so output the group selector if we are not downloading.
            groups_print_activity_menu($cm, $options->get_url());
        }

        // Print information on the number of existing attempts.
        $attempts = $DB->get_records_sql("SELECT id FROM {quizaccess_tcquiz_attempt} where sessionid={$sessionid}");
        $numattempts = count($attempts) - 1; // Subtract 1 for the teacher attempt.

        // Group mode disregarded here.
        echo '<div class="quizattemptcounts">' . get_string('attemptsnum', 'quiz', $numattempts) .'</div>';

        if (!$hasquestions) {
            echo quiz_no_questions_message($quiz, $cm, $this->context);
        } else if ($currentgroup == self::NO_GROUPS_ALLOWED) {
            echo $OUTPUT->notification(get_string('notingroup'));
        } else if (!$hasstudents) {
            echo $OUTPUT->notification(get_string('nostudentsyet'));
        } else if ($currentgroup && !$this->hasgroupstudents) {
            echo $OUTPUT->notification(get_string('nostudentsingroup'));
        }

        // End of print_standard_header_and_messages.

        if ( $options->usercanseegrades) {
            $output = $PAGE->get_renderer('mod_quiz');
            list($bands, $bandwidth) = self::get_bands_count_and_width($quiz);
            $labels = self::get_bands_labels($bands, $bandwidth, $quiz);

            if ($currentgroup && $this->hasgroupstudents) {
                $sql = "SELECT qg.id
                          FROM {quiz_grades} qg
                          JOIN {user} u on u.id = qg.userid
                        {$groupstudentsjoins->joins}
                          WHERE qg.quiz = $quiz->id AND {$groupstudentsjoins->wheres}";

                if ($DB->record_exists_sql($sql, $groupstudentsjoins->params)) {
                    $data = quiz_report_grade_bands($bandwidth, $bands, $quiz->id, $groupstudentsjoins);
                    $chart = self::get_chart($labels, $data);
                    $groupname = format_string(groups_get_group_name($currentgroup), true, [
                        'context' => $this->context,
                    ]);
                    $graphname = get_string('overviewreportgraphgroup', 'quiz_overview', $groupname);
                    // Numerical range data should display in LTR even for RTL languages.
                    echo $output->chart($chart, $graphname, ['dir' => 'ltr']);
                }

            }

            if ($DB->record_exists('quiz_grades', ['quiz' => $quiz->id])) {

                $data = quiz_report_grade_bands($bandwidth, $bands, $quiz->id, new \core\dml\sql_join());

                $sql = "SELECT sumgrades FROM {quiz_attempts} qa
                                LEFT JOIN {quizaccess_tcquiz_attempt} qta ON qa.id = qta.attemptid
                                WHERE qta.sessionid =:sessionid AND qa.preview = 0";

                $gradestoplot = $DB->get_records_sql($sql, ['sessionid' => $sessionid]);

                $multiplier = $quiz->sumgrades;
                $multiplier = floatval($quiz->grade) / floatval($quiz->sumgrades);

                $frequencies = array_fill(0, $bands, 0);

                foreach ($gradestoplot as $grade) {

                    if (!is_null($grade->sumgrades)) {

                        $index = floor(floatval($grade->sumgrades) * $multiplier / $bandwidth);
                        if ($index == $bands) {
                            $index--;
                        }
                        $frequencies[$index]++;

                    }

                }

                $chart = self::get_chart($labels, $frequencies);
                $graphname = get_string('overviewreportgraph', 'quiz_overview');
                echo $output->chart($chart, $graphname, ['dir' => 'ltr']);
            }
        }
        return true;
    }
}
