@core @core_user
Feature: Edit user email
  In order to update my profile
  As a student
  I need to be able to edit my email

  Background:
    Given the following "users" exist:
      | username  | firstname | lastname    | email                   |
      | student1  | Student   | ONE         | student.one@example.com |
    And the following config values are set as admin:
      | config              | value               |
      | allowemailaddresses | moodle.example.com |

  Scenario: Verify I can edit any other fields in my profile without changing my email (even if my email got a disallowed address domain).
    Given I log in as "student1"
    When I follow "Profile" in the user menu
    And I click on "Edit profile" "link" in the "region-main" "region"
    And I set the following fields to these values:
        | City/town | Perth |
    And I click on "Update profile" "button"
    Then I should see "Changes saved"

  Scenario: Verify I cannot change my email to a disallowed address domain.
    Given I log in as "student1"
    When I follow "Profile" in the user menu
    And I click on "Edit profile" "link" in the "region-main" "region"
    And I set the following fields to these values:
        | Email address | student.one@bad.example.com |
    And I click on "Update profile" "button"
    Then I should not see "Changes saved"

  Scenario: Verify I can change my email to an allowed address domain.
    Given I log in as "student1"
    When I follow "Profile" in the user menu
    And I click on "Edit profile" "link" in the "region-main" "region"
    And I set the following fields to these values:
        | Email address | student.one@moodle.example.com |
    And I click on "Update profile" "button"
    Then I should see "You requested a change of email address"
