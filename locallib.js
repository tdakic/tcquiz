var tcquiz = {};
tcquiz.givenanswer=false;
tcquiz.timeleft=-1;
tcquiz.timer=null;
//ttt
tcquiz.timerevent=null;
tcquiz.updatenumstudentsevent=null;
tcquiz.questionnumber=-1;
tcquiz.answernumber=-1;
tcquiz.questionxml=null;
tcquiz.controlquiz = false;
tcquiz.lastrequest = '';
tcquiz.sesskey=-1;
tcquiz.coursepage='';
tcquiz.siteroot='';
tcquiz.myscore=0;
tcquiz.myanswer=-1;
tcquiz.resendtimer = null;
tcquiz.resenddelay = 2000; // How long to wait before resending request
tcquiz.alreadyrunning = false;
tcquiz.questionviewinitialised = false;

tcquiz.markedquestions = 0;
// TTT
tcquiz.attemptid = -1;
tcquiz.cmid = -1;
tcquiz.response_xml_text ='';
tcquiz.questionsstarted = false;
tcquiz.totalquestions = 0;
tcquiz.shownquestions = 0; //This is var is not nesessary as it should be doing the same job as tcquiz.questionnumber

tcquiz.tcqsessionid = -1;
tcquiz.joincode='';
tcquiz.page = -1;


tcquiz.image = [];
tcquiz.text = [];


/**************************************************
 * Some values that need to be passed in to the javascript
 **************************************************/

function tcquiz_set_maxanswers(number) {
    tcquiz.maxanswers = number;
}

function tcquiz_set_quizid(id) {
    tcquiz.quizid = id;
}

function tcquiz_set_userid(id) {
    tcquiz.userid = id;
}

function tcquiz_set_sesskey(key) {
    tcquiz.sesskey = key;
}

function tcquiz_set_image(name, value) {
    tcquiz.image[name] = value;
}

function tcquiz_set_text(name, value) {
    tcquiz.text[name] = value;

}

function tcquiz_set_coursepage(url) {
    tcquiz.coursepage = url;
}

function tcquiz_set_siteroot(url) {
    tcquiz.siteroot = url;
}

function tcquiz_set_running(running) {
    tcquiz.alreadyrunning = running;
}
// TTT
function tcquiz_set_cmid(value) {
    tcquiz.cmid = value;
}

function tcquiz_set_sessionid(value) {
    tcquiz.tcqsessionid = value;
}


function tcquiz_set_page(value) {
    tcquiz.page = value;
}

function tcquiz_set_attemptid(value) {
    tcquiz.attemptid= value;
}

function tcquiz_set_joincode(value) {
    tcquiz.joincode= value;
}

function tcquiz_set_controlquiz(value) {
    tcquiz.controlquiz = value;
}


function tcquiz_delayed_request(code, time) {
    if (tcquiz.resendtimer != null) {
        clearTimeout(tcquiz.resendtimer);
        tcquiz.resendtimer = null;
    }
    tcquiz.resendtimer = setTimeout(code, time);
}

function tcquiz_init_question_view() {

  if (tcquiz.questionviewinitialised) {
        return;
    }
    if (tcquiz.controlquiz) {

        var sessiondiv = document.getElementById("availablesessions");
        if (sessiondiv)
          sessiondiv.style.display = 'none';

        document.getElementById("questionarea").innerHTML = "<h1><span id='questionnumber'>"+tcquiz.text['waitstudent']+"</span></h1><div id='numberstudents'></div><div id='questionimage'></div><div id='questiontext'>"+tcquiz.text['clicknext']+"</div><ul id='answers'></ul><p><span id='status'></span> <span id='timeleft'></span></p>";
        document.getElementById("questionarea").innerHTML += "<div id='questioncontrols'></div><br style='clear: both;' />";
        tcquiz_update_next_button(true);
    } else {
        document.getElementById("questionarea").innerHTML = "<h1><span id='questionnumber'>"+ tcquiz.text['waitfirst']+"</span></h1><div id='questionimage'></div><div id='questiontext'></div><ul id='answers'></ul><p><span id='status'></span> <span id='timeleft'></span></p><br style='clear: both;' />";

        tcquiz_get_student_question();
        tcquiz.myscore = 0;
    }
    tcquiz.questionviewinitialised = true;
}
/**************************************************
 * Functions to manage the on-screen timer
 **************************************************/
function tcquiz_start_timer(counttime, preview) {
    //ttt
    tcquiz_stop_timer();
    tcquiz_stop_timer_event();

    tcquiz.timeleft = counttime + 1;
    tcquiz.timer = setInterval("tcquiz_timer_tick("+preview+")", 1000);
    if (tcquiz.controlquiz){
      tcquiz.updatenumstudentsevent = setInterval("tcquiz_get_num_answers()",250);
    }
    else{
      tcquiz.timerevent = setInterval("update_question_status()", 250);
    }
    tcquiz_timer_tick();
}

function update_question_status(){

  const xhr = new XMLHttpRequest();
  xhr.open("GET", tcquiz.siteroot+"/mod/quiz/accessrule/tcquiz/get_question_state.php?sessionid="+tcquiz.tcqsessionid, true);

  // If specified, responseType must be empty string or "text"
  xhr.responseType = "text";

  xhr.onload = () => {
    if (xhr.readyState === xhr.DONE) {
      if (xhr.status === 200) {
        //console.log(xhr.response);
        if (parseInt(xhr.responseText) == 0) {
          tcquiz.timeleft = 0;
      }
      }
    }
  };

  xhr.send(null);

}

function tcquiz_stop_timer() {
    if (tcquiz.timer != null) {
        clearInterval(tcquiz.timer);
        tcquiz.timer = null;
    }
}


function tcquiz_stop_timer_event() {
    if (tcquiz.timerevent != null) {
        clearInterval(tcquiz.timerevent);
        tcquiz.timerevent = null;
    }
    if (tcquiz.updatenumstudentsevent != null){
      clearInterval(tcquiz.updatenumstudentsevent);
      tcquiz.updatenumstudentsevent = null;
    }
}

//preview is always false -- remove param
function tcquiz_timer_tick(preview) {
    tcquiz.timeleft--;

    if (tcquiz.timeleft <= 0) {

        //ttt
        tcquiz_stop_timer();
        tcquiz_stop_timer_event();
        tcquiz.timeleft = 0;

        document.getElementById('timeleft').innerHTML = "0";

        if (tcquiz.controlquiz){
              document.getElementById('responseform').submit();
              //tcquiz_get_results(); happens from processattempt.php which is called on the above submit
        }
        else {
              tcquiz_get_student_results();
        }


    } else {
        document.getElementById('timeleft').innerHTML = tcquiz.timeleft;
    }
}

function node_text(node) { // Cross-browser - extract text from XML node
    var text = node.textContent;
    if (text != undefined) {
        return text;
    } else {
        return node.text;
    }
}


/**************************************************
 * Functions to display information on the screen
 **************************************************/
//function tcquiz_set_status(status) {
//    document.getElementById('status').innerHTML = status;
//}









/*not used ? */
function tcquiz_resend_request() { // Only needed if something went wrong
}

/*function tcquiz_return_course() { // Go back to the course screen if something went wrong
    if (tcquiz.coursepage == '') {
        alert('tcquiz.coursepage not set');
    } else {
        //window.location = tcquiz.coursepage;
    }
}*/

//only used by the teacher
/*async function get_question_stats() {

  results_php_script = "/mod/quiz/accessrule/tcquiz/report_question_result_stats.php";
  search_params = new URLSearchParams(parameters);
  search_params.append("mode","statistics");
  //search_params.append("quizid",tcquiz.quizid);
  search_params.append("sessionid",tcquiz.tcqsessionid);
  search_params.append("slot",tcquiz.questionnumber);

  let myObject = await fetch(tcquiz.siteroot+results_php_script+"?" + search_params,{method: 'POST'});
  let myText = await myObject.text();

  document.getElementById('questiontext').innerHTML += "<br />" + myText;

  return myText;
}
*/
//prevents redirection - might come in handy
  /*  $(function () {
            $('#responseform').submit(function (event) {
                event.preventDefault();
                var form = document.getElementById('responseform');
                var formData = new FormData(form);
                formData['attempt'] = tcquiz.attemptid;
                formData['sesskey'] = tcquiz.sesskey;
                //  cmid: tcquiz.cmid,
                  //      quizid: tcquiz.quizid,
                  //      sesskey:  tcquiz.sesskey,


                $.ajax({
                    url: tcquiz.siteroot + "/mod/quiz/accessrule/tcquiz/processattempt.php",
                    method: 'POST',
                    async: false,
                    data: formData,
                    processData: false,
                    contentType: false,

                    success: function (response) {
                      nav_buttons = document.getElementsByClassName("submitbtns")[0].getElementsByTagName('input');
                      //nav_buttons[0].value = 'Submit';
                      nav_buttons[0].disabled = true;
                    },

                    error: function (xhr, status, error) {
                        alert('Your question was not submitted successfully.');
                        alert(error);
                    }
                });
            });
        });
        */
