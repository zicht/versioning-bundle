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
  Then the number of versions for page with id 1 should be 1

Scenario: Change the title
  Given a new page is created with id 1 and title "A"
  And I change the field "title" to "B" on the page with id 1
  When i retrieve the page with id 1
  Then the field "title" of the retrieved page has the value "B"
  Then the number of versions for page with id 1 should be 2

Scenario: Activating a previous version
  Given a new page is created with id 1 and title "A"
  And I change the field "title" to "B" on the page with id 1
  Then the number of versions for page with id 1 should be 2
  When I change the active version for the page with id 1 to version 1
  And i retrieve the page with id 1
  Then the field "title" of the retrieved page has the value "A"
  Then the number of versions for page with id 1 should be 3

Scenario: Activating a previous version should empty newer fields
  Given a new page is created with id 1 and title "A"
  And I change the field "title" to "B" on the page with id 1
  Given I change the field "introduction" to "aaa" on the page with id 1
  Then the number of versions for page with id 1 should be 3
  When I change the active version for the page with id 1 to version 1
  And i retrieve the page with id 1
  Then the field "title" of the retrieved page has the value "A"
  And the field "introduction" of the retrieved page has no value

Scenario: Activating a previous version with other fields
  Given a new page is created with id 1 and title "A"
  And I change the field "title" to "B" on the page with id 1
  And I change the field "introduction" to "aaa" on the page with id 1
  And I change the field "introduction" to "bbb" on the page with id 1
  When I change the active version for the page with id 1 to version 3
  And i retrieve the page with id 1
  Then the field "title" of the retrieved page has the value "B"
  And the field "introduction" of the retrieved page has the value "aaa"

Scenario: Version juggling ^^
  Given a new page is created with id 1 and title "A"
  And I change the field "title" to "B" on the page with id 1
  And I change the field "introduction" to "aaa" on the page with id 1
  And I change the field "introduction" to "bbb" on the page with id 1
  Then the number of versions for page with id 1 should be 4
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

Scenario: Back to entity with less fields
  Given a new page is created with id 1 and title "A" with an old schema
  And I change the field "title" to "B" on the page with id 1
  And I change the field "introduction" to "bbb" on the page with id 1
  And I change the field "foo" to "bar" on the page with id 1
  When i retrieve the page with id 1
  Then the field "title" of the retrieved page has the value "B"
  And the field "introduction" of the retrieved page has the value "bbb"
  And the field "foo" of the retrieved page has the value "bar"
  When I change the active version for the page with id 1 to version 1
  And i retrieve the page with id 1
  Then the field "title" of the retrieved page has the value "A"
  And the field "introduction" of the retrieved page has no value
  And the field "foo" of the retrieved page has no value

Scenario: Unknown fields shouldn't be a problem
  Given a new page is created with id 1 and title "A"
  And I change the field "title" to "B" on the page with id 1
  And I change the field "introduction" to "bbb" on the page with id 1
  And I change the field "foo" to "bar" on the page with id 1
  When the data of version 2 of page with id 1 has data for the unexisting field "unexisting" in it
  Then the number of versions for page with id 1 should be 4
  When I change the active version for the page with id 1 to version 3
  And i retrieve the page with id 1
  Then the field "unexisting" shouldn't exist in the retrieved page
  And the field "title" of the retrieved page has the value "B"
  And the field "introduction" of the retrieved page has the value "bbb"
  And the field "foo" of the retrieved page has no value