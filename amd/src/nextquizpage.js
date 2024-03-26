const Selectors = {
    actions: {

        nextquestionButtonR: '[data-action="quizaccess_tcquiz/next-question_in_review_button"]',
    },

};

const registerEventListeners = (sessionid, joincode, quizid, cmid, attemptid, page) => {
  document.addEventListener('click', async(e) => {
        if (e.target.closest(Selectors.actions.nextquestionButtonR)) {
          e.preventDefault();
          page++;

          var  result = await fetch(M.cfg.wwwroot+'/mod/quiz/accessrule/tcquiz/quizdatateacher.php?requesttype=getquestion&quizid='
            +quizid+'&joincode='+joincode+'&cmid='+ cmid +'&attempt='+attemptid
            +'&sessionid='+sessionid+'&rejoin=0&currentquestion='+page+'&page='+page+'&sesskey='+ M.cfg.sesskey,{method: 'POST'});

          var response_xml_text = await result.text();

          await  parse_next_url(response_xml_text);

        }
      });
};


export const init = (sessionid, joincode, quizid, cmid, attemptid, page) => {

  registerEventListeners(sessionid, joincode, quizid, cmid, attemptid, page);
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
