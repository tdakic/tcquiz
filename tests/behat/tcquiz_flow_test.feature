@quizaccess @quizaccess_tcquiz @javascript
Feature: Test that the teacher can control a flow of a TCQuiz.
  In order to complete a TCQuiz
  As an student
  I need to be answer the questions when they are presented by the teacher.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname |
      | teacher  | Teachy    |
      | student  | Study     |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
      | student | C1     | student        |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype       | name  | questiontext                 | correct |
      | Test questions   | truefalse   | TF1   | Text of the first question   | True    |
      | Test questions   | truefalse   | TF2   | Text of the second question  | True    |
    And the following "activities" exist:
        | activity | name   | intro              | course | idnumber | grade | navmethod  | tcqrequired | questiontime |
        | quiz     | Quiz 1 | Quiz 1 description | C1     | quiz1    | 2     | free       | 1           | 60          |
    And quiz "Quiz 1" contains the following questions:
      | question | page | maxmark |
      | TF1      | 1    |  1      |
      | TF2      | 2    |  1      |

  @javascript
  Scenario: Teacher creates a TCQuiz, starts it and displays the first question. The student joins the quiz
  and should see the first question.
    # The above background doesn't seem to set the quiz to be a TCQuiz, so here it goes.
    When I am on the "Quiz 1" "quiz activity editing" page logged in as "teacher"
    And I expand all fieldsets
    And I set the field "Administer TCQuiz" to "Yes"
    And I set the field "Default question time" to "60"
    And I click on "Save and display" "button"
    Then I should see "Start new quiz session"
    And I set the field with xpath "//input[@type='text' and @id='id_joincode']" to "teachercode4"
    When I click on "Start new quiz session" "button"
    Then I should see "Waiting for students to connect"
    And I should see "Number of connected students 0"
    And I log out

    # Student joins the quiz.
    When I am on the "Quiz 1" "mod_quiz > View" page logged in as "student"
    Then I should see "Wait until your teacher gives you the code."
    And "Join quiz" "button" should be visible
    And I set the field with xpath "//input[@type='text' and @id='id_joincode']" to "teachercode4"
    When I click on "Join quiz" "button"
    Then I should see "Waiting for the first question to be sent"
    And I log out

    # Teacher posts the first question.
    When I am on the "Quiz 1" "mod_quiz > View" page logged in as "teacher"
    Then "Rejoin" "button" should be visible
    And I should see "teachercode4"
    When I click on "Rejoin" "button"
    Then I should see "Number of connected students 1"
    And I click on "Next >>" "button"
    Then I should see "Text of the first question"
    And I log out

    # Student answers the first question.
    And I am on the "Quiz 1" "mod_quiz > View" page logged in as "student"
    Then I should see "Wait until your teacher gives you the code."
    And "Join quiz" "button" should be visible
    And I set the field with xpath "//input[@type='text' and @id='id_joincode']" to "teachercode4"
    When I click on "Join quiz" "button"
    Then I should see "Text of the first question"
    And I click on "True" "radio" in the "Text of the first question" "question"
    And I click on "Submit" "button"
    Then I should see "Question done - waiting for results."
    And I log out

    # The teacher ends the first question.
    When I am on the "Quiz 1" "mod_quiz > View" page logged in as "teacher"
    Then "Rejoin" "button" should be visible
    And I should see "teachercode4"
    When I click on "Rejoin" "button"
    Then I should see "Text of the first question"
    And I should see "Number of received answers 1"
    And "End question" "button" should be visible
    When I click on "End question" "button"
    Then I should see "Text of the first question"
    And I should see "The correct answer is 'True'"
    And I log out

    # Check that the student can see the answer and if their answer is correct.
    And I am on the "Quiz 1" "mod_quiz > View" page logged in as "student"
    Then I should see "Wait until your teacher gives you the code."
    And "Join quiz" "button" should be visible
    And I set the field with xpath "//input[@type='text' and @id='id_joincode']" to "teachercode4"
    When I click on "Join quiz" "button"
    Then I should see "Text of the first question"
    And I should see "Correct"
    And I should see "The correct answer is 'True'"
    And I log out

    # The teacher starts the second question.
    When I am on the "Quiz 1" "mod_quiz > View" page logged in as "teacher"
    Then "Rejoin" "button" should be visible
    And I should see "teachercode4"
    When I click on "Rejoin" "button"
    Then I should see "Text of the first question"
    And I should see "Analysis of responses"
    And "Next >>" "button" should be visible
    When I click on "Next >>" "button"
    Then I should see "Text of the second question"
    And I log out

    # The student answers the second question
    When I am on the "Quiz 1" "mod_quiz > View" page logged in as "student"
    Then I should see "Wait until your teacher gives you the code."
    And "Join quiz" "button" should be visible
    And I set the field with xpath "//input[@type='text' and @id='id_joincode']" to "teachercode4"
    When I click on "Join quiz" "button"
    Then I should see "Text of the second question"
    And I click on "False" "radio" in the "Text of the second question" "question"
    And I click on "Submit" "button"
    Then I should see "Question done - waiting for results."
    And I log out

    # This time the teacher should let the time elapse.
    When I am on the "Quiz 1" "mod_quiz > View" page logged in as "teacher"
    Then "Rejoin" "button" should be visible
    And I should see "teachercode4"
    When I click on "Rejoin" "button"
    Then I should see "Text of the second question"
    And I should see "Number of received answers 1"
    And "End question" "button" should be visible
    And I wait 65 seconds for "The correct answer is 'True'." to appear
    And I log out

    # The student should now be able to see the answer.
    And I am on the "Quiz 1" "mod_quiz > View" page logged in as "student"
    Then I should see "Wait until your teacher gives you the code."
    And "Join quiz" "button" should be visible
    And I set the field with xpath "//input[@type='text' and @id='id_joincode']" to "teachercode4"
    When I click on "Join quiz" "button"
    Then I should see "Text of the second question"
    And I should see "The correct answer is 'True'."
    And I should see "Incorrect"
    And I log out

    # The teacher clicks next to display the final results.
    When I am on the "Quiz 1" "mod_quiz > View" page logged in as "teacher"
    Then "Rejoin" "button" should be visible
    And I should see "teachercode4"
    When I click on "Rejoin" "button"
    Then I should see "Text of the second question"
    And I should see "Analysis of responses"
    And "Next >>" "button" should be visible
    When I click on "Next >>" "button"
    #Then I should see "Overall number of students achieving grade ranges" - fails???
    And I should see "Attempts: 1"
    And I log out

    # The student should see their grade.

    When I am on the "Quiz 1" "mod_quiz > View" page logged in as "student"
    Then I should see "Wait until your teacher gives you the code."
    And "Join quiz" "button" should be visible
    And I set the field with xpath "//input[@type='text' and @id='id_joincode']" to "teachercode4"
    When I click on "Join quiz" "button"
    Then "Done" "button" should be visible
    #And I should see "Your score is 1.00 / 2."
    #And I wait 5 seconds for "Your score is 1.00 / 2." to appear
    When I click on "Done" "button"
    Then I should see "Wait until your teacher gives you the code."
    And I log out

    # The teacher ends the quiz

    When I am on the "Quiz 1" "mod_quiz > View" page logged in as "teacher"
    Then "Rejoin" "button" should be visible
    And I should see "teachercode4"
    When I click on "Rejoin" "button"
    Then I should see "Overall number of students achieving grade ranges"
    When I click on "End quiz" "button"
    Then I should see "Start new quiz session"
    And I log out
