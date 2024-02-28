<?php

use mod_quiz\quiz_settings;

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/tcquiz/locallib.php');
//for constants only
require_once($CFG->dirroot . '/mod/quiz/classes/quiz_attempt.php');

global $DB;

$sessionid = required_param('id', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$quizobj = quiz_settings::create_for_cmid($cmid, $USER->id);

if ($quizobj->is_preview_user()){
    $session = $DB->get_record('quizaccess_tcquiz_session', array('id' => $sessionid));
    $session->status = TCQUIZ_STATUS_FINISHED;
    $DB->update_record('quizaccess_tcquiz_session', $session); // FIXME - not update all fields?


    // finish all attempts associated with this quiz
    /*$sql = "SELECT qta.attemptid FROM {quizaccess_tcquiz_attempt} qta WHERE sessionid = :sessionid";

    $attemptids = $DB->get_records_sql($sql, ['sessionid' => $sessionid]);
    foreach($attemptids as $attemptid){
      echo intval($attemptid->attemptid);
      echo " ";
    }
*/

    $sql = "SELECT * FROM {quizaccess_tcquiz_attempt} qta WHERE sessionid = :sessionid";

    //echo "AAAAAAAAAA";
    //echo $sessionid."   ";
    $attemptids = $DB->get_records_sql($sql, ['sessionid' => $sessionid]);
    //var_dump($attemptids);
    foreach($attemptids as $attemptid){
      //echo "BBBBBBBBB";
      //echo intval($attemptid->attemptid);
      //echo " ";
      //echo "BBBBBBBBB";
      $attempt = $DB->get_record('quiz_attempts', array('id' => intval($attemptid->attemptid)));
      if ( $attempt && $attempt->state != quiz_attempt::FINISHED){
        //var_dump($attempt);
        //echo "AAAAAAAAAA";
        //echo intval($attemptid->attemptid);
        //echo "AAAAAAAAAA";
        //var_dump($DB->get_record('quiz_attempts', array('id' => intval($attemptid->attemptid))));
        //echo "AAAAAAAAAA";
        //$attempt->state = "finished";
        $attempt->state=quiz_attempt::FINISHED;
        $DB->update_record('quiz_attempts', $attempt); // FIXME - not update all fields or or add timestamps? Submit the attempts?
      }

    }
    redirect(new moodle_url('/mod/quiz/view.php',['id' => $cmid ]));

    die();
}
