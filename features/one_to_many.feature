Feature: Versioning - One to Many relations

#Background:
#  Given I have a clean database

#Scenario: Creating a new version with a content item
#  Given an existing page with title "A"
#  When i add a content item with title "Item of A"
#  Then a content item with title "Item of A" should be present

#Scenario: Adding a content item to the original version
#  Given an existing page with title "A" with a new version with title "B"
#  And I add a content item with title "Item of A"
#  Then a content item with title "Item of A" should be present
#  And the title should be be "A"

#Scenario: Adding a content item to the original version should not change the other  version
#  Given an existing page with title "A" with a new version with title "B"
#  And I add a content item with title "Item of A"
#  And I set the active version to title "B"
#  Then a content item with title "Item of A" should not be present
#  And the title should be be "B"