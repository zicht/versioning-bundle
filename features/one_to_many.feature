Feature: Versioning - One to Many relations

Background:
  Given I have a clean database

Scenario: Creating a new version with a content item
  Given a new page is created with id 1 and title "A"
  And I change the field "introduction" to "sdkjdfskj" on the page with id 1
  And the page with id 1 has a contentitem with id 1 and title "C1"
  And the page with id 1 has a contentitem with id 2 and title "C2"
  When i retrieve the page with id 1
#  Then the field "title" of the contentitem with id 1 should have the value "C1"
  And the number of versions for page with id 1 should be 4
  Then throw error

Scenario: Adding a content item to the original version
  Given an existing page with title "A" with a new version with title "B"
  And I add a content item with title "Item of A"
  Then a content item with title "Item of A" should be present
  And the title should be be "A"

Scenario: Adding a content item to the original version should not change the other  version
  Given an existing page with title "A" with a new version with title "B"
  And I add a content item with title "Item of A"
  And I set the active version to title "B"
  Then a content item with title "Item of A" should not be present
  And the title should be be "B"