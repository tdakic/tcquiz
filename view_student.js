/**
 * Code for a student taking the quiz
 *
 * @author: Davosmith
 * @package tcquiz
 **/

// Set up the variables used throughout the javascript

/**************************************************
 * Set up the basic layout of the student view
 **************************************************/
function tcquiz_init_student_view() {
  alert("OOOAAAA");
    var msg = "<div id='questionarea'><center><input type='text' id='joincode' required \><br \>";
    //msg = msg+"<center><input type='button' id='rt_join_student' disabled='disabled' onclick='tcquiz_start_attempt();' value='"+tcquiz.text['jointcquiz']+"' />";
    msg = msg+"<input type='button' id='rt_join_student'  onclick='tcquiz_start_attempt();' value='"+tcquiz.text['jointcquiz']+"' />";
    msg += "<p id='status'>"+tcquiz.text['joininstruct']+"</p></center></div>";
    //document.getElementById('questionarea').innerHTML = msg;
    document.getElementById('region-main').innerHTML = msg;
    alert(msg);

    student_try_to_connect();
}

async function tcquiz_start_attempt() {
  joincode = $('#joincode').val();

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
                      joincode: joincode
                    },
              success : function(data) {

                  if (data == -2){
                    alert("Invalid code. Try again.");
                  }
                  else if (data == -3){
                    alert("Quiz not running. Try again.");
                  }
                  else {

                    tcquiz.joincode = joincode;
                    tcquiz.attemptid = parseInt(data);
                    // add session id?
                    tcquiz_init_question_view();
                    tcquiz_join_quiz();

                    //location.reload();

                  }

                  }
          });

    }
}

async function submit_attempt_and_show_final_results(quizresponse){

  $.ajax({
            type: "GET",
            url: tcquiz.siteroot + "/mod/quiz/accessrule/tcquiz/submitattempt.php" ,
            async: false,
            data: { cmid: tcquiz.cmid,
                    quizid: tcquiz.quizid,
                    sesskey:  tcquiz.sesskey,
                    attempt: tcquiz.attemptid
                  },
            success : function(data) {
                final_results_url = tcquiz.siteroot +"/mod/quiz/accessrule/tcquiz/report_student_final_results.php?tcqsid="+tcquiz.tcqsessionid+"&cmid="+tcquiz.cmid+"&quizid="+tcquiz.quizid+"&attemptid="+tcquiz.attemptid;
                window.location.replace(final_results_url);
              }
        });

}


/**************************************************
 * Functions to communicate with server
 **************************************************/


async function tcquiz_create_student_request(partial_url) {
  //TTT temp fix
  split_url = partial_url.split("?");
  requested_file = split_url[0];
  parameters = split_url[1];

  //parameters = parameters.replace(/=/g,":");
  //parameters = parameters.replace(/&/g,",");
  search_params = new URLSearchParams(parameters);

  if (!search_params.has("sesskey")){

    search_params.append("sesskey",tcquiz.sesskey);
  }



  if (!search_params.has("page")){
    search_params.append("page",tcquiz.page );
  }


  // TTT workaround
  if (search_params.has("attempt")){
    search_params.delete("attempt");

    search_params.append("attempt",tcquiz.attemptid);
  }

  let myObject = await fetch(tcquiz.siteroot+requested_file + "?" + search_params,{method: 'POST'});
  let myText = await myObject.text();


  await tcquiz_process_response_student_xml(myText);
}

function tcquiz_process_response_student_xml(response_xml_text)
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
        //var quizresponse = httpRequest.responseXML.getElementsByTagName('questionpage').item(0);


          if (quizresponse == null) {
              tcquiz_delayed_request("tcquiz_resend_request()", 700);

          } else {

            var quizstatus = node_text(quizresponse.getElementsByTagName('status').item(0));

              if (quizstatus == 'quiznotrunning'){

                  tcquiz_delayed_request("student_try_to_connect()", 900);

              }
              else if (quizstatus == 'waitforquestion'){

                if (!tcquiz.questionviewinitialised && document.getElementById('rt_join_student').disabled)
                  document.getElementById('rt_join_student').disabled = false;

                tcquiz.tcqsessionid = parseInt(node_text(quizresponse.getElementsByTagName('tcq_session_id').item(0)));

                tcquiz_delayed_request("tcquiz_get_student_question()",900);

              } else if (quizstatus == 'showquestion') {

                attempt_url = node_text(quizresponse.getElementsByTagName('url').item(0));
                window.location.replace(attempt_url);


              } else if (quizstatus == 'showresults') {

                  result_url = node_text(quizresponse.getElementsByTagName('url').item(0));
                  window.location.replace(result_url);

                  // TTT
                  //tcquiz.questionnumber = tcquiz.questionnumber +1;
                  if (tcquiz.controlquiz) {
                      tcquiz_update_next_button(true);  // Teacher controls when to display the next question
                  } else {
                      tcquiz_delayed_request("tcquiz_get_student_question()",900); // Wait for next question to be displayed
                  }

              } else if (quizstatus == 'answerreceived') {
                  if (tcquiz.timeleft > 0) {
                      //tcquiz_set_status(tcquiz.text['answersent']);
                  } else {
                      tcquiz_get_results();
                  }

              } else if (quizstatus == 'waitforquestion') {
                tcquiz_init_question_view();
                //tcquiz.attemptid = node_text(quizresponse.getElementsByTagName('attemptid').item(0));
                  //document.getElementById('rt_join_student').disabled = false;

                  var waittime = quizresponse.getElementsByTagName('waittime').item(0);
                  if (waittime) {
                      waittime = parseFloat(node_text(waittime)) * 1000;
                  } else {
                      waittime = 600;
                  }


                  var number_of_students = quizresponse.getElementsByTagName('numberstudents').item(0) ;
                  if (number_of_students && document.getElementById("numberstudents")) {
                      if (node_text(number_of_students) == '1') {
                          document.getElementById("numberstudents").innerHTML = node_text(number_of_students)+' '+tcquiz.text['studentconnected'] ;
                      } else {
                          document.getElementById("numberstudents").innerHTML = node_text(number_of_students)+' '+tcquiz.text['studentsconnected'] ;
                      }
                  }


              } else if (quizstatus == 'waitforresults') {
                  var waittime = quizresponse.getElementsByTagName('waittime').item(0);
                  if (waittime) {
                      waittime = parseFloat(node_text(waittime)) * 1000;
                  } else {
                      waittime = 1000;
                  }


                  tcquiz_delayed_request("tcquiz_get_student_results()", waittime);
                }

               else if (quizstatus == 'finalresults') {


                  //for now until i figure out why it is not submitting from quizdatastudent
                  submit_attempt_and_show_final_results(quizresponse);
                  //tcquiz_show_final_results(quizresponse);

              } else if (quizstatus == 'error') {
                  var errmsg = node_text(quizresponse.getElementsByTagName('message').item(0));

              } else if (quizstatus == 'noaction') {
                  //tcquiz_resend_request();
                  tcquiz_delayed_request("tcquiz_get_student_question()",900);

              }

              else{
                  alert("2");
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


// Various requests that can be sent to the server

function student_try_to_connect(){
  tcquiz_create_student_request('/mod/quiz/accessrule/tcquiz/quizdatastudent.php?requesttype=startquiz&quizid='+tcquiz.quizid+"&cmid="+tcquiz.cmid+'&userid='+tcquiz.userid+'&attempt='+tcquiz.attemptid);
}

function tcquiz_get_student_question() {
    //tcquiz_create_request('requesttype=getquestion&quizid='+tcquiz.quizid);
    tcquiz_create_student_request('/mod/quiz/accessrule/tcquiz/quizdatastudent.php?requesttype=getquestion&quizid='+tcquiz.quizid+'&joincode='+tcquiz.joincode+'&tcqsid='+tcquiz.tcqsessionid+'&cmid='+ tcquiz.cmid+'&userid='+tcquiz.userid +'&attempt='+tcquiz.attemptid+'&currentquestion='+tcquiz.questionnumber);
}

function tcquiz_get_student_results() {
    tcquiz.questionnumber = tcquiz.page + 1; //This should not really be here
    tcquiz_create_student_request('/mod/quiz/accessrule/tcquiz/quizdatastudent.php?requesttype=getresults&quizid='+tcquiz.quizid+'&joincode='+tcquiz.joincode+'&question='+tcquiz.questionnumber+'&attempt='+tcquiz.attemptid+"&showall=false"+"&page=" + tcquiz.page);
}

function tcquiz_post_student_answer(ans) {
    //tcquiz_create_request('requesttype=postanswer&quizid='+tcquiz.quizid+'&question='+tcquiz.questionnumber+'&userid='+tcquiz.userid+'&answer='+ans);
    tcquiz_create_student_request('/mod/quiz/accessrule/tcquiz/quizdatastudent.php?requesttype=postanswer&quizid='+tcquiz.quizid+'&joincode='+tcquiz.joincode+'&question='+tcquiz.questionnumber+'&userid='+tcquiz.userid+'&answer='+ans);
}

function tcquiz_join_quiz() {
    //tcquiz_create_request('requesttype=quizrunning&quizid='+tcquiz.quizid+'');
    tcquiz_create_student_request('/mod/quiz/accessrule/tcquiz/quizdatastudent.php?requesttype=joining&quizid='+tcquiz.quizid+'&joincode='+tcquiz.joincode+'&attempt='+tcquiz.attemptid);
}
