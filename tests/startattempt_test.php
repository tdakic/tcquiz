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

namespace quizaccess_tcquiz;

use core_question\local\bank\question_version_status;
use mod_quiz\output\view_page;
use question_engine;
use mod_quiz\quiz_settings;
use mod_quiz\quiz_attempt;

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot.'/lib/weblib.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/tcquiz/locallib.php');

/**
 * Unit tests for starting a tcquiz for both teacher and student
 *
 * @package     quizaccess_tcquiz
 * @copyright   2024 Tamara Dakic @ Capilanou University
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class startattempt_test extends \advanced_testcase {

    /**
     * Create quiz and attempt data with layout.
     *
     * @param string $layout layout to set. Like quiz attempt.layout. E.g. '1,2,0,3,4,0,'.
     * @param string $navmethod quiz navigation method (defaults to free)
     * @return quiz_attempt the new quiz_attempt object
     */
    protected function create_quiz_and_attempt_with_layout($layout, $navmethod = QUIZ_NAVMETHOD_FREE) {
        $this->resetAfterTest(true);

        // Make a user to do the quiz.
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        // Make a quiz.
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(['course' => $course->id,
            'grade' => 100.0, 'sumgrades' => 2, 'layout' => $layout, 'navmethod' => $navmethod]);

        $quizobj = quiz_settings::create($quiz->id, $user->id);

        $quba = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();

        $page = 1;
        foreach (explode(',', $layout) as $slot) {
            if ($slot == 0) {
                $page += 1;
                continue;
            }

            $question = $questiongenerator->create_question('shortanswer', null, ['category' => $cat->id]);
            quiz_add_quiz_question($question->id, $quiz, $page);
        }

        return [$quizobj, $quiz, $course, $quba];
    }


    /**
     *  Test student starting a tcq attempt.
     *
     *  When the student types in a joincode and clicks on the Join quiz button on their quiz view page,
     *  the mform includes validation that there is a running session of tcq with the given joincode. Currently,
     *  there should only be one open session of a TCQuiz. The function really tests the function setup_tcquiz_attempt.
     *  The following tests are performed:
     *
     * 1. student never attempted the quiz or there is no in progress attempt. New TCQ attempt is created. (
     *    tested in function test_studentstartattempt_start_clean below
     *  2. there is an open quiz attempt, but it is not a TCQ attempt. Finish it and create a new TCQ attempt.
     *  3. there is an open quiz attempt and it is a TCQ attempt. Use it!
     *
     */
    public function test_studentstartattempt() {

        global $DB;
        $this->resetAfterTest(true);

        list($quizobj, $quiz, $course, $quba) = $this->create_quiz_and_attempt_with_layout('1,2,0,3,4,0,5,6,0');

        $timenow = time();

        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $attempt = quiz_create_attempt($quizobj, 1, false, $timenow, false, $student->id);
        quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        quiz_attempt_save_started($quizobj, $quba, $attempt);

        $this->setUser($student);
        $attemptid = $attempt->id;

        $timenow = time();
        $accessmanager = $quizobj->get_access_manager($timenow);

        // Attempt is not a tcq attempt.
        list($currentattemptid, $attemptnumber, $lastattempt, $messages, $page) =
              quiz_validate_new_attempt($quizobj, $accessmanager, false, -1, false);

        $this->assertEquals($attempt->id, $currentattemptid );

        $sessionid = create_new_tcq_session("testcode", $quiz);
        $session = $DB->get_record("quizaccess_tcquiz_session", ['quizid' => $quiz->id, 'joincode' => "testcode"]);

        // This should close $attempt and create a new tcqattempt.
        $tcqattempt = setup_tcquiz_attempt($quizobj, $session, $currentattemptid, "testcode",
                                                              $accessmanager, $attemptnumber, $lastattempt);

        // Get $attempt from DB because it's staus should have changed.
        $attempt = $DB->get_record("quiz_attempts", ['id' => $attempt->id]);

        // Since the $attempt was not a TCQ attempt, setup_tcquiz_attempt should close it and create a new TCQ attempt.
        $this->assertEquals($attempt->state, \mod_quiz\quiz_attempt::FINISHED);

        $this->assertNotEquals($tcqattempt, $currentattemptid);

        // Now $tcqattempt is the id of a tcq attempt, so when calling quiz_validate_new_attempt and setup_tcquiz_attempt,
        // we should get $tcqattempt back.

        list($currentattemptid, $attemptnumber, $lastattempt, $messages, $page) =
              quiz_validate_new_attempt($quizobj, $accessmanager, false, -1, false);
        $y = setup_tcquiz_attempt($quizobj, $session, $currentattemptid, "testcode", $accessmanager, $attemptnumber, $lastattempt);

        $this->assertEquals($tcqattempt, $y);

    }

    /**
     * Continuation of the above tests. Test
     * 1. student never attempted the quiz or there is no in progress attempt. New TCQ attempt is created.
     */
    public function test_studentstartattempt_start_clean() {

        global $DB;
        $this->resetAfterTest(true);

        list($quizobj, $quiz, $course, $quba) = $this->create_quiz_and_attempt_with_layout('1,2,0,3,4,0,5,6,0');

        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);

        $timenow = time();
        $accessmanager = $quizobj->get_access_manager($timenow);

        list($currentattemptid, $attemptnumber, $lastattempt, $messages, $page) =
              quiz_validate_new_attempt($quizobj, $accessmanager, false, -1, false);

        $sessionid = create_new_tcq_session("testcode", $quiz);
        $session = $DB->get_record("quizaccess_tcquiz_session", ['quizid' => $quiz->id, 'joincode' => "testcode"]);

        $x = setup_tcquiz_attempt($quizobj, $session, $currentattemptid, "testcode", $accessmanager, $attemptnumber, $lastattempt);

        // Check that new entries are created in the DB.
        $attempt = $DB->get_record("quiz_attempts", ['quiz' => $quiz->id, 'userid' => $student->id,
                                                     'state' => \mod_quiz\quiz_attempt::IN_PROGRESS]);
        $this->assertNotNull($attempt);

        $tcqattempt = $DB->get_record("quizaccess_tcquiz_attempt", ['attemptid' => $x, 'sessionid' => $sessionid]);
        $this->assertNotNull($tcqattempt);

    }

    /**
     * When the teacher types in a joincode and clicks on the Start new quiz quiz button on their quiz view page,
     * the mform includes validation ensures that the joincode is not empty and that there is no tcq session with the same joincode.
     *
     * This function really tests validate_and_start_teacher_tcq_attempt function to make sure that:
     * 1. When the teacher starts anew, new quiz attempt is created, new tcq session is created and new tcq attempt is created
     * 2. If there is an existing teacher attempt, make sure that the attempt is finished and the session is finished and new
     *    session and tcq attempt created
     */
    public function test_teacherstartquiz() {
        global $DB;
        $this->resetAfterTest(true);

        list($quizobj, $quiz, $course, $quba) = $this->create_quiz_and_attempt_with_layout('1,2,0,3,4,0,5,6,0');

        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $this->setUser($teacher);

        $timenow = time();
        $accessmanager = $quizobj->get_access_manager($timenow);

        // Create a new quiz attempt.
        list($currentattemptid, $attemptnumber, $lastattempt, $messages, $page) =
            quiz_validate_new_attempt($quizobj, $accessmanager, true, -1, false);

        list($newattemptid, $newsessionid) = validate_and_start_teacher_tcq_attempt($quizobj, "testcode",
                                                                $lastattempt, $attemptnumber, $currentattemptid);

        // Check that the session with the new joincode is created.
        $session = $DB->get_record("quizaccess_tcquiz_session", ['quizid' => $quiz->id, 'joincode' => "testcode"]);
        $this->assertNotNull($session);

        // Check that the teacher's attempt is inserted in the quizaccess_tcquiz_attempt table.
        $tcqteacherattempt = $DB->get_record("quizaccess_tcquiz_attempt",
                                            ['sessionid' => $session->id, 'attemptid' => $newattemptid]);
        $this->assertNotNull($tcqteacherattempt);

        // Teacher starts a new quiz session while the old one is still active.
        list($currentattemptid1, $attemptnumber1, $lastattempt1, $messages1, $page1) =
            quiz_validate_new_attempt($quizobj, $accessmanager, true, -1, false);

        list($newattemptid1, $newsessionid1) = validate_and_start_teacher_tcq_attempt($quizobj, "testcode1",
                                                            $lastattempt1, $attemptnumber1, $currentattemptid1);

        // Check that the previous teacher attempt is deleted.
        $attemptt = $DB->get_record("quiz_attempts", ['id' => $newattemptid]);
        $this->assertEquals(false, $attemptt);

        // Check that the previous tcq session is in state TCQUIZ_STATUS_FINISHED == 50.
        $session = $DB->get_record("quizaccess_tcquiz_session", ['quizid' => $quiz->id, 'id' => $newsessionid]);
        $this->assertEquals(TCQUIZ_STATUS_FINISHED, $session->status);

        // Check that the new tcq session and tcq attempt are created.
        $session = $DB->get_record("quizaccess_tcquiz_session", ['quizid' => $quiz->id, 'joincode' => "testcode1"]);
        $this->assertNotNull($session);

        // Check that the teacher's attempt is inserted in the quizaccess_tcquiz_attempt table.
        $tcqteacherattempt = $DB->get_record("quizaccess_tcquiz_attempt",
                                            ['sessionid' => $newattemptid1, 'attemptid' => $newattemptid1]);
        $this->assertNotNull($tcqteacherattempt);

    }

}
