Feature: Serialization

Background:
  Given I have a clean database

Scenario: Creating a new page
  Given a new page is created with title "A"
  And when i retrieve the last created page
  Then the retrieved page has title "A"

Scenario: Creating two new pages and retrieving the first one
  Given a new page is created with title "A"
  And   a new page is created with title "B"
  And when i retrieve the first created page
  Then the retrieved page has title "A"

Scenario: Creating two new pages and retrieving the last one
  Given a new page is created with title "A"
  And   a new page is created with title "B"
  And when i retrieve the last created page
  Then the retrieved page has title "B"

Scenario: Activating a new version
  Given a new page is created with title "A"
  And when i check the number of versions for last created page
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