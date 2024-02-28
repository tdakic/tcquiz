/**
 * Code for a teacher running a quiz
 *
 * @author: Davosmith
 * @package tcquiz
 **/
//something like this should be used to prevent double clicking
//tcquiz.clickednext = 0; // The question number of the last time the teacher clicked 'next'

function tcquiz_first_question() {

    tcquiz.controlquiz = true;

    var joincode = document.getElementById('joincode').value;
    if (joincode.length > 0) {
        joincode = encodeURIComponent(joincode);

    tcquiz_create_request('/mod/quiz/accessrule/tcquiz/quizdatateacher.php?requesttype=startquiz&joincode='+joincode+'&quizid='+tcquiz.quizid+"&cmid="+tcquiz.cmid+'&userid='+tcquiz.userid+'&attempt='+tcquiz.attemptid);
    //tcquiz_init_question_view();
    }
    else {
      alert("Join code empty!");
    }
}


//TTT this needs attention
function tcquiz_update_next_button(enabled) {

    if (!tcquiz.controlquiz) {
        return;
    }
    if (enabled) {
        document.getElementById('questioncontrols').innerHTML = '<input type="button" id="nextbutton" class="btn btn-secondary" onclick="run_next_quiz_question()" value="'+tcquiz.text['next']+'" />';
    }
    else {
        document.getElementById('questioncontrols').innerHTML = '<input type="button"  id="nextbutton"  class="btn btn-secondary" onclick="run_next_quiz_question()" value="'+tcquiz.text['next']+'" disabled="disabled" />';
    }

/*    document.getElementById('questioncontrols').innerHTML += '<div id="teachercontrols">'
    document.getElementById('questioncontrols').innerHTML += 'Number of received answers: ' + '<span id="numanswers"> 0 </span><br />';
    //document.getElementById('questioncontrols').innerHTML += 'Time left ' + + '<span id="timeleft"> 0 </span><br />';
    document.getElementById('questioncontrols').innerHTML += '<input type="button" class="btn btn-secondary" value="End question" id="endquestion"/>';
    document.getElementById('questioncontrols').innerHTML += '</div>';

    $("#endquestion").on("click", function() {
        tcquiz.timeleft = 0;
        const req = new XMLHttpRequest();
        req.open("POST", tcquiz.siteroot+'/mod/quiz/accessrule/tcquiz/change_question_state.php?sessionid='+tcquiz.tcqsessionid);
        //alert(tcquiz.quizid);
        //req.send("quizid="+tcquiz.quizid);
        req.send();
        req.onload = (e) => {
          //alert("Sent!"+ req.readyState);
        }
    });*/

}

//atteched to the End question button in renderer
function end_button_action(){

        //tcquiz_stop_timer();
        tcquiz.timeleft = 0;
        const req = new XMLHttpRequest();
        req.open("POST", tcquiz.siteroot+'/mod/quiz/accessrule/tcquiz/change_question_state.php?sessionid='+tcquiz.tcqsessionid+'&cmid='+tcquiz.cmid);
        req.send();
        req.onload = (e) => {

        }
}

// get the attemptid - ajax used because of the sync issues
async function tcquiz_start_quiz() {
  //get the code and validate it startattempt
  //var joincode = $("#joincode").val();
  var joincode = document.getElementById('joincode').value;
  if (joincode.length == 0) {
      alert("Code cannot be empty! ");
  }
  else {
    $.ajax({
              type: "GET",
              url: tcquiz.siteroot + "/mod/quiz/accessrule/tcquiz/startattempt.php" ,
              async: false,
              data: { cmid: tcquiz.cmid,
                      quizid: tcquiz.quizid,
                      sesskey:  tcquiz.sesskey,
                      forcenew: true,
                      joincode: joincode},
              success : function(data) {
                  if (data == -1){
                    alert("the session with the same name already exists. Try again");
                  }
                  else{
                    tcquiz.joincode = joincode;
                    tcquiz.attemptid = parseInt(data);
                    tcquiz.controlquiz = true;
                    tcquiz_first_question();
                  }

                }
          });

  }
}



function tcquiz_init_teacher_view(sess) {

  tcquiz.controlquiz = false;
  var msg = "<div id = 'questionarea' style='text-align: center;'>";

  for(var i = 0; i < sess.length; i++){
    msg += sess[i];
  }
  //add the notifiaction span back
  msg += "<span class='notifications' id='user-notifications'></span>";
  msg += "<p>" + tcquiz.text['availablesessionsdesc'] + "</p>";

  msg += "<input type='button' class='btn btn-secondary' onclick='tcquiz_start_quiz();' value='" + tcquiz.text['startnewquiz'] + "' /> <input type='text' name='joincode' id='joincode' maxlength='255' value='' />";
  msg += "<p>" + tcquiz.text['teacherstartnewinstruct'] + "</p>";

  document.getElementById('region-main').innerHTML = msg;
}


// TTT changed to fetch instead deprecated httprequest
async function tcquiz_create_request(partial_url) {
  split_url = partial_url.split("?");
  requested_file = split_url[0];
  parameters = split_url[1];


  search_params = new URLSearchParams(parameters);

  if (!search_params.has("sesskey")){

    search_params.append("sesskey",tcquiz.sesskey);
  }
  //TTT temp fix


  if (!search_params.has("page")){

    search_params.append("page",tcquiz.questionnumber);
  }


  // TTT workaround
  if (search_params.has("attempt")){
    search_params.delete("attempt");

    search_params.append("attempt",tcquiz.attemptid);
  }

  let myObject = await fetch(tcquiz.siteroot+requested_file + "?" + search_params,{method: 'POST'});
  let myText = await myObject.text();

  await tcquiz_process_response_xml(myText);
}

function tcquiz_process_response_xml(response_xml_text)
{

        tcquiz.response_xml_text= response_xml_text;
        // We've heard back from the server, so do not need to resend the request
        if (tcquiz.resendtimer != null) {
            clearTimeout(tcquiz.resendtimer);
            tcquiz.resendtimer = null;
        }

        // Reduce the resend delay whenever there is a successful message
        // (assume network delays have started to recover again)
        tcquiz.resenddelay -= 2000;
        if (tcquiz.resenddelay < 2000) {
            tcquiz.resenddelay = 2000;
        }

        const parser = new DOMParser();
        const response_xml = parser.parseFromString(response_xml_text, 'text/html');

        var quizresponse = response_xml.getElementsByTagName('tcquiz').item(0);


        //ERROR handling?

          if (quizresponse == null) {
              tcquiz_delayed_request("tcquiz_resend_request()", 700);

          } else {

              var quizstatus = node_text(quizresponse.getElementsByTagName('status').item(0));

              if (quizstatus == 'quizrunning'){

                tcquiz_init_question_view();

              } else if (quizstatus == 'showquestion') {


                attempt_url = node_text(quizresponse.getElementsByTagName('url').item(0));
                window.location.replace(attempt_url);

              } else if (quizstatus == 'showresults') {


                results_url = node_text(quizresponse.getElementsByTagName('url').item(0));
                window.location.replace(results_url);

                  //if (tcquiz.controlquiz) {
                    //  tcquiz_update_next_button(true);  // Teacher controls when to display the next question
                //  } else {
                //      tcquiz_delayed_request("tcquiz_get_student_question()",900); // Wait for next question to be displayed
                //  }

              } else if (quizstatus == 'updatenumberanswers') {

                var number_of_answers = node_text(quizresponse.getElementsByTagName('numanswers').item(0));
                // $("#numanswers").innerHTML = number_of_answers; //doesn't work?
                document.getElementById("numanswers").innerHTML = number_of_answers;

              } else if (quizstatus == 'waitforquestion') {
                  // if not initialized ....fix it
                  tcquiz_init_question_view();

                  var waittime = quizresponse.getElementsByTagName('waittime').item(0);
                  if (waittime) {
                      waittime = parseFloat(node_text(waittime)) * 1000;
                  } else {
                      waittime = 600;
                  }
                  var number_of_students = quizresponse.getElementsByTagName('numberstudents').item(0);
                  if (number_of_students && document.getElementById("numberstudents")) {
                      if (node_text(number_of_students) == '1') {
                          document.getElementById("numberstudents").innerHTML = node_text(number_of_students)+' '+tcquiz.text['studentconnected'] ;
                      } else {
                          document.getElementById("numberstudents").innerHTML = node_text(number_of_students)+' '+tcquiz.text['studentsconnected'] ;
                      }
                  }

                  tcquiz.totalquestions = parseInt(node_text(quizresponse.getElementsByTagName('questioncount').item(0)));
                  tcquiz.tcqsessionid = parseInt(node_text(quizresponse.getElementsByTagName('tcq_session_id').item(0)));

                  if (!tcquiz.questionsstarted){

                    tcquiz_delayed_request("tcquiz_get_number_of_students()",250);
                  }
              }
              else if (quizstatus == 'updatenumberstudents') {
                var number_of_students = quizresponse.getElementsByTagName('numberstudents').item(0) ;
                if (number_of_students && document.getElementById("numberstudents")) {
                    if (node_text(number_of_students) == '1') {
                        document.getElementById("numberstudents").innerHTML = node_text(number_of_students)+' '+tcquiz.text['studentconnected'] ;
                    } else {
                        document.getElementById("numberstudents").innerHTML = node_text(number_of_students)+' '+tcquiz.text['studentsconnected'] ;
                    }
                }
                //tcquiz_delayed_request("tcquiz_get_question()", waittime);
                if (!tcquiz.questionsstarted){

                  tcquiz_delayed_request("tcquiz_get_number_of_students()",250);
                }
              }
              else if (quizstatus == 'waitforresults') {
                  var waittime = quizresponse.getElementsByTagName('waittime').item(0);
                  if (waittime) {
                      waittime = parseFloat(node_text(waittime)) * 1000;
                  } else {
                      waittime = 1000;
                  }
                  tcquiz_delayed_request("tcquiz_get_results()", waittime);

              }  else if (quizstatus == 'quiznotrunning') {

                  //do nothing?

              } else if (quizstatus == 'finalresults') {

                    final_results_url = tcquiz.siteroot +"/mod/quiz/accessrule/tcquiz/report_final_results.php?tcqsid="+tcquiz.tcqsessionid+"&quizid="+tcquiz.quizid;
                    window.location.replace(final_results_url);

              } else if (quizstatus == 'error') {
                  var errmsg = node_text(quizresponse.getElementsByTagName('message').item(0));
                  alert(tcquiz.text['servererror']+errmsg);

              } else {
                  alert(tcquiz.text['badresponse']+httpRequest.responseText);
                  if (confirm(tcquiz.text['tryagain'])) {
                      tcquiz_resend_request();
                  } else {
                      tcquiz_return_course();
                  }
              }
          }
          return;

}

function tcquiz_get_question(rejoin = false) {

    //tcquiz_create_request('requesttype=getquestion&quizid='+tcquiz.quizid);
    tcquiz_create_request('/mod/quiz/accessrule/tcquiz/quizdatateacher.php?requesttype=getquestion&rejoin='+rejoin+'&sessionid='+tcquiz.tcqsessionid+'&quizid='+tcquiz.quizid+'&joincode='+tcquiz.joincode+'&tcqsid='+tcquiz.tcqsessionid+'&cmid='+ tcquiz.cmid+'&userid='+tcquiz.userid +'&attempt='+tcquiz.attemptid+'&currentquestion='+tcquiz.questionnumber);
    //alert ('/mod/quiz/accessrule/tcquiz/quizdatateacher.php?requesttype=getquestion&rejoin='+rejoin+'&quizid='+tcquiz.quizid+'&joincode='+tcquiz.joincode+'&tcqsid='+tcquiz.tcqsessionid+'&cmid='+ tcquiz.cmid+'&userid='+tcquiz.userid +'&attempt='+tcquiz.attemptid+'&currentquestion='+tcquiz.questionnumber);
}

function tcquiz_get_number_of_students() {

    //tcquiz_create_request('requesttype=getquestion&quizid='+tcquiz.quizid);
    tcquiz_create_request('/mod/quiz/accessrule/tcquiz/quizdatateacher.php?requesttype=getnumberstudents&quizid='+tcquiz.quizid+'&joincode='+tcquiz.joincode+'&tcqsid='+tcquiz.tcqsessionid+'&cmid='+ tcquiz.cmid+'&userid='+tcquiz.userid +'&attempt='+tcquiz.attemptid+'&currentquestion='+tcquiz.questionnumber);

}

function tcquiz_get_final_results() {

    //tcquiz_create_request('requesttype=getquestion&quizid='+tcquiz.quizid);
    tcquiz_create_request('/mod/quiz/accessrule/tcquiz/quizdatateacher.php?requesttype=getfinalresults&quizid='+tcquiz.quizid+'&joincode='+tcquiz.joincode+'&id='+ tcquiz.cmid+'&tcqsid='+tcquiz.tcqsessionid+'&userid='+tcquiz.userid +'&attempt='+tcquiz.attemptid+'&mode=overview');

}
function tcquiz_end_quiz() {

    //tcquiz_create_request('requesttype=getquestion&quizid='+tcquiz.quizid);
    tcquiz_create_request('/mod/quiz/accessrule/tcquiz/quizdatateacher.php?requesttype=endquiz&quizid='+tcquiz.quizid+'&joincode='+tcquiz.joincode+'&tcqsid='+tcquiz.tcqsessionid+'&cmid='+ tcquiz.cmid+'&userid='+tcquiz.userid +'&attempt='+tcquiz.attemptid+'&mode=overview');

}

function tcquiz_get_results() {
    //not called anymore ... processattempt.php does thework
    tcquiz_create_request('/mod/quiz/accessrule/tcquiz/quizdatateacher.php?requesttype=getresults&quizid='+tcquiz.quizid+'&joincode='+tcquiz.joincode+'&sessionid='+tcquiz.tcqsessionid+'&tcqsid='+tcquiz.tcqsessionid+'&question='+tcquiz.questionnumber+'&attempt='+tcquiz.attemptid+"&showall=false"+"&page=" + (parseInt(tcquiz.questionnumber) -1));
    //alert(tcquiz.quizid+'&question='+tcquiz.questionnumber+'&attempt='+tcquiz.attemptid+"&showall=false"+"&page=" + (parseInt(tcquiz.questionnumber) -1));
}

function tcquiz_get_num_answers() {
    tcquiz_create_request('/mod/quiz/accessrule/tcquiz/quizdatateacher.php?requesttype=getnumberanswers&quizid='+tcquiz.quizid+'&joincode='+tcquiz.joincode+'&tcqsid='+tcquiz.tcqsessionid+'&question='+tcquiz.questionnumber+'&attempt='+tcquiz.attemptid+"&showall=false"+"&page=" + (parseInt(tcquiz.questionnumber) -1));
    //alert(tcquiz.quizid+'&question='+tcquiz.questionnumber+'&attempt='+tcquiz.attemptid+"&showall=false"+"&page=" + (parseInt(tcquiz.questionnumber) -1));
}


function tcquiz_post_answer(ans) {
    //tcquiz_create_request('requesttype=postanswer&quizid='+tcquiz.quizid+'&question='+tcquiz.questionnumber+'&userid='+tcquiz.userid+'&answer='+ans);
    tcquiz_create_request('/mod/quiz/accessrule/tcquiz/quizdatateacher.php?requesttype=postanswer&quizid='+tcquiz.quizid+'&joincode='+tcquiz.joincode+'&tcqsid='+tcquiz.tcqsessionid+'&question='+tcquiz.questionnumber+'&userid='+tcquiz.userid+'&answer='+ans);
}


//called every time the teacher clicks the next button
function run_next_quiz_question()
{

    tcquiz.questionsstarted = true;
    //if (!endofquiz){
    //aaargh this is horrible --  who changes tcquiz.questionnumber?????...should be changed here
    // seems to work by a miracle and many sideeffects
    if (tcquiz.questionnumber < tcquiz.totalquestions){

      //get question - the DB detrmines which one
      tcquiz_get_question();
      //display question
      // start the timer which displays results
    }
    else {

        //submit_attempt_and_show_final_results("");
        tcquiz_get_final_results();
    }
}

//attached to a button in rule.php
function tcquiz_teacher_rejoin(joincode,id,status,attemptid)
{

  tcquiz.controlquiz = true;
  tcquiz_init_question_view();
  //alert(joincode + " " +id + " " + status +" "+attemptid);
  tcquiz.tcqsessionid = id;
  tcquiz.joincode = joincode;
  tcquiz.attemptid = attemptid;


  //need to reopen the attempt?

  if (status == 10){
    tcquiz_get_number_of_students();
  }
  else if (status == 15 || status == 20){
    tcquiz_get_question(true);
  }
  else if (status == 30){
    tcquiz_get_results();
  }
  else if (status == 40){
    tcquiz_get_final_results();

  }


}

//attached to a button in rule.php
async function tcquiz_end_session(id)
{
  search_params = new URLSearchParams();
  search_params.append("id",id);
  search_params.append("cmid",tcquiz.cmid);


  let myObject = await fetch(tcquiz.siteroot+"/mod/quiz/accessrule/tcquiz/end_session.php" + "?" + search_params,{method: 'POST'});
  //let myText = await myObject.text();

  await location.reload();

}

/* not used but might be useful
async function tcquiz_reconnect_quiz() {
    tcquiz.controlquiz = true;

    $.ajax({
              type: "GET",
              url: tcquiz.siteroot + "/mod/tcquiz/startattempt.php" ,
              async: false,
              data: { cmid: tcquiz.cmid,
                      quizid: tcquiz.quizid,
                      sesskey:  tcquiz.sesskey,
                      forcenew: false},
              success : function(data) {

                  tcquiz.attemptid = data;

                  tcquiz.controlquiz = true;

                  tcquiz_create_request('/mod/tcquiz/quizdata.php?requesttype=teacherrejoin&quizid='+tcquiz.quizid+'&joincode='+tcquiz.joincode+'&userid='+tcquiz.userid+'&attempt='+tcquiz.attemptid+'&currentquestion='+tcquiz.questionnumber+'&showall=false' );

              }
          });

}

*/
