Feature: Versioning - value objects

Background:
  Given I have a clean database

Scenario: Creating a new page
  Given a new page is created with id 1 and title "A"
  When i retrieve the page with id 1
  Then the field "title" of the retrieved page has the value "A"

Scenario: Creating two new pages and retrieving the first one
  Given a new page is created with id 1 and title "A"
  Given a new page is created with id 2 and title "B"
  When i retrieve the page with id 1
  Then the field "title" of the retrieved page has the value "A"

Scenario: Creating two new pages and retrieving the second one
  Given a new page is created with id 1 and title "A"
  Given a new page is created with id 2 and title "B"
  When i retrieve the page with id 2
  Then the field "title" of the retrieved page has the value "B"

Scenario: Just a single version
  Given a new page is created with id 1 and title "A"
  When i check the number of versions for the page with id 1
  Then the number of versions is 1

Scenario: Change the title
  Given a new page is created with id 1 and title "A"
  And I change the field "title" to "B" on the page with id 1
  When i retrieve the page with id 1
  Then the field "title" of the retrieved page has the value "B"
  And i check the number of versions for the page with id 1
  Then the number of versions is 2

Scenario: Activating a previous version
  Given an existing page with id 1 with title "A" with a new version with title "B"
  When i check the number of versions for the page with id 1
  Then the number of versions is 2
  When I change the active version for the page with id 1 to version 1
  And i retrieve the page with id 1
  Then the field "title" of the retrieved page has the value "A"
  When i check the number of versions for the page with id 1
  Then the number of versions is 3

Scenario: Activating a previous version should empty newer fields
  Given an existing page with id 1 with title "A" with a new version with title "B"
  Given I change the field "introduction" to "aaa" on the page with id 1
  When i check the number of versions for the page with id 1
  Then the number of versions is 3
  When I change the active version for the page with id 1 to version 1
  And i retrieve the page with id 1
  Then the field "title" of the retrieved page has the value "A"
  And the field "introduction" of the retrieved page has no value

Scenario: Activating a previous version with other fields
  Given an existing page with id 1 with title "A" with a new version with title "B"
  And I change the field "introduction" to "aaa" on the page with id 1
  And I change the field "introduction" to "bbb" on the page with id 1
  When I change the active version for the page with id 1 to version 3
  And i retrieve the page with id 1
  Then the field "title" of the retrieved page has the value "B"
  And the field "introduction" of the retrieved page has the value "aaa"

Scenario: Version swapping
  Given an existing page with id 1 with title "A" with a new version with title "B"
  And I change the field "introduction" to "aaa" on the page with id 1
  And I change the field "introduction" to "bbb" on the page with id 1
  When i check the number of versions for the page with id 1
  Then the number of versions is 4
  When I change the active version for the page with id 1 to version 1
  And i retrieve the page with id 1
  Then the field "title" of the retrieved page has the value "A"
  And the field "introduction" of the retrieved page has no value
  When I change the active version for the page with id 1 to version 4
  And i retrieve the page with id 1
  Then the field "title" of the retrieved page has the value "B"
  And the field "introduction" of the retrieved page has the value "bbb"
  When I change the active version for the page with id 1 to version 3
  And i retrieve the page with id 1
  Then the field "title" of the retrieved page has the value "B"
  And the field "introduction" of the retrieved page has the value "aaa"
