Feature: Serialization

Scenario: Creating a new page
  Given a new page with title "A"
  When i save it
  And i retrieve it
  Then I get the page back with title "A"

Scenario: Creating a new version
  Given an existing page with title "A"
  When i change the title to "B"
  And i save it as a new version
  When i retrieve it
  Then the title should be be "A"

Scenario: Activating the new version
  Given an existing page with title "A" with a new version with title "B"
  When I set the active version to title "B"
  When i retrieve it
  Then the title should be be "B"

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