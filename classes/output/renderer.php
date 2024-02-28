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

//namespace accessrule_tcquiz;
namespace quizaccess_tcquiz\output;

//use mod_quiz;
//namespace quizaccess_offlineattempts;
//namspace mod_quiz_accessrule\tcquiz;

use cm_info;
use coding_exception;
use context;
use context_module;
use html_table;
use html_table_cell;
use html_writer;
use mod_quiz\access_manager;
use mod_quiz\form\preflight_check_form;
use mod_quiz\question\display_options;
use mod_quiz\quiz_attempt;
use moodle_url;
use plugin_renderer_base;
use popup_action;
use question_display_options;
use mod_quiz\quiz_settings;
use renderable;
use single_button;
use stdClass;

/**
 * The main renderer for the quiz module.
 *
 * @package   mod_quiz
 * @category  output
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \mod_quiz\output\renderer {


public function tcq_transfer_parameters_to_js($attemptobj, $page, $slots, $id, $sessionid, $sesskey){
  global $DB;
  global $CFG;
  /* The following should be changed at somepoint */
  $tcquiz = $DB->get_record('quizaccess_tcquiz_session', array('id' => $sessionid));
  $joincode = $tcquiz->joincode;
  /* end unnecessary code + set_joincode below should be deleted*/

  return '<script type="text/javascript"> window.addEventListener("load", function() {
                tcquiz_set_sessionid('.$sessionid.');
                tcquiz_set_siteroot("'.$CFG->wwwroot.'");
                tcquiz_set_quizid('.$attemptobj->get_quizid().');
                tcquiz_set_page('.$page.');
                tcquiz_set_attemptid('.$attemptobj->get_attemptid().');
                tcquiz_set_sesskey("'.$sesskey.'");
                tcquiz_set_joincode("'.$joincode.'");
                tcquiz_set_controlquiz('.$attemptobj->is_preview_user().');
                tcquiz_set_cmid('.$attemptobj->get_cmid.');
          });</script>';

}
  /**
   * Outputs the form for making an attempt
   *
   * @param quiz_attempt $attemptobj
   * @param int $page Current page number
   * @param array $slots Array of integers relating to questions
   * @param int $id ID of the attempt
   * @param int $nextpage Next page number
   */
  /* change the processattempt_url */
  public function tcq_attempt_form($attemptobj, $page, $slots, $id, $sessionid, $sesskey, $time_left_for_question) {

      global $CFG;

      $process_url = new moodle_url(new moodle_url('/mod/quiz/accessrule/tcquiz/processattempt.php'),['cmid' => $attemptobj->get_cmid(),'attemptid'=>$attemptobj->get_attemptid(),'sessionid'=>$sessionid]);

      $output = '';
      $output .= html_writer::start_tag('form',
                                ['action' => $process_url,
                                       'method' => 'post',
                                        'enctype' => 'multipart/form-data', 'accept-charset' => 'utf-8',
                                        'id' => 'responseform']);
      $output .= html_writer::start_tag('div');

      // Print all the questions.

      //Are there any active questions? Assuming each page, not question has a submit button and all questions on the page are in the same state
      $active_questions = false;

      foreach ($slots as $slot) {
          if ($attemptobj->get_question_state($slot)->is_active()){

            $active_questions = true;
          }
          $output .= $attemptobj->render_question($slot, false, $this, $attemptobj->attempt_url($slot, $page));
      }


      if ($active_questions){
              $output .= html_writer::empty_tag('input', ['type' => 'submit', 'name' => 'responseformsubmit',
              'value' => 'Submit', 'class' => 'mod_quiz-next-nav btn btn-primary', 'id' => 'responseformsubmit',
              'formaction' => $process_url]);
            }

     $output .= html_writer::start_tag('div');
     $output .= html_writer::end_tag('div');

      // Some hidden fields to track what is going on
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'attempt',
              'value' => $attemptobj->get_attemptid()]);
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'thispage',
              'value' => $page, 'id' => 'followingpage']);
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'nextpage',
              'value' => $nextpage]);
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'timeup',
              'value' => '0', 'id' => 'timeup']);
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey',
              'value' => sesskey()]);
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'mdlscrollto',
              'value' => '', 'id' => 'mdlscrollto']);

      // Add a hidden field with questionids. Do this at the end of the form, so
      // if you navigate before the form has finished loading, it does not wipe all
      // the student's answers.
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'slots',
              'value' => implode(',', $attemptobj->get_active_slots($page))]);

      // Finish the form.
      $output .= html_writer::end_tag('div');
      $output .= html_writer::end_tag('form');

      //Likely not needed
      $output .= html_writer::start_tag('p',['id' => 'status']);
      //$output .= "STATUS: ";
      $output .= html_writer::end_tag('p');

      $output .= html_writer::start_tag('p');
      $output .= "Time left: ";
      $output .= html_writer::start_tag('span',['id' => 'timeleft']);
      $output .= "0";
      $output .= html_writer::end_tag('span');
      $output .= html_writer::end_tag('p');

      $output .= '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/quiz/accessrule/tcquiz/locallib.js"></script>';
      $output .= '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/quiz/accessrule/tcquiz/view_student.js"></script>';

      $output .= $this->tcq_transfer_parameters_to_js($attemptobj, $page, $slots, $id, $sessionid, $sesskey);
      $output .= '<script type="text/javascript">window.addEventListener("load", function(){ tcquiz_start_timer('.$time_left_for_question.', false);}); </script>';

      $output .= $this->connection_warning();

      if (!$active_questions){
        $output .= "<h3> Question done - waiting for results. <h3><br />";

      }

      return $output;
  }



  /**
   * Attempt Page
   *
   * @param quiz_attempt $attemptobj Instance of quiz_attempt
   * @param int $page Current page number
   * @param access_manager $accessmanager Instance of access_manager
   * @param array $messages An array of messages
   * @param array $slots Contains an array of integers that relate to questions
   * @param int $id The ID of an attempt
   * @param int $nextpage The number of the next page
   * @return string HTML to output.
   */
  public function tcq_attempt_page($attemptobj, $page, $accessmanager, $messages, $slots, $id, $sessionid, $sesskey, $time_left_for_question) {
      $output = '';
      $output .= $this->header();
    //  $output .= $this->during_attempt_tertiary_nav($attemptobj->view_url());
    //  $output .= $this->quiz_notices($messages);
    //  $output .= $this->countdown_timer($attemptobj, time());
      $output .= $this->tcq_attempt_form($attemptobj, $page, $slots, $id, $sessionid,$sesskey, $time_left_for_question);
      //$output .= $this->tcq_teacher_controls_and_js();
      $output .= $this->footer();
      return $output;
  }

  /**
   * Builds the review page
   *
   * @param quiz_attempt $attemptobj an instance of quiz_attempt.
   * @param array $slots of slots to be displayed.
   * @param int $page the current page number
   * @param bool $showall whether to show entire attempt on one page.
   * @param bool $lastpage if true the current page is the last page.
   * @param display_options $displayoptions instance of display_options.
   * @param array $summarydata contains all table data
   * @return string HTML to display.
   */
  public function tcq_review_page(quiz_attempt $attemptobj, $slots, $page, $showall,
          $lastpage, display_options $displayoptions, $summarydata , $sessionid, $sesskey) {

      global $CFG;

      $output = '';
      $output .= $this->header();
      //$output .= $this->review_summary_table($summarydata, $page);
      $displayoptions = $attemptobj -> get_display_options(true);
      //var_dump($displayoptions);
      if ($attemptobj->is_preview_user()){
        $output .= $this->tcq_teacher_review_form($attemptobj, $page, $slots, $id, $sessionid,$sesskey);
        /*$output .= $this->tcq_review_form($page, $showall, $displayoptions,
                $this->questions($attemptobj, true, $slots, $page, $showall, $displayoptions),
                $attemptobj);*/

      }
      else{
        $output .= $this->tcq_review_form($page, $showall, $displayoptions,
                $this->questions($attemptobj, true, $slots, $page, $showall, $displayoptions),
                $attemptobj);
      }
      //$output .= $this->review_next_navigation($attemptobj, $page, $lastpage, $showall);
      $output .= '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/quiz/accessrule/tcquiz/locallib.js"></script>';
      $output .= '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/quiz/accessrule/tcquiz/view_student.js"></script>';
      $output .= '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/quiz/accessrule/tcquiz/view_teacher.js"></script>';
      $output .= $this->tcq_transfer_parameters_to_js($attemptobj, $page, $slots, $attemptobj->get_quizid(), $sessionid, $sesskey);




      if ($attemptobj->is_preview_user()){

      /*  if ($attemptobj->is_last_page($page)){

          $output .= '<script type="text/javascript"> window.addEventListener("load", function(){ tcquiz_get_final_results();}); </script>';
          //$output .= "<h1>Final results </h1>";
        }
        else{
          //localib js loaded twice?
          $output .= $this->tcq_teacher_controls_and_js();
        }*/
        $output .=  $this->tcq_teacher_controls_and_js();
      }
      else{
        $output .= '<script type="text/javascript"> window.addEventListener("load", function(){ tcquiz_get_student_question();}); </script>';
      }

      $output .= $this->footer();
      return $output;
  }



  /**
   * Renders the main bit of the review page.
   *
   * @param int $page current page number
   * @param bool $showall if true display attempt on one page
   * @param display_options $displayoptions instance of display_options
   * @param string $content the rendered display of each question
   * @param quiz_attempt $attemptobj instance of quiz_attempt
   * @return string HTML to display.
   */
  public function tcq_review_form($page, $showall, $displayoptions, $content, $attemptobj) {
      if ($displayoptions->flags != question_display_options::EDITABLE) {
          return $content;
      }


      //$this->page->requires->js_init_call('M.mod_quiz.init_review_form', null, false,
      //        quiz_get_js_module());

      $output = '';
      $output .= html_writer::start_tag('form', ['action' => $attemptobj->review_url(null,
              $page, $showall), 'method' => 'post', 'class' => 'questionflagsaveform']);
      $output .= html_writer::start_tag('div');
      $output .= $content;
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey',
              'value' => sesskey()]);
      $output .= html_writer::start_tag('div', ['class' => 'submitbtns']);
      $output .= html_writer::empty_tag('input', ['type' => 'submit',
              'class' => 'questionflagsavebutton btn btn-secondary', 'name' => 'savingflags',
              'value' => get_string('saveflags', 'question')]);
      $output .= html_writer::end_tag('div');
      $output .= html_writer::end_tag('div');
      $output .= html_writer::end_tag('form');

      return $output;
  }

  public function tcq_teacher_review_form($attemptobj, $page, $slots, $id, $sessionid,$sesskey) {
      global $CFG;


    //  $this->page->requires->js_init_call('M.mod_quiz.init_review_form', null, false,
      //        quiz_get_js_module());


      $output = '';


      $output .= html_writer::start_tag('form',
                                       ['action' => "",
                                       'method' => 'post',
                                        'enctype' => 'multipart/form-data', 'accept-charset' => 'utf-8',
                                        'id' => 'responseform']);
      $output .= html_writer::start_tag('div');

      // Print all the questions.

      $file = $CFG->dirroot . '/mod/quiz/accessrule/tcquiz/report/statistics/report.php';
      if (is_readable($file)) {
          include_once($file);
      }
      $reportclassname = 'tcquiz_statistics_report';
      if (!class_exists($reportclassname)) {
          throw new \moodle_exception('preprocesserror', 'quiz');
      }

      $report = new $reportclassname();

      foreach ($slots as $slot) {

          $output .= $attemptobj->render_question($slot, false, $this, $attemptobj->attempt_url($slot, $page));

            $output .= html_writer::start_tag('div',['class' => 'questionresults']);

            $tmp_str = $report->tcq_display_question_stats($attemptobj->get_quiz(), $sessionid, $slot, $attemptobj->get_cm(), $attemptobj->get_course());
            $output .= $tmp_str;
      }
      $cmid = optional_param('cmid', 0, PARAM_INT);
      $quizid = optional_param('quizid', 0, PARAM_INT);


      $output .= html_writer::end_tag('div');

      // Some hidden fields to track what is going on.
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'attempt',
              'value' => $attemptobj->get_attemptid()]);
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'thispage',
              'value' => $page, 'id' => 'followingpage']);
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'nextpage',
              'value' => $nextpage]);
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'timeup',
              'value' => '0', 'id' => 'timeup']);
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey',
              'value' => sesskey()]);
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'mdlscrollto',
              'value' => '', 'id' => 'mdlscrollto']);

      // Add a hidden field with questionids. Do this at the end of the form, so
      // if you navigate before the form has finished loading, it does not wipe all
      // the student's answers.
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'slots',
              'value' => implode(',', $attemptobj->get_active_slots($page))]);

      // Finish the form.

      $output .= html_writer::end_tag('form');

      return $output;
  }

    public function tcq_teacher_controls_and_js(){

      global $CFG;
      $output = '';
      $output .= '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/quiz/accessrule/tcquiz/locallib.js"></script>';
      $output .= '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/quiz/accessrule/tcquiz/view_teacher.js"></script>';

      $output .= html_writer::start_tag('div', ['id' => 'teachercontrols', 'style'=> 'background-color: #e7f3f5;' ,'class' => 'que formulation clearfix']);

      $output .= html_writer::start_tag('p');
      $output .= "Number of received answers: ";
      $output .= html_writer::start_tag('span',['id' => 'numanswers' ]);
      $output .= "0";
      $output .= html_writer::end_tag('span');
      $output .= html_writer::end_tag('p');

      $output .= html_writer::start_tag('p');
      $output .= "Time left: ";
      $output .= html_writer::start_tag('span',['id' => 'timeleft']);
      $output .= "0";
      $output .= html_writer::end_tag('span');
      $output .= html_writer::end_tag('p');

      $output .= html_writer::start_tag('p');
      $output .= html_writer::empty_tag('input',['type' => 'button', 'class' => 'btn btn-secondary', 'value' => 'End question', 'id' => 'endquestion', 'onclick' => 'end_button_action();']);
      $output .= html_writer::end_tag('p');

      $output .= html_writer::start_tag('p');
      $output .= html_writer::empty_tag('input',['type' => 'button', 'class' => 'btn btn-secondary', 'value' => 'Next >>', 'id' => 'nextbutton', 'onclick' => 'run_next_quiz_question();']);
      $output .= html_writer::end_tag('p');

      $output .= html_writer::end_tag('div');

      return $output;
    }

    public function tcq_teacher_attempt_page($attemptobj, $page, $accessmanager, $messages, $slots, $id, $sessionid, $sesskey, $time_left_for_question) {

      global $CFG;
      $output = '';
      $output .= $this->header();


      $process_url = new moodle_url(new moodle_url('/mod/quiz/accessrule/tcquiz/processattempt.php'),['cmid' => $attemptobj->get_cmid(),'attemptid'=>$attemptobj->get_attemptid(),'sessionid'=>$sessionid]);

      $output .= html_writer::start_tag('form',
                                      ['action' => $process_url,
                                       'method' => 'post',
                                       'enctype' => 'multipart/form-data', 'accept-charset' => 'utf-8',
                                       'id' => 'responseform']);
      $output .= html_writer::start_tag('div');

      // Print all the questions.

      foreach ($slots as $slot) {

          $output .= $attemptobj->render_question($slot, false, $this,
                  $attemptobj->attempt_url($slot, $page));
      }

      $output .= html_writer::end_tag('div');


            // no submit button for the teacher
          /* $output .= html_writer::empty_tag('input', ['type' => 'submit', 'name' => 'responseformsubmit',
              'value' => 'Submit', 'class' => 'mod_quiz-next-nav btn btn-primary', 'id' => 'responseformsubmit',
              'formaction' => $process_url]);*/

      // Some hidden fields to track what is going on.
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'attempt',
              'value' => $attemptobj->get_attemptid()]);
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'thispage',
              'value' => $page, 'id' => 'followingpage']);
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'nextpage',
              'value' => $nextpage]);
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'timeup',
              'value' => '0', 'id' => 'timeup']);
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey',
              'value' => sesskey()]);
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'mdlscrollto',
              'value' => '', 'id' => 'mdlscrollto']);

      // Add a hidden field with questionids. Do this at the end of the form, so
      // if you navigate before the form has finished loading, it does not wipe all
      // the student's answers.
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'slots',
              'value' => implode(',', $attemptobj->get_active_slots($page))]);

      // Finish the form.

      $output .= html_writer::end_tag('form');

      $output .= html_writer::start_tag('p',['id' => 'status']);
      $output .= html_writer::end_tag('p');

      $output .= $this->tcq_transfer_parameters_to_js($attemptobj, $page, $slots, $id, $sessionid, $sesskey);
      $output .= $this->tcq_teacher_controls_and_js();

      $output .= '<script type="text/javascript">window.addEventListener("load", function(){ tcquiz_start_timer('.$time_left_for_question.', false);}); </script>';

      $output .= $this->connection_warning();

      $output .= $this->footer();
      return $output;
    }

    /**
     * Renders each question - there is an issue (flag in the question appears not clickable, but it is (seems the right css class). CSS not loaded?
     *
     * @param quiz_attempt $attemptobj instance of quiz_attempt
     * @param bool $reviewing
     * @param array $slots array of integers relating to questions
     * @param int $page current page number
     * @param bool $showall if true shows attempt on single page
     * @param display_options $displayoptions instance of display_options
     */
  /*  public function tcq_questions(quiz_attempt $attemptobj, $reviewing, $slots, $page, $showall,
            display_options $displayoptions) {
        $output = '';
        foreach ($slots as $slot) {
            //$this would not work for renderer
            $output .= $attemptobj->tcq_render_question($slot, $reviewing, $PAGE->get_renderer('quizaccess_tcquiz'),$displayoptions,
                    $attemptobj->review_url($slot, $page, $showall));
        }
        return $output;
    }*/

}
