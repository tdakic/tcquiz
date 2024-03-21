import $ from 'jquery';

const Selectors = {
    actions: {
        starTCQButton: '[data-action="quizaccess_tcquiz/starttcq-startnew_button"]',
        endButton: '[data-action="quizaccess_tcquiz/starttcq-end_button"]',
        rejoinButton: '[data-action="quizaccess_tcquiz/starttcq-rejoin_button"]',
    },
    regions: {
        inputField: '[data-region="quizaccess_tcquiz/starttcq-input"]',
 },
};

const registerEventListeners = (sessionid, joincode, timestamp, currentpage, status, attemptid, existingsession, quizid, cmid) => {
    document.addEventListener('click', async(e) => {
        if (e.target.closest(Selectors.actions.starTCQButton)) {
          e.preventDefault(); //try to make form validation work - button is of type submit
          var joincode = document.querySelector(Selectors.regions.inputField).value;
          if (joincode==""){
            alert("Join code cannot be empty");
            return;
          }

          const url = M.cfg.wwwroot +"/mod/quiz/accessrule/tcquiz/teacherstartquiz.php?forcenew=true&cmid="+cmid+
            "&quizid="+quizid+"&joincode="+joincode+"&sesskey="+M.cfg.sesskey;

          try{
            const response = await fetch(url, { method: "POST"});

                    const attemptid = await response.text();

                    if (attemptid == -1){
                      alert("the session with the same name already exists. Try again");
                    }
                    else{

                      const wait_for_students_url = M.cfg.wwwroot +
                      "/mod/quiz/accessrule/tcquiz/wait_for_students.php?joincode="+
                      joincode+"&quizid="+quizid+"&attemptid="+attemptid+"&sesskey="+M.cfg.sesskey+"&cmid="+cmid;
                      window.location.replace(wait_for_students_url);
                    }
            } catch (error) {
                  //await displayException(error);
                  alert('Fetch error teacherstartquiz.php: ', error);
            }

    }
    });

    document.addEventListener('click', async(e) => {
          if (e.target.closest(Selectors.actions.endButton)) {

            var url = M.cfg.wwwroot+"/mod/quiz/accessrule/tcquiz/end_session.php?id="+ e.target.name + "&cmid=" + cmid;

            try {
                    await fetch(url, { method: "POST",});
                    await location.reload();
            } catch (error) {
                  alert('Fetch error end_session.php: ', error);
            }

          }
    });

    document.addEventListener('click', async(e) => {
          if (e.target.closest(Selectors.actions.rejoinButton)) {
            //constants defined in locallib.php. Move them!!!
            var url = "";
            if (status == 10){
              url = M.cfg.wwwroot+"/mod/quiz/accessrule/tcquiz/wait_for_students.php?quizid="+ quizid + "&cmid=" + cmid +
              "&attemptid=" + attemptid + "&joincode=" + joincode + "&sessionid=" + sessionid+"&sesskey="+ M.cfg.sesskey;
            }
            else if (status == 15 || status == 20){
              url = M.cfg.wwwroot+"/mod/quiz/accessrule/tcquiz/attempt.php?showall=0&quizid="+ quizid + "&cmid=" + cmid +
              "&attempt=" + attemptid + "&joincode" + joincode + "&sessionid=" + sessionid + "&page=" + currentpage +
              "&sesskey="+ M.cfg.sesskey;
            }
            else if (status == 30){
              url = M.cfg.wwwroot+"/mod/quiz/accessrule/tcquiz/review_tcq.php?showall=false&quizid="+ quizid + "&cmid=" + cmid +
              "&attempt=" + attemptid + "&joincode" + joincode + "&sessionid=" + sessionid + "&page=" + currentpage +
              "&sesskey="+ M.cfg.sesskey;
            }
            else if (status == 40){
              //tcquiz_get_final_results();
              url = M.cfg.wwwroot+"/mod/quiz/accessrule/tcquiz/report_final_results.php?mode=overview&quizid="+ quizid +
              "&id=" + cmid + "&tcqsid=" + sessionid + "&sesskey="+ M.cfg.sesskey;
            }
            else{
              alert("quiz not running");
            }

            window.location.replace(url);

          }
    });


   window.addEventListener('load', function(){

      $("#page-content").html($("#starttcquizform"));

    });

};

export const init = (sessionid, joincode, timestamp, currentpage, status, attemptid, existingsession, quizid, cmid) => {

  registerEventListeners(sessionid, joincode, timestamp, currentpage, status, attemptid, existingsession, quizid, cmid);

};
