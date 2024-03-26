
const registerEventListeners = (sessionid, joincode, quizid, cmid, attemptid, page, time_for_question) => {
// should this listener be more specific or should

window.goToCurrentQuizPageEvent = setInterval(async () =>
  {await go_to_current_quiz_page(sessionid, joincode, quizid, cmid, attemptid, page, time_for_question);}, 250);
};


/**
 * Add two numbers.
 * @param {sessionid} sessionid The first number.
 * @param {joincode} joincode The second number.
 * @param {quizid} quizid The second number.
 * @param {cmid} cmid The second number.
 * @param {attemptid} attemptid The second number.
 * @param {page} page The second number.
 * @param {time_for_question} time_for_question The second number.
 * @returns {quizid} The sum of the two numbers.
 */
async function go_to_current_quiz_page(sessionid, joincode, quizid, cmid, attemptid, page, time_for_question) {

  var  result = await fetch(M.cfg.wwwroot+'/mod/quiz/accessrule/tcquiz/quizdatastudent.php?requesttype=getnumberstudents&quizid='
    +quizid+'&joincode='+joincode+'&sessionid='+sessionid+'&cmid='+ cmid +'&attempt='+attemptid
    +'&currentquestion=0&sesskey='+ M.cfg.sesskey+'&page='+page+'time_for_q='+time_for_question,{method: 'POST'});

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

                //window.goToCurrentQuizPageEvent = null;
                //clearInterval(window.goToCurrentQuizPageEvent);
                //var result_url = quizresponse.getElementsByTagName('url').item(0).textContent;
                //window.location.replace(result_url);

                  //tcquiz_delayed_request("tcquiz_get_student_question()",900); // Wait for next question to be displayed
              }

              else if (quizstatus == 'waitforquestion') {

              } else if (quizstatus == 'waitforresults') {

              }

               else if (quizstatus == 'finalresults') {


                  window.goToCurrentQuizPageEvent = null;
                  clearInterval(window.goToCurrentQuizPageEvent);
                  var result_url = quizresponse.getElementsByTagName('url').item(0).textContent;
                  window.location.replace(result_url);


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
  //alert(sessionid, joincode, quizid, cmid, attemptid, page, time_for_question);
  registerEventListeners(sessionid, joincode, quizid, cmid, attemptid, page, time_for_question);
};
