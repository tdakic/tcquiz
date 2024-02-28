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

namespace accessrule_tcquiz;


/**
 * This class represents one user's attempt at a particular quiz.
 *
 * @package   mod_quiz
 * @copyright 2008 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use question_engine;
//use question_display_options;
use mod_quiz\question\display_options;
use mod_quiz\quiz_attempt;
use mod_quiz\access_manager;

class tcquiz_attempt extends quiz_attempt{

  public function __construct($attempt, $quiz, $cm, $course, $loadquestions = true) {
    parent::__construct($attempt, $quiz, $cm, $course, $loadquestions);
  }

  /**
   * Used by {create()} and {create_from_usage_id()}.
   *
   * @param array $conditions passed to $DB->get_record('quiz_attempts', $conditions).
   * @return quiz_attempt the desired instance of this class.
   */
  protected static function create_helper($conditions) {
      global $DB;

      $attempt = $DB->get_record('quiz_attempts', $conditions, '*', MUST_EXIST);
      $quiz = access_manager::load_quiz_and_settings($attempt->quiz);
      $course = get_course($quiz->course);
      $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);

      // Update quiz with override information.
      $quiz = quiz_update_effective_access($quiz, $attempt->userid);

      return new tcquiz_attempt($attempt, $quiz, $cm, $course);
  }

  /**
   * Static function to create a new quiz_attempt object given an attemptid.
   *
   * @param int $attemptid the attempt id.
   * @return quiz_attempt the new quiz_attempt object
   */
  public static function create($attemptid) {
      return self::create_helper(['id' => $attemptid]);
  }

  //TTT - just can't get that little flag to show as clickable in an attemp. is it a css issue?

  public function get_display_options($reviewing) {

        if ($reviewing) {
            if (is_null($this->reviewoptions)) {
                $this->reviewoptions = quiz_get_review_options($this->get_quiz(),
                        $this->attempt, $this->quizobj->get_context());
                if ($this->is_own_preview()) {
                    // It should  always be possible for a teacher to review their
                    // own preview irrespective of the review options settings.
                    $this->reviewoptions->attempt = true;
                }
            }
            //TTT added
            //this->reviewoptions->attempt = true;
            $this->reviewoptions->feedback = display_options::VISIBLE;
            $this->reviewoptions->overallfeedback = display_options::VISIBLE;
            $this->reviewoptions->generalfeedback = display_options::VISIBLE;
            $this->reviewoptions->numpartscorrect = display_options::VISIBLE;
            $this->reviewoptions->correctness = display_options::VISIBLE;
            $this->reviewoptions->specificfeedback = display_options::VISIBLE;
            $this->reviewoptions->rightanswer = display_options::VISIBLE;
            $this->reviewoptions->marks = display_options::VISIBLE;
            $this->reviewoptions->correctness = display_options::VISIBLE;

             //$this->reviewoptions->flags = 1;
            //var_dump($this->reviewoptions);
            return $this->reviewoptions;

        } else {
            $options = display_options::make_from_quiz($this->get_quiz(),
                    display_options::DURING);
            //TTT added
            $options->attempt = true;
            $options->feedback = display_options::VISIBLE;
            $options->overallfeedback = display_options::VISIBLE;
            $options->generalfeedback = display_options::VISIBLE;
            $options->specificfeedback = display_options::VISIBLE;
            $options->numpartscorrect = display_options::VISIBLE;
            $options->correctness = display_options::VISIBLE;
            $options->rightanswer = display_options::VISIBLE;
            $options->marks = display_options::VISIBLE;
            $options->correctness = display_options::VISIBLE;


            //$options->flags = quiz_get_flag_option($this->attempt, $this->quizobj->get_context());
            $options->flags = 1; //0 - hidden 2 - EDITABLE 1-visible
            //var_dump($options);
            return $options;
        }
    }



    public function process_finish_tcq($timestamp, $timefinish = null, $studentisonline = false) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

      /*  if ($processsubmitted) {
            $this->quba->process_all_actions($timestamp);
        }
        $this->quba->finish_all_questions($timestamp);

        question_engine::save_questions_usage_by_activity($this->quba);
*/
        $this->attempt->timemodified = $timestamp;
        $this->attempt->timefinish = $timefinish ?? $timestamp;
        $this->attempt->sumgrades = $this->quba->get_total_mark();
        $this->attempt->state = self::FINISHED;
        $this->attempt->timecheckstate = null;
        $this->attempt->gradednotificationsenttime = null;

        if (!$this->requires_manual_grading() ||
                !has_capability('mod/quiz:emailnotifyattemptgraded', $this->get_quizobj()->get_context(),
                        $this->get_userid())) {
            $this->attempt->gradednotificationsenttime = $this->attempt->timefinish;
        }

        $DB->update_record('quiz_attempts', $this->attempt);

        if (!$this->is_preview()) {
            $this->recompute_final_grade();

            // Trigger event.
            $this->fire_state_transition_event('\mod_quiz\event\attempt_submitted', $timestamp, $studentisonline);

            // Tell any access rules that care that the attempt is over.
            $this->get_access_manager($timestamp)->current_attempt_finished();
        }

        $transaction->allow_commit();
    }

    public function process_attempt_tcq($timenow, $finishattempt, $timeup, $thispage) {
        global $DB;


        $transaction = $DB->start_delegated_transaction();

        /* Tried to have the submit button for every question being able to be clicked more than once but couldn't get it to work */
        //question_engine::delete_questions_usage_by_activity($this->$quba->get_id());
        //$qubaids = new qubaid_list(array(1));
        //public static function delete_questions_usage_by_activity($qubaid) {
        //    self::delete_questions_usage_by_activities(new qubaid_list(array($qubaid)));
        //}


        //$slots_on_this_page = $this->get_slots($thispage);

      /* foreach ($slots_on_this_page as $slot) {
      $     qa = $this->get_question_attempt($slot);
        if ($qa !=null){
          $qaid = $qa->get_database_id();
          echo $qaid;
          echo " ";
          question_engine::delete_questions_usage_by_activity($qaid);
          }
    }

    */
            //need to add the attempt to mdl_question_response_analysis
            //cache($qubaids, $whichtries, $questionid, $variantno, $subpartid, $responseclassid, $calculationtime = null)


            // Just process the responses for this page and go to the next page.
            $slots_on_this_page = $this->get_slots($thispage);

            try {
              $this->quba->process_all_actions($timestamp);
              foreach ($slots_on_this_page as $slot) {
                $this->quba->finish_question($slot,$timenow);
              }
              question_engine::save_questions_usage_by_activity($this->quba);

            } catch (question_out_of_sequence_exception $e) {
                throw new moodle_exception('submissionoutofsequencefriendlymessage', 'question',
                        $this->attempt_url(null, $thispage));

            } catch (Exception $e) {
                // This sucks, if we display our own custom error message, there is no way
                // to display the original stack trace.
                $debuginfo = '';
                if (!empty($e->debuginfo)) {
                    $debuginfo = $e->debuginfo;
                }
                throw new moodle_exception('errorprocessingresponses', 'question',
                        $this->attempt_url(null, $thispage), $e->getMessage(), $debuginfo);
            }
            $this->fire_attempt_updated_event();
            //$this->quba->process_all_actions($timenow, $slots_on_this_page);

            //This will help with the statistics  */
          //  foreach ($slots_on_this_page as $slot) {
        //      $this->quba->finish_question($slot,$timenow);
          //  }
            //ttt Is what is below still nedeed for statistics? it makes it so the student can't submit?
            //$this->quba->process_all_actions($timestamp);
          //  question_engine::save_questions_usage_by_activity($this->quba);

            $this->attempt->timemodified = $timenow;
            $this->attempt->state = self::IN_PROGRESS;
            $DB->update_record('quiz_attempts', $this->attempt);

            $transaction->allow_commit();

            return self::IN_PROGRESS;
    }

}
