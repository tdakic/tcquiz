@quizaccess @quizaccess_tcquiz @javascript
Feature: Test that the student needs the right code to join a TCQuiz
  In order to join a TCQuiz
  As an student
  I need to know the code set up by the teacher.

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

  @javascript
  Scenario: Teacher creates a TCQuiz and starts it. The student can't see the question if they don't know the code.
    Given I am on the "Course 1" "Course" page logged in as "teacher"
    And I turn editing mode on
    And I add a "Quiz" to section "1" and I fill the form with:
      | Name                  | TCQuiz                        |
      | Description           | This quiz is a TCQuiz         |
      | Administer TCQuiz     | Yes                           |
      | Default question time | 60                            |
    And I add a "True/False" question to the "TCQuiz" quiz with:
      | Question name                      | First question              |
      | Question text                      | Is this the first question? |
      | Correct answer                     | True                        |
    And I log out

    # Student tries to join, but the quiz is not running.
    When I am on the "TCQuiz" "mod_quiz > View" page logged in as "student"
    Then I should see "Wait until your teacher gives you the code."
    And "Join quiz" "button" should be visible
    And I set the field with xpath "//input[@type='text' and @id='id_joincode']" to "mycode"
    When I click on "Join quiz" "button"
    Then I should see "-Wrong join code. Try again."
    And I log out

    # Teacher sets the joincode.
    When I am on the "TCQuiz" "mod_quiz > View" page logged in as "teacher"
    Then I should see "Start new quiz session"
    And "Start new quiz session" "button" should be visible
    And I set the field with xpath "//input[@type='text' and @id='id_joincode']" to "teachercode"
    When I click on "Start new quiz session" "button"
    Then I should see "Waiting for students to connect"
    And I log out

    # Student tries to join with the wrong code.
    When I am on the "TCQuiz" "mod_quiz > View" page logged in as "student"
    Then I should see "Wait until your teacher gives you the code."
    And "Join quiz" "button" should be visible
    And I set the field with xpath "//input[@type='text' and @id='id_joincode']" to "mycode"
    When I click on "Join quiz" "button"
    Then I should see "-Wrong join code. Try again."

    # Student tries to join with the right code.
    When I set the field with xpath "//input[@type='text' and @id='id_joincode']" to "teachercode"
    When I click on "Join quiz" "button"
    Then I should see "Waiting for the first question to be sent"
    And I log out

    # Teacher's view should see that one student joined.
    When I am on the "TCQuiz" "mod_quiz > View" page logged in as "teacher"
    Then I should see "Available session"
    And "Rejoin" "button" should be visible
    When I click on "Rejoin" "button"
    Then I should see "Waiting for students to connect"
    And I should see "Number of connected students 1"
    When I click on "Next >>" "button"
    Then I should see "Is this the first question?"
    And I log out

    # Student rejoins the quiz to see the first question.
    When I am on the "TCQuiz" "mod_quiz > View" page logged in as "student"
    Then I should see "Wait until your teacher gives you the code."
    And "Join quiz" "button" should be visible
    And I set the field with xpath "//input[@type='text' and @id='id_joincode']" to "teachercode"
    When I click on "Join quiz" "button"
    Then I should see "Is this the first question?"
