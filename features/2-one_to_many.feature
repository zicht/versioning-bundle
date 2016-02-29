Feature: Versioning - One to Many relations

Background:
  Given I have a clean database

Scenario: Creating a new version with a content item
  Given a new page is created with id 1 and title "1 A"
  And I change the field "introduction" to "2 intro intro" on the page with id 1
  And the page with id 1 has a contentitem with id 1 and title "3 CI-1"
  And the page with id 1 has a contentitem with id 2 and title "4 CI-2"
  When i retrieve the page with id 1
  Then the field "title" of the contentitem with id 1 should have the value "3 CI-1"
  And the number of versions for page with id 1 should be 4
  Then throw error

Scenario: Adding a content item to the original version should not change the other  version
  Given a page exists with id 1, title "A" and a contentitem with id 1 and title "CI title"