<?php

//namespace quizaccess_tcquiz;

require_once(__DIR__ . '/../../../../config.php');
global $CFG, $DB, $PAGE;


//$url = new moodle_url('/local/greetings/view.php', []);
//$PAGE->set_url($url);
//$PAGE->set_context(context_system::instance());

$PAGE->set_title($SITE->fullname);
//$PAGE->set_heading(get_string('pluginname', 'local_greetings'));
echo $OUTPUT->header();
//echo "START";
//echo $OUTPUT->render_from_template('quizaccess_tcquiz/hello', []);
$first = "WOODY";
$last = "YOU";
/*$PAGE->requires->js_call_amd(
    'quizaccess_tcquiz/helloworld',
    'init',
    [$first, $last]);*/
//$PAGE->requires->js_call_amd('quizaccess_tcquiz/greetings', 'init', array($this->quiz->cmid));

$m = new Mustache_Engine(array(
                'loader' => new Mustache_Loader_FilesystemLoader($CFG->dirroot .'/mod/quiz/accessrule/tcquiz')
             ));
//$engine = new \Mustache_Engine();
//$tpl = $m->loadTemplate('Hello, {{ name }}!');
//$tpl = $m->loadTemplate('templates/hello.mustache');
//echo $tpl(array('first' => 'Worldd', 'last' => "WOODY"));
//https://stackoverflow.com/questions/9301873/accessing-value-of-iterated-field-in-mustache-php

echo "Starting the template";
$simple_data = array('value1','value2','value3');
$complex_data = array(array('id'=>'1','name'=>'Jane'),array('id'=>'2','name'=>'Fred') );

$template_data['simple'] = $simple_data;
$template_data['complex'] = new ArrayIterator( $complex_data );
var_dump($template_data);
//$m->render('templates/test', json_encode($template_data) );//echo "BYE";
echo $OUTPUT->render_from_template('quizaccess_tcquiz/test', ['ttt'=>json_encode($template_data),'first'=>$template_data]);
echo "Ending the template";
echo $OUTPUT->footer();
return;


$data=[1,2,3];

$data = $DB->get_record("quiz_attempts", array('id' => 1422));
//var_dump($data);
//$data1 = new stdClass();
//$data1->events = array_values($data);
$data1 = json_encode($data);

//var_dump(array_values($data));
var_dump($data1);
//$data = new stdClass();
//$data->events = array_values($eventrecords);
//$data=json_encode($data);
echo $OUTPUT->render_from_template('quizaccess_tcquiz/hello', ['first' => 'Worldd', 'last' => "WOODY",'opensess' => $data1]);
echo $OUTPUT->footer();
