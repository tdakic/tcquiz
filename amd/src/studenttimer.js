const Selectors = {
    regions: {
        timeLeft: '[data-region="quizaccess_tcquiz/timeleft_span"]',
 },
};

const registerEventListeners = (sessionid, joincode, quizid, cmid, attemptid, page, time_for_question) => {
//the timer can be stoped either by the teacher or time -- handle both events
//enough to check when the state of the quiz has changed to show results (30)


    var timeLeft = time_for_question; //+1 to wait for everyone?
    var timeLeft_html = document.querySelector(Selectors.regions.timeLeft);
    var teacherEndedQuestion = false;

    var timer = setInterval(function() {
      //alert(timeLeft);
        timeLeft--;
        timeLeft_html.innerHTML = timeLeft;
        if (timeLeft <= 0 || teacherEndedQuestion){
          clearInterval(timer);
          clearInterval(tecaherEndedQuestionEvent);
          timer = null;
          timeLeft_html.innerHTML = 0;
          window.goToCurrentQuizPageEvent = setInterval(async () =>
            {await go_to_current_quiz_page(sessionid, joincode, quizid, cmid, attemptid);}, 250);
          //document.getElementById('responseform').submit();
        }
    }, 1000);




    const tecaherEndedQuestionEvent = setInterval(async function() {
      teacherEndedQuestion = await check_question_state(sessionid, joincode, quizid, cmid, attemptid);
    }, 250); //1000 means 1 sec, 5000 means 5 seconds

};


/**
 * Add two numbers.
 * @param {sessionid} sessionid The first number.
 * @param {joincode} joincode The second number.
 * @param {quizid} quizid The second number.
 * @param {cmid} cmid The second number.
 * @param {attemptid} attemptid The second number.
 * @returns {quizid} The sum of the two numbers.
 */
async function check_question_state(sessionid, joincode, quizid, cmid, attemptid) {

  var  result = await fetch(M.cfg.wwwroot+'/mod/quiz/accessrule/tcquiz/get_question_state.php?requesttype=getnumberanswers&quizid='
    +quizid+'&joincode='+joincode+'&sessionid='+sessionid+'&cmid='+ cmid +'&attempt='+attemptid
    +'&currentquestion=0&sesskey='+ M.cfg.sesskey,{method: 'POST'});

  var response_xml_text = await result.text();

  return response_xml_text == "0";

}

/**
 * Add two numbers.
 * @param {sessionid} sessionid The first number.
 * @param {joincode} joincode The second number.
 * @param {quizid} quizid The second number.
 * @param {cmid} cmid The second number.
 * @param {attemptid} attemptid The second number.
 * @returns {quizid} The sum of the two numbers.
 */
async function go_to_current_quiz_page(sessionid, joincode, quizid, cmid, attemptid) {

  var  result = await fetch(M.cfg.wwwroot+'/mod/quiz/accessrule/tcquiz/quizdatastudent.php?requesttype=getnumberstudents&quizid='
    +quizid+'&joincode='+joincode+'&sessionid='+sessionid+'&cmid='+ cmid +'&attempt='+attemptid
    +'&currentquestion=0&sesskey='+ M.cfg.sesskey,{method: 'POST'});

  var response_xml_text = await result.text();

  await update_quiz_page(response_xml_text);

}

/**
 * helper function to update the html with fetched number of connected students
 * @param {string} response_xml_text
 */
function update_quiz_page(response_xml_text) {

        const parser = new DOMParser();
        const response_xml = parser.parseFromString(response_xml_text, 'text/html');

        var quizresponse = response_xml.getElementsByTagName('tcquiz').item(0);

        //ERROR handling?
        //var quizresponse = httpRequest.responseXML.getElementsByTagName('questionpage').item(0);


          if (quizresponse === null) {
              //Try again?
              //alert("NO RESPONSE");
              return;

          } else {

            var quizstatus = quizresponse.getElementsByTagName('status').item(0).textContent;

              if (quizstatus == 'quiznotrunning'){

                  //tcquiz_delayed_request("student_try_to_connect()", 900);
                  //keep trying

              }
              else if (quizstatus == 'waitforquestion'){

                //tcquiz_delayed_request("tcquiz_get_student_question()",900);

              } else if (quizstatus == 'showquestion') {

                window.goToCurrentQuizPageEvent = null;
                clearInterval(window.goToCurrentQuizPageEvent);
                var attempt_url = quizresponse.getElementsByTagName('url').item(0).textContent;
                window.location.replace(attempt_url);

              } else if (quizstatus == 'showresults') {

                window.goToCurrentQuizPageEvent = null;
                clearInterval(window.goToCurrentQuizPageEvent);
                var result_url = quizresponse.getElementsByTagName('url').item(0).textContent;
                window.location.replace(result_url);

                  //tcquiz_delayed_request("tcquiz_get_student_question()",900); // Wait for next question to be displayed
              }

              else if (quizstatus == 'waitforquestion') {

              } else if (quizstatus == 'waitforresults') {

              }

               else if (quizstatus == 'finalresults') {


                  window.goToCurrentQuizPageEvent = null;
                  clearInterval(window.goToCurrentQuizPageEvent);


              } else if (quizstatus == 'error') {
                  var errmsg = quizresponse.getElementsByTagName('message').item(0).textContent;
                  alert('ERR' + errmsg);

              } else if (quizstatus == 'noaction') {

              }

              else{
                  alert("ERROR");

              }
          }


}




  export const init = (sessionid, joincode, quizid, cmid, attemptid, page, time_for_question) => {

  registerEventListeners(sessionid, joincode, quizid, cmid, attemptid, page, time_for_question);
};
