const Selectors = {
    actions: {
        nextButton: '[data-action="quizaccess_tcquiz/waitforstudents-next_button"]',
        },
        regions: {
            numStudentsSpan: '[data-region="quizaccess_tcquiz/numberstudents_span"]',
        },
};

const registerEventListeners = (sessionid, joincode, quizid, cmid, attemptid) => {

  var updateNumStudentsEvent = setInterval(async () =>
    {await update_number_of_students(sessionid, joincode, quizid, cmid, attemptid);}, 250);


  document.addEventListener('click', async (e) => {
        if (e.target.closest(Selectors.actions.nextButton)) {
          e.preventDefault();
          clearInterval(updateNumStudentsEvent);
          updateNumStudentsEvent = null;

          var  result = await fetch(M.cfg.wwwroot+'/mod/quiz/accessrule/tcquiz/quizdatateacher.php?requesttype=getquestion&quizid='
            +quizid+'&joincode='+joincode+'&cmid='+ cmid +'&attempt='+attemptid
            +'&sessionid='+sessionid+'&rejoin=0&&currentquestion=0&sesskey='+ M.cfg.sesskey,{method: 'POST'});


          var response_xml_text = await result.text();

          await  go_to_next_url(response_xml_text);

        }
    });

};


export const init = (sessionid, joincode, quizid, cmid, attemptid) => {
  registerEventListeners(sessionid, joincode, quizid, cmid, attemptid);
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
async function update_number_of_students(sessionid, joincode, quizid, cmid, attemptid) {

  var  result = await fetch(M.cfg.wwwroot+'/mod/quiz/accessrule/tcquiz/quizdatateacher.php?requesttype=getnumberstudents&quizid='
    +quizid+'&joincode='+joincode+'&sessionid='+sessionid+'&cmid='+ cmid +'&attempt='+attemptid
    +'&currentquestion=0&sesskey='+ M.cfg.sesskey,{method: 'POST'});

  var response_xml_text = await result.text();

  await update_num_students_html(response_xml_text);

}

/**
 * helper function to update the html with fetched number of connected students
 * @param {string} response_xml_text
 */
function update_num_students_html(response_xml_text){

  var parser = new DOMParser();
  var response_xml = parser.parseFromString(response_xml_text, 'text/html');

  var quizresponse = response_xml.getElementsByTagName('tcquiz').item(0);

  var number_of_students = quizresponse.getElementsByTagName('numberstudents').item(0).textContent;

  document.querySelector(Selectors.regions.numStudentsSpan).innerHTML = number_of_students;
  //return;

}

/**
 * helper function to update the html with fetched number of connected students
 * @param {string} response_xml_text
 */
function go_to_next_url(response_xml_text){

  var parser = new DOMParser();
  var response_xml = parser.parseFromString(response_xml_text, 'text/html');

  var quizresponse = response_xml.getElementsByTagName('tcquiz').item(0);

  var next_url = quizresponse.getElementsByTagName('url').item(0).textContent;
  window.location.replace(next_url);

}
