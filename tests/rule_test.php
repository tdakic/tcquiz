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

use basic_testcase;
use quizaccess_tcquiz;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/accessrule/tcquiz/rule.php');

/**
 * Unit tests for the quizaccess_tcquiz plugin.
 *
 * @package   quizaccess_tcquiz
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \quizaccess_tcquiz
 */
class rule_test extends \advanced_testcase {
    public function test_tcquiz_rule() {
        $quiz = new stdClass();
        $quiz->attempts = 3;
        $quiz->questions = '';
        $cm = new stdClass();
        $cm->id = 0;
        $quizobj = new \quizaccess_tcquiz_quiz_settings_class_alias($quiz, $cm, null);
        // By default tcquiz is not required.
        $rule = quizaccess_tcquiz::make($quizobj, 0, false);
        $this->assertNull($rule);

        // Quiz set up as tcquiz.
        $quiz->tcquizrequired = true;
        $rule = quizaccess_tcquiz::make($quizobj, 0, false);
        $this->assertInstanceOf('quizaccess_tcquiz', $rule);
        $this->assertEquals($rule->prevent_access(), get_string('accesserror', 'quizaccess_tcquiz'));

    }

    public function test_tcquiz_description_student() {

        $this->resetAfterTest(true);

        // Make a user to do the quiz.
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        // Make a quiz.
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(['course' => $course->id,
            'grade' => 100.0, 'navmethod' => QUIZ_NAVMETHOD_FREE, 'tcquizrequired' => true, 'questiontime' => 60]);

        $quizobj = \quizaccess_tcquiz_quiz_settings_class_alias::create($quiz->id, $user->id);
        // By default tcquiz is not required.
        $rule = quizaccess_tcquiz::make($quizobj, 0, false);
        // No questions in the quiz yet.
        $this->assertEquals($rule->description()[0], get_string('configuredastcq', 'quizaccess_tcquiz'));

        // Add a question.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('shortanswer', null, ['category' => $cat->id]);
        quiz_add_quiz_question($question->id, $quiz, 0);

        /* No idea how to test redirection. This was fine when the code contained css manipulation.
        $ruledesc = $rule->description()[0];
        $this->assertStringContainsString(get_string('joininstruct', 'quizaccess_tcquiz'), $ruledesc);
        // The button was rendered too.
        $this->assertStringContainsString('id="fitem_id_join_session_button"', $ruledesc);
        // And the input field - use regex instead?
        $this->assertStringContainsString('id="id_joincode"', $ruledesc);
        */

    }

    public function test_tcquiz_description_teacher() {

        $this->resetAfterTest(true);

        // Make a user to do the quiz.
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        // Make a quiz.
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(['course' => $course->id,
            'grade' => 100.0, 'navmethod' => QUIZ_NAVMETHOD_FREE, 'tcquizrequired' => true, 'questiontime' => 60]);

        $quizobj = \quizaccess_tcquiz_quiz_settings_class_alias::create($quiz->id, $user->id);
        // By default tcquiz is not required.
        $rule = quizaccess_tcquiz::make($quizobj, 0, false);
        // No questions in the quiz yet.
        $this->assertEquals($rule->description()[0], get_string('configuredastcq', 'quizaccess_tcquiz'));

        // Add a question.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('shortanswer', null, ['category' => $cat->id]);
        quiz_add_quiz_question($question->id, $quiz, 0);

        // Now test the teacher's view.
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        // Login as teacher.
        $this->setUser($teacher);

        /* No idea how to test redirection. This was fine when the code contained css manipulation.
        $ruledesc = $rule->description()[0];
        $this->assertStringContainsString(get_string('teacherstartnewinstruct', 'quizaccess_tcquiz'), $ruledesc );
        // The button was rendered too.
        $this->assertStringContainsString('id="id_start_new_session_button"', $ruledesc );
        // And the input field - use regex instead?
        $this->assertStringContainsString('id="id_joincode"', $ruledesc );
        */
    }
}
