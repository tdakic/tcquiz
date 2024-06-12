@quizaccess @quizaccess_tcquiz @javascript
Feature: Test that the teacher can control the flow of a TCQuiz.
  In order to administer a TCQuiz
  As an teacher
  I need to be able to control its flow.

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
        | quiz     | Quiz 2 | Quiz 2 description | C1     | quiz2    | 100   | free       | Yes         | 20           |
    And quiz "Quiz 2" contains the following questions:
      | question | page | maxmark |
      | TF1      | 1    |         |
      | TF2      | 2    |         |

  @javascript
  Scenario: Teacher creates a TCQuiz, starts it and displays the first question. Then they click on End question button
  to stop and display the answer to the first question. Then they click Next to display the second question, but this time
  they wait for the timer to run out. The answer to second question is then displayed. When the teacher clicks Next, the final
  results are displayed.

    # The above background doesn't seem to set the quiz to be a TCQuiz.
    When I am on the "Quiz 2" "quiz activity editing" page logged in as "teacher"
    And I expand all fieldsets
    And I set the field "Administer TCQuiz" to "Yes"
    And I set the field "Default question time" to "20"
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
    And I wait 25 seconds for "The correct answer" to appear

    # The answer to the second question is displayed
    Then I should see "Text of the second question"
    And "Next >>" "button" should be visible
    When I click on "Next >>" "button"

    # The final results are displayed
    Then I should see "Attempts: 0"
    And "End quiz" "button" should be visible
    When I click on "End quiz" "button"

    # I should be on the quiz view page
    Then I should see "Start new quiz session"
