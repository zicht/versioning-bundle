Feature: Serialization

Background:
  Given I have a clean database

Scenario: Creating a new page
  Given a new page is created with title "A"
  And when i retrieve a page with title "A"
  Then the retrieved page has title "A"

Scenario: Creating two new pages and retrieving the first one
  Given a new page is created with title "A"
  And   a new page is created with title "B"
  And when i retrieve a page with title "A"
  Then the retrieved page has title "A"

Scenario: Creating a new version
  Given an existing page with title "A"
  When i change the title to "B"
  And i save it as a new version
  And when i retrieve a page with title "A"
  Then the retrieved page has title "A"

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