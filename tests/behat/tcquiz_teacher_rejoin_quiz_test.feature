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
    And the following "activities" exist:
        | activity | name   | intro              | course | idnumber | grade | navmethod  | tcqrequired | questiontime |
        | quiz     | Quiz 2 | Quiz 2 description | C1     | quiz2    | 100   | free       | 1           | 100          |
    And quiz "Quiz 2" contains the following questions:
      | question | page | maxmark |
      | TF1      | 1    |         |

  @javascript
  Scenario: Teacher tries rejoining the tcquiz when: the teacher just started the quiz, the teacher is displaying a
  question, the teacher is displaying the answers to a question, the teacher is displaying the final results, the
  quiz is closed.

    # The above background doesn't seem to set the quiz to be a TCQuiz.
    When I am on the "Quiz 2" "quiz activity editing" page logged in as "teacher"
    And I expand all fieldsets
    And I set the field "Administer TCQuiz" to "Yes"
    And I set the field "Default question time" to "100"
    When I click on "Save and display" "button"

    # Start new TCQ session.
    Then I should see "Start new quiz session"
    And I set the field with xpath "//input[@type='text' and @id='id_joincode']" to "teachercode7"
    When I click on "Start new quiz session" "button"
    Then I should see "Waiting for students to connect"
    And I log out

    # Assume the teacher crashed and they want to reconnect.
    When I am on the "Quiz 2" "mod_quiz > View" page logged in as "teacher"
    Then "Rejoin" "button" should be visible
    And I should see "teachercode7"
    When I click on "Rejoin" "button"
    Then I should see "Waiting for students to connect"
    And "Next >>" "button" should be visible

    # Display the first question.
    When I click on "Next >>" "button"
    Then I should see "Text of the first question"
    And I log out

    # Crash again - but this time the teacher should reconnect to question
    When I am on the "Quiz 2" "mod_quiz > View" page logged in as "teacher"
    Then "Rejoin" "button" should be visible
    And I should see "teachercode7"
    When I click on "Rejoin" "button"
    Then I should see "Text of the first question"
    And "End question" "button" should be visible

    # End the question. Make sure the results are displayed and crash again.
    When I click on "End question" "button"
    # The answer to the first question is displayed
    Then I should see "Text of the first question"
    And I should see "The correct answer is 'True'"
    And I log out
    # Crash.
    When I am on the "Quiz 2" "mod_quiz > View" page logged in as "teacher"
    Then "Rejoin" "button" should be visible
    And I should see "teachercode7"
    When I click on "Rejoin" "button"
    # Still displaying the answer to the first question.
    Then I should see "Text of the first question"
    And I should see "The correct answer is 'True'"

    # This should take the teacher to the final results.
    # There is only one question in the quiz.
    When I click on "Next >>" "button"
    Then I should see "Attempts: 0"
    And "End quiz" "button" should be visible
    And I log out

    # Crash.
    When I am on the "Quiz 2" "mod_quiz > View" page logged in as "teacher"
    Then "Rejoin" "button" should be visible
    And I should see "teachercode7"
    When I click on "Rejoin" "button"
    # Should be back to final results page.
    Then I should see "Attempts: 0"
    And "End quiz" "button" should be visible

    # End the quiz. Make sure that you can't rejoin.
    When I click on "End quiz" "button"
    Then I should see "Start new quiz session"
    And "Rejoin" "button" should not be visible
