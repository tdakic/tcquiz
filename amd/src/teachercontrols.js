const Selectors = {
    actions: {
        endquestionButton: '[data-action="quizaccess_tcquiz/end-question_button"]',
        nextquestionButton: '[data-action="quizaccess_tcquiz/next-question_button"]',
    },
    regions: {
        numAnswers: '[data-region="quizaccess_tcquiz/numberanswers_span"]',
        timeLeft: '[data-region="quizaccess_tcquiz/timeleft_span"]',
 },
};

const registerEventListeners = (sessionid, joincode, quizid, cmid, attemptid, page, time_for_question) => {

  document.addEventListener('click', async(e) => {
        if (e.target.closest(Selectors.actions.nextquestionButton)) {

          page++;
          clearInterval(updateNumAnswersEvent);
          updateNumAnswersEvent = null;
          document.querySelector(Selectors.regions.timeLeft).innerHTML = 0; //will this stop setInterval?

          var  result = await fetch(M.cfg.wwwroot+'/mod/quiz/accessrule/tcquiz/quizdatateacher.php?requesttype=getquestion&quizid='
            +quizid+'&joincode='+joincode+'&cmid='+ cmid +'&attempt='+attemptid
          +'&sessionid='+sessionid+'&rejoin=false&currentquestion='+page+'&page='+page+'&sesskey='+ M.cfg.sesskey,{method: 'POST'});

          var response_xml_text = await result.text();
          await  parse_next_url(response_xml_text);

        }

        if (e.target.closest(Selectors.actions.endquestionButton)) {
            clearInterval(updateNumAnswersEvent);
            updateNumAnswersEvent = null;

            document.querySelector(Selectors.regions.timeLeft).innerHTML = 0; //will this stop setInterval?
            const req = new XMLHttpRequest();
            req.open("POST", M.cfg.wwwroot+
              '/mod/quiz/accessrule/tcquiz/change_question_state.php?sessionid='+sessionid+'&cmid='+cmid);
            req.send();

            req.onload = () => {
              document.getElementById('responseform').submit();
            };
        }
    });

    var updateNumAnswersEvent = setInterval(async () =>
      {await update_number_of_answers(sessionid, joincode, quizid, cmid, attemptid);}, 250);


    var timeLeft = time_for_question; //+1 to wait for everyone?

    var timer = setInterval(function() {
        var timeLeft_html = document.querySelector(Selectors.regions.timeLeft);
        timeLeft--;
        timeLeft_html.innerHTML = timeLeft;

        if (timeLeft <= 0) {
          clearInterval(timer);
          timer = null;
          timeLeft_html.innerHTML = 0;
          document.getElementById('responseform').submit();
        }
    }, 1000);

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
async function update_number_of_answers(sessionid, joincode, quizid, cmid, attemptid) {

  var  result = await fetch(M.cfg.wwwroot+'/mod/quiz/accessrule/tcquiz/quizdatateacher.php?requesttype=getnumberanswers&quizid='
    +quizid+'&joincode='+joincode+'&sessionid='+sessionid+'&cmid='+ cmid +'&attempt='+attemptid
    +'&currentquestion=0&sesskey='+ M.cfg.sesskey,{method: 'POST'});

  var response_xml_text = await result.text();

  await update_num_answers_html(response_xml_text);

}

/**
 * helper function to update the html with fetched number of connected students
 * @param {string} response_xml_text
 */
function update_num_answers_html(response_xml_text){

  var parser = new DOMParser();
  var response_xml = parser.parseFromString(response_xml_text, 'text/html');

  var quizresponse = response_xml.getElementsByTagName('tcquiz').item(0);

  var number_of_answers = quizresponse.getElementsByTagName('numanswers').item(0).textContent;
  document.querySelector(Selectors.regions.numAnswers).innerHTML = number_of_answers;
}

  export const init = (sessionid, joincode, quizid, cmid, attemptid, page, time_for_question) => {

  registerEventListeners(sessionid, joincode, quizid, cmid, attemptid, page, time_for_question);
};

/**
 * helper function to update the html with fetched number of connected students
 * @param {string} response_xml_text
 */
function parse_next_url(response_xml_text){

  var parser = new DOMParser();
  var response_xml = parser.parseFromString(response_xml_text, 'text/html');

  var quizresponse = response_xml.getElementsByTagName('tcquiz').item(0);
  var next_url = quizresponse.getElementsByTagName('url').item(0).textContent;

  window.location.replace(next_url);

}
