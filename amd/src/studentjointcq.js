import $ from 'jquery';

const Selectors = {
    actions: {
        joinTCQButton: '[data-action="quizaccess_tcquiz/starttcq-jointcq_button"]',
    },
    regions: {
        inputField: '[data-region="quizaccess_tcquiz/jointcq-input"]',
 },
};

const registerEventListeners = (cmid,quizid) => {

  document.addEventListener('click', async(e) => {
      if (e.target.closest(Selectors.actions.joinTCQButton)) {
        e.preventDefault(); //try to make form validation work - button is of type submit
        var joincode = document.querySelector(Selectors.regions.inputField).value;
        if (joincode==""){
          alert("Join code cannot be empty");
          return;
        }
        const url = M.cfg.wwwroot + "/mod/quiz/accessrule/tcquiz/startattempt.php?forcenew=true&cmid="+cmid+
          "&quizid="+quizid+"&joincode="+joincode+"&sesskey="+M.cfg.sesskey;

        try{
          const response = await fetch(url, { method: "POST"});
          const data = await response.text();

          if (data == -2) {
            alert("Wrong join code. Try again");
            return;
          }
          else if (data == -3){
            alert("That quiz is not running. Try again.");
            return;
          }
          else{

            const wait_for_students_url = M.cfg.wwwroot +
            "/mod/quiz/accessrule/tcquiz/wait_for_question.php?joincode="+
            joincode+"&quizid="+quizid+"&attemptid="+data+"&sesskey="+M.cfg.sesskey+"&cmid="+cmid;

            window.location.replace(wait_for_students_url);

          }
        } catch (error) {
              //await displayException(error);
              alert('Fetch error startattempt.php: ', error);
        }
  }
  });

   window.addEventListener('load', function(){

      $("#page-content").html($("#studentjointcquizform"));
    });

};

  export const init = (sessionid, joincode, timestamp, currentpage, existingsession, quizid, cmid) => {

  registerEventListeners(cmid,quizid);

};
