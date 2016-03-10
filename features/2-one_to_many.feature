Feature: Versioning - One to Many relations

Background:
  Given I have a clean database

Scenario: Creating a new version with a content item
  Given a new page is created with id 1 and title "1 A"
  And I change the field "introduction" to "2 intro intro" on the page with id 1
  And the page with id 1 has a contentitem with id 1 and title "3 CI-1"
  And the page with id 1 has a contentitem with id 2 and title "4 CI-2"
  When i retrieve the page with id 1
  Then the count of contentitems in the active version of the page with id 1 should be 0
  And the number of versions for page with id 1 should be 4
#  Then throw error

Scenario: Creating a new version with a content item and save as active
  Given a new page is created with id 1 and title "1 A"
  And I change the field "introduction" to "intro intro" on the page with id 1 and save it as the active page
  And the page with id 1 has a contentitem with id 1 and title "3 CI-1" and save it as the active page
  And the page with id 1 has a contentitem with id 2 and title "4 CI-2" and save it as the active page
  Then the number of versions for page with id 1 should be 4
  And the active version for page with id 1 should be 4
  When i retrieve the page with id 1
  Then the count of contentitems in the active version of the page with id 1 should be 2
  And the field "title" of the contentitem with id 1 should have the value "3 CI-1"
  And the field "title" of the contentitem with id 2 should have the value "4 CI-2"
#  And throw error

Scenario: Changing the title on the page shouldn't change the contentitem
  Given a page exists with id 1, title "A" and a contentitem with id 1 and title "CI title"
  When I change the field "title" to "B" on the page with id 1 and save it as the active page
  And i retrieve the page with id 1
  Then the field "title" of the retrieved page has the value "B"
  And the field "title" of the contentitem with id 1 should have the value "CI title"

Scenario: Adding a contentitem in the not active version, shouldn't show
  Given a new page is created with id 1 and title "A"
  And the page with id 1 has a contentitem with id 1 and title "CI title"
  When i retrieve the page with id 1
  Then the count of contentitems in the active version of the page with id 1 should be 0