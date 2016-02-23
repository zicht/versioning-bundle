Feature: Versioning - value objects

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

Scenario: Change the title
  Given a new page is created with id 1 and title "A"
  And I change the title to "B" on the page with id 1
  When i retrieve the page with id 1
  Then the retrieved page has title "B"
  And i check the number of versions for the page with id 1
  Then the number of versions is 2

Scenario: Activating a previous version
  Given an existing page with id 1 with title "A" with a new version with title "B"
  When i check the number of versions for the page with id 1
  Then the number of versions is 2
  When I change the active version for the page with id 1 to version 1
  And i retrieve the page with id 1
  Then the retrieved page has title "A"

#Scenario: Checking other fields are filled in
#Scenario: Checking other fields are not filled in