<?php
require_once(__DIR__ . '/../../../../config.php');
global $DB;
//TTT add privilege checks

$quizid = $session -> quizid;

$sessionid = required_param('sessionid', PARAM_INT);
//$cmid = required_param('cmid', PARAM_INT);

$session = $DB->get_record('quizaccess_tcquiz_session', array('id' => $sessionid));
$session->currentquestionstate = 0;
$session->nextendtime = time();

$DB->update_record('quizaccess_tcquiz_session', $session);

echo "Success ".($session->nextendtime-time());
