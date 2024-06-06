@quizaccess @quizaccess_tcquiz @javascript
Feature: Test that the teacher can rejoin the quiz and be on the right page.
  In order to administer a TCQuiz
  As an teacher
  If I crash
  I need to be able to rejoin the quiz at the right page.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname |
      | teacher  | Teachy    |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype       | name  | questiontext                | correct        |
      | Test questions   | truefalse   | TF1   | Text of the first question  | True           |
      | Test questions   | truefalse   | TF2   | Text of the second question | False          |
    And the following "activities" exist:
        | activity | name   | intro              | course | idnumber | grade | navmethod  | tcqrequired | questiontime |
        | quiz     | Quiz 2 | Quiz 2 description | C1     | quiz1    | 100   | free       | 1           | 60          |
    And quiz "Quiz 2" contains the following questions:
      | question | page | maxmark |
      | TF1      | 1    |         |
      | TF2      | 2    |         |

  @javascript
  Scenario: Teacher creates a TCQuiz, starts it and displays the first question. Then they click on End question button
  to display the answer to the first question. Then they click Next to display the second question, but this time they
  wait for the timer to run out. The answer to second question is then displayed. When the teacher clicks Next, the final
  results are displayed.

    # The above background doesn't seem to set the quiz to be a TCQuiz.
    When I am on the "Quiz 2" "quiz activity editing" page logged in as "teacher"
    And I expand all fieldsets
    And I set the field "Administer TCQuiz" to "Yes"
    And I set the field "Default question time" to "60"
    When I click on "Save and display" "button"

    # Start new TCQ session.
    Then I should see "Start new quiz session"
    And I set the field with xpath "//input[@type='text' and @id='id_joincode']" to "teachercode6"
    When I click on "Start new quiz session" "button"
    Then I should see "Waiting for students to connect"
    When I click on "Next >>" "button"

    # First question is displayed displayed
    Then I should see "Text of the first question"
    When I click on "End question" "button"

    # The answer to the first question is displayed
    Then I should see "Text of the first question"
    And I should see "The correct answer is 'True'"
    When I click on "Next >>" "button"

    # The second question is displayed
    Then I should see "Text of the second question"
    And I wait 70 seconds

    # The answer to the second question is displayed
    Then I should see "Text of the second question"
    And I should see "The correct answer is 'False'"
    When I click on "Next >>" "button"

    # The final results are displayed
    Then I should see "Overall number of students achieving grade ranges"
    When I click on "End quiz" button

    # I should be on the quiz view page
    Then I should see "Start new quiz session"

    #And I wait for the page to be loaded

    #When I wait for the page to be loaded

    #And I follow "Quiz"
    When I am on the "Quiz 2" "mod_quiz > View" page logged in as "teacher"
    Then I should see "Start new quiz session"
    And I should see "Current page"
    And "Rejoin" "button" should be visible
    And I click on "Rejoin" "button"
    Then I should see "Text of the first question"
    And I click on "End question" "button"
    Then I should see "The correct answer is"
    And I should see "Text of the first question"
    When I am on the "Quiz 2" "mod_quiz > View" page
    And I click on "Rejoin" "button"
    Then I should see "correct"
