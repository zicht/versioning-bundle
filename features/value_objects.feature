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

Scenario: Change the title, but not make the new version active
  Given a new page is created with id 1 and title "A"
  And I change the field "title" to "B" on the page with id 1
  Then the number of versions for page with id 1 should be 2
  And the active version for page with id 1 should be 1
  When i retrieve the page with id 1
  And the field "title" of the retrieved page has the value "A"

Scenario: Change the title multiple times, but not make the new version active
  Given a new page is created with id 1 and title "A"
  And I change the field "title" to "B" on the page with id 1
  Then the number of versions for page with id 1 should be 2
  Then I change the field "title" to "C" on the page with id 1
  And I change the field "title" to "D" on the page with id 1
  And I change the field "title" to "E" on the page with id 1
  Then the number of versions for page with id 1 should be 5
  And the active version for page with id 1 should be 1
  When i retrieve the page with id 1
  And the field "title" of the retrieved page has the value "A"
  When I change the active version for the page with id 1 to version 3
  And i retrieve the page with id 1
  Then the field "title" of the retrieved page has the value "C"
#  And throw error

Scenario: Activating a previous version
  Given a new page is created with id 1 and title "A"
  And I change the field "title" to "B" on the page with id 1
  Then the number of versions for page with id 1 should be 2
  When i retrieve the page with id 1
  Then the field "title" of the retrieved page has the value "A"
  And the active version for page with id 1 should be 1
  When I change the active version for the page with id 1 to version 2
  And i retrieve the page with id 1
  Then the active version for page with id 1 should not be 2
  And the active version for page with id 1 should be based on 2
  And the field "title" of the retrieved page has the value "B"
  And the number of versions for page with id 1 should be 3

Scenario: Activating a previous version should empty newer fields
  Given a new page is created with id 1 and title "A"
  And I change the field "title" to "B" on the page with id 1 and save it as the active page
  Then the number of versions for page with id 1 should be 2
  And the active version for page with id 1 should be 2
  Given I change the field "introduction" to "aaa" on the page with id 1 and save it as the active page
  Then the number of versions for page with id 1 should be 3
  And the active version for page with id 1 should be 3
  And i retrieve the page with id 1
  Then the field "title" of the retrieved page has the value "B"
  And the field "introduction" of the retrieved page has the value "aaa"

Scenario: Activating a previous version with other fields
  Given a new page is created with id 1 and title "A"
  And I change the field "title" to "B" on the page with id 1 and save it as the active page
  And I change the field "introduction" to "aaa" on the page with id 1 and save it as the active page
  And I change the field "introduction" to "bbb" on the page with id 1 and save it as the active page
  Then the number of versions for page with id 1 should be 4
  And the active version for page with id 1 should be 4
  When I change the active version for the page with id 1 to version 3
  And i retrieve the page with id 1
  Then the field "title" of the retrieved page has the value "B"
  And the field "introduction" of the retrieved page has the value "aaa"

Scenario: Version juggling ^^
  Given a new page is created with id 1 and title "A"
  And I change the field "title" to "B" on the page with id 1 and save it as the active page
  And I change the field "introduction" to "aaa" on the page with id 1 and save it as the active page
  And I change the field "introduction" to "bbb" on the page with id 1 and save it as the active page
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
  And I change the field "title" to "B" on the page with id 1 and save it as the active page
  And I change the field "introduction" to "bbb" on the page with id 1 and save it as the active page
  And I change the field "foo" to "bar" on the page with id 1 and save it as the active page
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
  And I change the field "title" to "B" on the page with id 1 and save it as the active page
  And I change the field "introduction" to "bbb" on the page with id 1 and save it as the active page
  And I change the field "foo" to "bar" on the page with id 1 and save it as the active page
  When the data of version 2 of page with id 1 has data for the unexisting field "unexisting" in it
  Then the number of versions for page with id 1 should be 4
  When I change the active version for the page with id 1 to version 3
  And i retrieve the page with id 1
  Then the field "unexisting" shouldn't exist in the retrieved page
  And the field "title" of the retrieved page has the value "B"
  And the field "introduction" of the retrieved page has the value "bbb"
  And the field "foo" of the retrieved page has no value

Scenario: Boolean field is stored correctly
  Given a new page is created with id 1 and title "A"
  And I change the field "booleanField" with type "boolean" to "true" on the page with id 1
  When i retrieve the page with id 1
  Then the field "booleanField" of the retrieved page has the value "true" and type "boolean"

Scenario: Integer field is stored correctly
  Given a new page is created with id 1 and title "A"
  And I change the field "integerField" with type "integer" to "123" on the page with id 1
  When i retrieve the page with id 1
  Then the field "integerField" of the retrieved page has the value "123" and type "integer"

Scenario: Boolean and integer fields are restored correctly
  Given a new page is created with id 1 and title "A"
  And I change the field "booleanField" with type "boolean" to "true" on the page with id 1
  And I change the field "integerField" with type "integer" to "123" on the page with id 1
  Then the number of versions for page with id 1 should be 3
  When I change the field "integerField" with type "integer" to "666" on the page with id 1
  And I change the field "booleanField" with type "boolean" to "false" on the page with id 1
  Then the number of versions for page with id 1 should be 5
  When I change the active version for the page with id 1 to version 3
  When i retrieve the page with id 1
  Then the field "integerField" of the retrieved page has the value "123" and type "integer"
  Then the field "booleanField" of the retrieved page has the value "true" and type "boolean"