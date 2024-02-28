<?php
require_once(__DIR__ . '/../../../../config.php');
global $DB;

$sessionid = required_param('sessionid', PARAM_INT);
$session = $DB->get_record('quizaccess_tcquiz_session', array('id' => $sessionid));
//add privilege checks??? what should be checked - using this at the wrong time?


//if the state of the quiz is different than TCQUIZ_STATUS_SHOWQUESTION =  20 or TCQUIZ_STATUS_PREVIEWQUESTION = 15 defined in locallib.php
/*if ($tcquizsession->status != 15  && $tcquizsession->status != 20){
  throw new moodle_exception('notrightquizstate', 'quizaccess_tcquiz', new moodle_url('/my/courses.php', []));

}*/
//$quizid = $session -> quizid;

echo $session->currentquestionstate;
