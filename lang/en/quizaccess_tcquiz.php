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
 * English language strings
 *
 * @copyright Davo Smith <moodle@davosmith.co.uk>
 * @package quizacess_tcqquiz
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

defined('MOODLE_INTERNAL') || die();

$string['setting:pollinginterval']="Polling frequency";
$string['setting:pollinginterval_desc']="Specify how often (in ms) the polling should be performed. Smaller values could affect the server performance. Larger value could couse tcquiz performance issues.";
$string['pluginname'] = 'TCQuiz';
$string['modulename'] = 'TCQuiz';
$string['modulenameplural'] = 'TCQuizzes';

$string['tcquizsettings'] = 'TCQuiz settings';
$string['questiontime'] = 'Default question time';
$string['questiontime_help'] = 'The default time (in seconds) to display each question.';
$string['tcquizrequired'] = 'Administer TCQuiz';
$string['tcquizrequired_help'] = 'If enabled, the teacher will have the control of which questions the students can answer at any given time. Primarily meant for in class polling.';
$string['yes'] = 'Yes';
$string['no'] = 'No';

//used in the rule.php
$string['accesserror'] = 'Access to this quiz is controlled by the teacher.';
$string['configuredastcq'] ='This quiz is configured as a TCQuiz';

//teacher startpage
$string['availablesession'] = 'Available session';
$string['availablesessionsdesc'] = 'Click on <b> Rejoin </b> to reconnect to an already running session. This is primarily meant for reconnecting if your session crashed during the quiz administration. If you click on <b> End </b>, this will end the session and finish all the student attempts associated with it';
$string['teacherstartnewinstruct'] = 'Click on the <b> Start new quiz session </b> button to start a new quiz sesion. You must provide a code for the session that the students can use to connect.';
$string['teacherwarning']= 'If there is an <b> Available session </b> visible below, starting a new session will close that session. Be very careful, as this will close any open student attempts associated with this quiz. If in doubt and if available below, try reconnecting to the existing session.';
$string['cantstartquiz'] ='You cannot start this quiz';
$string['startnewquiz'] = 'Start new quiz session';
$string['joincode'] = 'Join code';
$string['timestarted'] = 'Time started';
$string['currentpage'] = 'Current page';
$string['rejoin'] = 'Rejoin';
$string['end'] = 'End';
$string['enterjoincode'] = 'Enter join code';
$string['joincodeemptyerror'] = 'Join code cannot be empty.';
$string['sessionexisterror'] = 'The session with the same name already exists.';
$string['wrongjoincode'] = 'Wrong join code. Try again.';
$string['quiznotrunning'] = 'Quiz not running at the moment - wait for your teacher to start it';


//wait_for_students page
$string['waitstudent'] = 'Waiting for students to connect';
$string['next'] = 'Next >>';
$string['numconnectedstudents'] = 'Number of connected students';
$string['clicknext'] = 'Click \'Next\' when everyone is ready';
$string['waitfirst'] = 'Waiting for the first question to be sent';

//teacher controls
$string['numanswers'] = 'Number of received answers';
$string['timeleft'] = 'Time left to answer:';
$string['endquestion'] = 'End question';

//student responseformsubmit
$string['jointcquiz'] = 'Join quiz';
$string['entercode'] = 'Enter code given by teacher';
$string['joininstruct'] = 'Wait until your teacher gives you the code.';

//error messages in js
$string['errorclosingsession'] = "Error closing the session. Try again, or start a new session.";
$string['errorrejoining'] = "Error rejoining the session. The session is not running.";

//for waitforquestion.js
$string['invalidserverresponse'] = "Invalid server response.";
$string['unknownserverresponse'] = "Unknown server response: ";


$string['teacherjoinquizinstruct'] = 'Use this if you want to try out a quiz yourself<br />(you will also need to start/reconnect to the quiz in a separate window).';
$string['teacherstartinstruct'] = 'Use this to start a quiz for the students to take<br />Use the textbox to define a name for this session (to help when looking through the results at a later date).';
$string['startquiz'] = 'Start quiz';
$string['studentconnected'] = 'student connected';
//$string['studentsconnected'] = 'students connected';
$string['startnewquizconfirm'] = 'Are you absolutely sure you want to abandon the currently running quiz session and start a new one?';

//for student attempts
$string['questiondonewaitforresults'] = "Question done - waiting for results.";
$string['reconnectquiz'] = 'Reconnect to quiz';
$string['reconnectinstruct'] = 'The quiz is already running - you can connect to this session and take control of it.';
$string['jointcquiz'] = 'Join quiz';
$string['joinquizasstudent'] = 'Join quiz as a student';

//for student final results page
$string['yourfinalscore'] = 'Your final score';
$string['yourscoreis'] = 'Your score is ';
$string['yourscorecanchange'] = ' Your score will be changed by your teacher if they did not ask all the questions in the quiz.';
$string['done'] = 'Done';

//for teacher final results page
$string['endquiz'] = 'End quiz';
$string['question'] = 'Question ';
$string['finalresults'] = 'Final results';
$string['nextquestion'] = 'Next question';
$string['noanswers'] = 'No answers given';

$string['notallowedattempt'] = 'You are not allowed to attempt this quiz';
$string['notauthorised'] = 'You are not authorised to control this quiz';

$string['pluginadministration'] = 'TCQuiz administration';

$string['nottcquiz'] = 'This quiz is not set up as a TCQuiz ';
$string['notcurrentpage'] = 'You tried to access the page of the quiz that is not the page currently being displayed by the teacher.';
$string['nosession'] = 'The requested session of TCQuiz doesn not exist';
$string['notrightquizstate'] = 'The quiz is currently in a different state.';
$string['notyoursession']= 'You are not the teacher for this session';
