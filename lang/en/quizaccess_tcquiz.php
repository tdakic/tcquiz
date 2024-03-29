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

$string['tcquizsettings'] = 'TCQuiz settings';
$string['questiontime'] = 'Default question time';
$string['questiontime_help'] = 'The default time (in seconds) to display each question.';
$string['tcquizrequired'] = 'Administer TCQuiz';
$string['tcquizrequired_help'] = 'If enabled, the teacher will have the control of which questions the students can answer at any given time. Primarily meant for in class polling.';
$string['yes'] = 'Yes';
$string['no'] = 'No';

//teacher startpage
$string['teacherjoinquizinstruct'] = 'Use this if you want to try out a quiz yourself<br />(you will also need to start/reconnect to the quiz in a separate window).';
$string['teacherstartinstruct'] = 'Use this to start a quiz for the students to take<br />Use the textbox to define a name for this session (to help when looking through the results at a later date).';
$string['teacherstartnewinstruct'] = 'Start a new quiz session. If there is a <b> Join </b> button visible above, this will close the session that is already running. Be very careful, as this will close any open student attempts associated with this quiz. If in doubt, if available above, try reconnecting to the existing session.';
$string['availablesessionsdesc'] = 'If there is an available session listed above, you can try clicking on <b> Rejoin </b> to reconnect to it. This is primarily meant for reconnecting if your session crashed during the quiz administration. If you click on <b> End </b>, this will end the session and finish all the student attempts associated with it';
$string['startquiz'] = 'Start quiz';
$string['startnewquiz'] = 'Start new quiz session';
$string['startnewquizconfirm'] = 'Are you absolutely sure you want to abandon the currently running quiz session and start a new one?';
$string['studentconnected'] = 'student connected';
$string['studentsconnected'] = 'students connected';
$string['clicknext'] = 'Click \'Next\' when everyone is ready';
$string['next'] = 'Next >>';
$string['reconnectquiz'] = 'Reconnect to quiz';
$string['reconnectinstruct'] = 'The quiz is already running - you can connect to this session and take control of it.';
$string['waitstudent'] = 'Waiting for students to connect';
$string['jointcquiz'] = 'Join quiz';
$string['joinquizasstudent'] = 'Join quiz as a student';


$string['question'] = 'Question ';
$string['timeleft'] = 'Time left to answer:';

$string['finalresults'] = 'Final results';



$string['removeimage'] = 'Remove image';



$string['addanswers'] = 'Add space for 3 more answers';
$string['addquestion'] = 'Add question';
$string['addingquestion'] = 'Adding question ';
$string['allquestions'] = 'Back to full results';
$string['allsessions'] = 'All Sessions';
$string['alreadyanswered'] = 'You have already answered this question';
$string['answer'] = 'Answer ';
$string['answers'] = 'Answers';
$string['answersent'] = 'Answer sent - waiting for question to finish: ';
$string['answertext'] = 'Answer text:';
$string['awaittime'] = 'AJAX waiting time';
$string['awaittimedesc'] = 'The setting defines the frequency in seconds to check question and result data by student browser window. When high number of quiz participants causes server slowness, this number should be increased to reduce the number of simultanious data calls. Minimum value is 1 second.';
$string['awaittimeerror'] = 'The value should not be less than 1';
$string['atleastoneanswer'] = 'You need at least one answer';
$string['backquiz'] = 'Back to the TCQuiz';
$string['backresponses'] = 'Back to the full results';
$string['badcurrentquestion'] = 'Bad currentquestion: '; // Do not translate 'currentquestion'.
$string['badquizid'] = 'Bad quizid: '; // Do not translate 'quizid'.
$string['badresponse'] = 'Unexpected response from server - ';
$string['badsesskey'] = 'Bad session key';
$string['checkdelete'] = 'Are you sure you want to delete this question?';
$string['choosecorrect'] = 'Set this as the correct answer';
$string['choosesession'] = 'Choose a session to display: ';
$string['classresult'] = 'Class result: ';
$string['classresultcorrect'] = ' correct';

$string['correct'] = 'Correct answer?';
$string['correctnotblank'] = 'Correct answer cannot be blank';
$string['cross'] = 'Wrong answer';
$string['deletequestion'] = 'Delete question';
$string['displaynext'] = 'About to display next question:';
$string['edit'] = 'Edit quiz';
$string['editquestions'] = 'Edit the questions';
$string['editquestiontime'] = 'Question time (0 for default)';
$string['edittingquestion'] = 'Editing question ';
$string['errorquestiontext'] = 'Error: You have not filled in the question';
$string['eventeditpageviewed'] = 'TCQuiz edit page viewed';
$string['eventresponsesviewed'] = 'TCQuiz responses viewed';

$string['hideusers'] = 'Hide users';
$string['httperror'] = 'There was a problem with the request - status: ';
$string['httprequestfail'] = 'Giving up :( Cannot create an XMLHTTP instance';
$string['incorrectstatus'] = 'Quiz has incorrect status: \'';
$string['invalidanswer'] = 'Invalid answer number ';
$string['joininstruct'] = 'Wait until your teacher tells you before you click on this';

$string['modulename'] = 'TCQuiz';
$string['modulenameplural'] = 'TCQuizzes';

$string['nextquestion'] = 'Next question';
$string['noanswers'] = 'No answers given';
$string['nocorrect'] = 'No \'right\' answer';
$string['noquestion'] = 'Bad response - no question data: ';
$string['nosessions'] = 'This TCQuiz has not yet been attempted';
$string['notallowedattempt'] = 'You are not allowed to attempt this quiz';
$string['notauthorised'] = 'You are not authorised to control this quiz';
$string['onecorrect'] = 'Error: There must be exactly one correct answer';
$string['pluginadministration'] = 'TCQuiz administration';
$string['pluginname'] = 'TCQuiz';
$string['prevquestion'] = 'Previous question';
$string['privacy:metadata:tcquiz_submitted'] = 'Details of an answer given to a TCQuiz question';
$string['privacy:metadata:tcquiz_submitted:answerid'] = 'The ID of the answer that was selected';
$string['privacy:metadata:tcquiz_submitted:questionid'] = 'The ID of the question that has been answered';
$string['privacy:metadata:tcquiz_submitted:sessionid'] = 'The ID of the session the answer was given in';
$string['privacy:metadata:tcquiz_submitted:userid'] = 'The ID of the user that gave the answer';
$string['questionimage'] = '(Optional) image: ';

$string['questiondelete'] = 'Delete question {$a}';
$string['questionfinished'] = 'Question finished, waiting for results';
$string['questionmovedown'] = 'Move question {$a} down';
$string['questionmoveup'] = 'Move question {$a} up';
$string['questions'] = 'Questions';
$string['questionslist'] = 'Questions in this TCQuiz: ';
$string['questiontext'] = 'Question text:';


$string['quizfinished'] = 'No more questions';
$string['quiznotrunning'] = 'Quiz not running at the moment - wait for your teacher to start it';
$string['tcquiz:addinstance'] = 'Add a new TCQuiz';
$string['tcquiz:attempt'] = 'Attempt a quiz';
$string['tcquiz:control'] = 'Start / control a quiz';
$string['tcquiz:seeresponses'] = 'View the responses to a quiz';
$string['tcquiz:editquestions'] = 'Edit the questions for a quiz';
$string['tcquizintro'] = 'Introduction';


$string['responses'] = 'View responses';
$string['resultcorrect'] = ' correct.';
$string['resultoverall'] = ' correct. Overall: ';
$string['resultthisquestion'] = 'This question: ';
$string['saveadd'] = 'Save question and add another';
$string['scorestable'] = 'Scores table';
$string['seeresponses'] = 'View the responses';
$string['sendinganswer'] = 'Sending answer';
$string['servererror'] = 'Server returned error: ';
$string['sessions'] = 'Sessions';
$string['showsession'] = 'Show';
$string['showusers'] = 'Show users';

$string['submissions'] = 'Submissions';
$string['tick'] = 'Correct answer';
$string['timeleft'] = 'Time left to answer:';
$string['totals'] = 'Running total';
$string['tryagain'] = 'Do you want to try again?';
$string['unknownrequest'] = 'Unknown request: \'';
$string['updatequestion'] = 'Save question';
$string['view'] = 'View quiz';
$string['waitfirst'] = 'Waiting for the first question to be sent';

$string['yourresult'] = 'Your result: ';

$string['nottcquiz'] = 'This quiz is not set up as a TCQuiz ';
$string['notcurrentpage'] = 'You tried to access the page of the quiz that is not the page currently being displayed by the teacher.';
$string['nosession'] = 'The requested session of TCQuiz doesn not exist';
$string['notrightquizstate'] = 'The quiz is currently in a different state.';
