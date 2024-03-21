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
 * Implementaton of the quizaccess_tcquiz plugin.
 *
 * @package   quizaccess_tcquiz
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


 //TTT
 use mod_quiz\quiz_settings;


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
 *
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_tcquiz extends quizaccess_tcquiz_parent_class_alias {

    public function is_preflight_check_required($attemptid) {
        //return empty($attemptid);
        return false;
    }

    public function add_preflight_check_form_fields(quizaccess_tcquiz_preflight_form_alias $quizform,
            MoodleQuickForm $mform, $attemptid) {



      /*  $mform->addElement('header', 'tcquizheader',
                get_string('tcquizheader', 'quizaccess_tcquiz'));
        $mform->addElement('static', 'tcquizmessage', '',
                get_string('tcquizstatement', 'quizaccess_tcquiz'));
        $mform->addElement('checkbox', 'tcquiz', '',
                get_string('tcquizlabel', 'quizaccess_tcquiz'));*/
    }

    public function validate_preflight_check($data, $files, $errors, $attemptid) {
        /* TTT help required
        if (empty($data['tcquiz'])) {
            $errors['tcquiz'] = "Error";
        }

        return $errors;*/
    }

    public static function make(quizaccess_tcquiz_quiz_settings_class_alias $quizobj, $timenow, $canignoretimelimits) {

        // TTT fix needed
        if (empty($quizobj->get_quiz()->tcquizrequired)) {
            return null;
        }

        return new self($quizobj, $timenow);
    }

    public static function add_settings_form_fields(mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {

      $mform->addElement('header', 'tcquizheader', get_string('tcquizsettings', 'quizaccess_tcquiz'));


      $mform->addElement('select', 'tcquizrequired',
                get_string('tcquizrequired', 'quizaccess_tcquiz'),
                array(
                    0 => get_string('no', 'quizaccess_tcquiz'),
                    1 => get_string('yes', 'quizaccess_tcquiz'),
                ));
      $mform->addHelpButton('tcquizrequired','tcquizrequired', 'quizaccess_tcquiz');

      $mform->addElement('text', 'questiontime', get_string('questiontime', 'quizaccess_tcquiz'));
      $mform->addRule('questiontime', null, 'numeric', null, 'client');
      $mform->setDefault('questiontime', 30);
      $mform->setType('questiontime', PARAM_INT);
      $mform->addHelpButton('questiontime', 'questiontime', 'quizaccess_tcquiz');
    }

    public static function save_settings($quiz) {
        global $DB;
        //TTT
        if (empty($quiz->tcquizrequired)) {
            $DB->delete_records('quizaccess_tcquiz', array('quizid' => $quiz->id));
        } else {
            if (!$DB->record_exists('quizaccess_tcquiz', array('quizid' => $quiz->id))) {
                $record = new stdClass();
                $record->quizid = $quiz->id;
                //$record->tcquizrequired = 1;
                $record->tcquizrequired = $quiz->tcquizrequired;
                $record->questiontime = $quiz->questiontime;
                $DB->insert_record('quizaccess_tcquiz', $record);
            }
            else {
                $tcquiz = $DB->get_record('quizaccess_tcquiz', array('quizid' => $quiz->id));
                $tcquiz->tcquizrequired = $quiz->tcquizrequired;
                $tcquiz->questiontime = $quiz->questiontime;
                $DB->update_record('quizaccess_tcquiz', $tcquiz);
            }
        }
    }

    public static function delete_settings($quiz) {
        global $DB;
        $DB->delete_records('quizaccess_tcquiz', array('quizid' => $quiz->id));
    }

  /*  public static function get_settings_sql($quizid) {
        return array(
            'tcquiz.questiontime as tcquiz_questiontime, '
          .  'tcquiz.tcquizrequired as tcquiz_tcquizrequired'
          ,  'LEFT JOIN {quizaccess_tcquiz} tcquiz ON tcquiz.quizid = quiz.id'
          ,  array());
    }*/

    public static function get_settings_sql($quizid) {
          return array(
              'tcquizrequired, questiontime',
              'LEFT JOIN {quizaccess_tcquiz} tcquiz ON tcquiz.quizid = quiz.id',
              array());
    }

/**
 * Sets up the attempt (review or summary) page with any special extra
 * properties required by this rule.
 *
 * @param moodle_page $page the page object to initialise.
 */
//doesn't do Anything
/*public function setup_attempt_page($page) {
    $page->set_title($this->quizobj->get_course()->shortname . ': ' . $page->title);
    $page->set_popup_notification_allowed(false); // Prevent message notifications.
    $page->set_heading($page->title);
    $page->set_pagelayout('secure');
}*/


    /**
     * Information, such as might be shown on the quiz view page, relating to this restriction.
     * There is no obligation to return anything. If it is not appropriate to tell students
     * about this rule, then just return ''.
     *
     * @return mixed a message, or array of messages, explaining the restriction
     *         (may be '' if no message is appropriate).
     */
    public function description() : array {
        global $PAGE; // not needed ???
        global $USER;
        global $CFG;
        global $DB;
        global $OUTPUT;

        $quizobj = quiz_settings::create_for_cmid($this->quiz->cmid, $USER->id);
        $context = $quizobj->get_context();

        if (!$quizobj->has_questions()){
          $messages[] ='This quiz is configured as a TCQuiz';
        }
        else {
            if (has_capability('mod/quiz:preview', $context)){

              if (!$data = self::get_open_session($context)){
                $existing_session = false;
              }
              else {
                $existing_session = true;
              }
              $messages[] = $OUTPUT->render_from_template('quizaccess_tcquiz/start_tcq', ['sessionid'=>$data['sessionid'], 'joincode'=>$data['joincode'],
                'timestamp'=>$data['timestamp'],'currentpage'=>$data['currentpage'], 'status'=> $data['status'], 'attemptid'=>$data['attemptid'],
                'existingsession'=>$existing_session,'quizid'=>$this->quiz->id, 'cmid'=>$this->quiz->cmid]);
            }
            else {
              $existing_session = false; // get rid of this
              $messages[] = $OUTPUT->render_from_template('quizaccess_tcquiz/student_join_tcq', ['sessionid'=>$data['sessionid'], 'joincode'=>$data['joincode'],
                'timestamp'=>$data['timestamp'],'currentpage'=>$data['currentpage'], 'existingsession'=>$existing_session,
                'quizid'=>$this->quiz->id, 'cmid'=>$this->quiz->cmid]);
            }
      }
      return $messages;
    }

 private function get_open_sessions($context)  : array {
    global $DB;
    global $USER;
    //display a table of all sessions that are in progress (status between 10-40 inclusive)

    $sql = "SELECT * FROM {quizaccess_tcquiz_session} WHERE quizid = :quizid AND status BETWEEN 10 and 40 ORDER BY timestamp DESC";

    $messages[]='';
    //if (has_capability('mod/quiz:preview', $context) && $sessions = $DB->get_records("quizaccess_tcquiz_session", array('quizid' => $this->quiz->id,'status>=10'))) {
      if (has_capability('mod/quiz:preview', $context) && $sessions =  $DB->get_records_sql($sql, ['quizid' => $this->quiz->id])){

      $header_added =false;

      foreach($sessions as $session){
        $jc = "'".$session->joincode."'";

        $sql = "SELECT qa.id FROM {quiz_attempts} qa
                        LEFT JOIN {quizaccess_tcquiz_attempt} qta ON qa.id = qta.attemptid
                        WHERE qta.sessionid =:sessionid AND qa.userid = :userid";

        $attemptid = $DB->get_record_sql($sql, array('sessionid'=>$session->id, 'userid'=>$USER->id));
        //$messages[] = $session->joincode."    ".$session->id."    ".$USER->id."  ";
        if ($attemptid){
          if (!$header_added){
            $messages[] = '<div id="availablesessions">';
            $messages[] ='<h2>Available sessions</h2>';
            $messages[] ='<table class="flexible table table-striped table-hover generaltable generalbox">';
            $messages[] ='<tr><th>Code</th><th>Time started</th><th>Current question</th><th></th><th></th></tr>';
            $header_added = true;
          }
          $messages[] = '<tr><td>'.$session->joincode.'</td><td>'.date('m/d/Y H:i:s',$session->timestamp).'</td><td>'.$session->currentquestion.
          '</td><td><button type="button" class="btn btn-secondary" onclick="tcquiz_teacher_rejoin('.$jc.','.$session->id.','.$session->status.','.$attemptid->id.');">Rejoin</button></td>
          <td><button type="button" class="btn btn-secondary" onclick="tcquiz_end_session('.$session->id.')">End</button></td></tr>';
        }
      }
      if ($header_added){
        $messages[] ='</table></div><br /><br />';
      }
    }
    return $messages;
  }

  private function get_open_session($context)  : array {
     global $DB;
     global $USER;

        // check to see if an open attempt of the teacher is a tcquiz attempt
        $sql = "SELECT * FROM {quizaccess_tcquiz_attempt} qta
                        LEFT JOIN {quiz_attempts} qa ON qta.attemptid = qa.id
                        WHERE qa.state = 'inprogress' AND qa.quiz = :quizid AND qa.userid = :uid";

        //$sess = $DB->get_record_sql($sql, ['quizid' => $this->quiz->id]);

        if (!$attempt = $DB->get_record_sql($sql, ['quizid' => $this->quiz->id, 'uid' => $USER->id])){
          return [];
        }
        else{
          //get the session assocaited with the teacher's attempt and return its data
          //Should be vetween 10 and 40 !!!!!!!!!!
          $sql = "SELECT * FROM {quizaccess_tcquiz_session} WHERE id = :sessid AND status BETWEEN 10 and 40";
          if (!$sess = $DB->get_record_sql($sql, ['sessid' => $attempt ->sessionid])){
            return [];
          }
          else {
            return array('sessionid'=>$sess->id, 'joincode'=>$sess->joincode, 'timestamp'=>date('m/d/Y H:i:s',$sess->timestamp), 'currentpage'=>$sess->currentpage,
              'status'=>$sess->status,  'attemptid'=>$attempt->id);
          }
        }


   }

}
