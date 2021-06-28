@report @report_participation @javascript
Feature: Use the particiaption report to email groups of students
  In order to engage with students based on participation
  As a teacher
  I need to be able to email students who have not participated in an activity

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | student3 | Student   | 3        | student3@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
    And the following config values are set as admin:
      | emailbulkmessaging | 1 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Book" to section "1" and I fill the form with:
      | Name        | Test book name |
      | Description | Test book      |
    And I follow "Test book name"
    And I set the following fields to these values:
      | Chapter title | Test chapter         |
      | Content       | Test chapter content |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test book name"
    And I log out

  Scenario: Email students who have not participated in book
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Course participation" in current page administration
    And I set the field "instanceid" to "Test book name"
    And I set the field "roleid" to "Student"
    And I press "Go"
    And I should see "Yes (1)" in the "Student 1" "table_row"
    And I should see "No" in the "Student 2" "table_row"
    And I should see "No" in the "Student 3" "table_row"
    When I press "Select all 'No'"
    And I choose "Send an email" from the participants page bulk action menu
    And I should see "Send email to 2 people"
    And I set the following fields to these values:
      | Subject | Hi           |
      | Message | Hello world! |
    And I press "Send email to 2 people"
    Then I should see "Email sent to 2 people"

  Scenario: Email students who have not participated in book with carbon copy
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Course participation" in current page administration
    And I set the field "instanceid" to "Test book name"
    And I set the field "roleid" to "Student"
    And I press "Go"
    And I should see "Yes (1)" in the "Student 1" "table_row"
    And I should see "No" in the "Student 2" "table_row"
    And I should see "No" in the "Student 3" "table_row"
    When I press "Select all 'No'"
    And I choose "Send an email" from the participants page bulk action menu
    And I should see "Send email to 2 people"
    And I set the following fields to these values:
      | Subject | Hi           |
      | Message | Hello world! |
    And I click on "Carbon copy" "checkbox"
    And I press "Send email to 2 people"
    Then I should see "Email sent to 3 people"
