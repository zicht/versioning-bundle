Feature: Serialization

Background:
  Given I have a clean database

Scenario: Creating a new page
  Given a new page is created with id 1 and title "A"
  When i retrieve the page with id 1
  Then the retrieved page has title "A"

Scenario: Creating two new pages and retrieving the first one
  Given a new page is created with id 1 and title "A"
  Given a new page is created with id 2 and title "B"
  When i retrieve the page with id 1
  Then the retrieved page has title "A"

Scenario: Creating two new pages and retrieving the second one
  Given a new page is created with id 1 and title "A"
  Given a new page is created with id 2 and title "B"
  When i retrieve the page with id 2
  Then the retrieved page has title "B"

Scenario: Just a single version
  Given a new page is created with id 1 and title "A"
  When i check the number of versions for the page with id 1
  Then the number of versions is 1

Scenario: Just a single version
  Given a new page is created with id 1 and title "A"
  And I change the title to "B" on the page with id 1
  When i check the number of versions for the page with id 1
  Then the number of versions is 2

Scenario: Activating the new version
  Given an existing page with title "A" with a new version with title "B"
  When I set the active version to title "B"
  And when i retrieve a page with title "B"
  Then the retrieved page has title "B"

Scenario: Creating a new version with a content item
  Given an existing page with title "A"
  When i add a content item with title "Item of A"
  Then a content item with title "Item of A" should be present

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