<?php

use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Context\SnippetAcceptingContext;

/**
 * Features context.
 */
class FeatureContext extends MinkContext implements SnippetAcceptingContext
{
    private $createdPageIds;
    private $retrievedData;

    /**
     * FeatureContext constructor.
     */
    public function __construct()
    {
        $this->createdPageIds = [];
    }


    /**
     * Helper method to communicate with the console command
     *
     * @see Zicht\Bundle\VersioningBundle\Command\ClientCommand
     * @param string $cmd the actual command to do
     * @param null|integer $id optional id
     * @param null|mixed $data the data, wrapped in an array
     * @return string
     */
    public static function console($cmd, $id = null, $data = null)
    {
        if ($id) {
            $cmd = $cmd . ' --id=' . $id;
        }

        if ($data) {
            foreach ($data as $key => $value) {
                $cmd .= sprintf(' --data="%s:%s"', $key, addslashes($value));
            }
        }

        $dir = __DIR__ . '/../../';

        //this is added when the bundle is placed inside the /vendor/ folder
        if (strpos(__DIR__, 'vendor') > -1) {
            $dir .= '../../../';
        }

        $rel = (new \Symfony\Component\Filesystem\Filesystem())->makePathRelative(realpath($dir . '/app'), realpath(getcwd()));
        $cmd = "(php {$rel}console -v zicht:versioning:test-util $cmd)";
        echo $cmd;

        $output = [];
        $exitcode = 0;
        exec($cmd, $output, $exitcode);
        if (0 !== $exitcode) {
            throw new UnexpectedValueException("Console command exited with exit code {$exitcode}");
        }
        return join('', $output);
    }

    /**
     * @Given /^I have a clean database$/
     */
    public function iHaveACleanDatabase()
    {
        // clean database before we run a new scenario
        self::console('clear-test-records');
    }

    /**
     * @Given /^a new page is created with id (\d+) and title "([^"]*)"$/
     */
    public function aNewPageIsCreatedWithIdAndTitle($id, $title)
    {
        self::console('create', $id, ['title' => $title]);
    }

    /**
     * @When /^i retrieve the page with id (\d+)$/
     */
    public function iRetrieveThePageWithId($id)
    {
        $result = self::console('retrieve', $id);
        $this->retrievedData = json_decode($result, true);
    }

    /**
     * @Then /^the field "([^"]*)" of the retrieved page has the value "([^"]*)"$/
     */
    public function theFieldOfTheRetrievedPageHasTheValue($fieldName, $expectedValue)
    {
        if ($this->retrievedData == null) {
            throw new RuntimeException('There is no retrieved page');
        }

        if (!key_exists($fieldName, $this->retrievedData)) {
            throw new RuntimeException('The retrieved page doesn\'t have a property named \'' . $fieldName . '\'');
        }

        if ($this->retrievedData[$fieldName] !== $expectedValue) {
            throw new RuntimeException(
                sprintf(
                    'The value of the field %s of the retrieved page is \'%s\' and that doesn\'t match the expected value \'%s\'',
                    $fieldName,
                    $this->retrievedData[$fieldName],
                    $expectedValue
                )
            );
        }
    }

    /**
     * @Then /^the field "([^"]*)" of the retrieved page has the value "([^"]*)" and type "([^"]*)"$/
     */
    public function theFieldOfTheRetrievedPageHasTheValueAndType($fieldName, $expectedValue, $expectedType)
    {
        switch($expectedType) {
            case 'boolean':
                $expectedValue = boolval($expectedValue);
                break;

            case 'integer':
                $expectedValue = intval($expectedValue);
                break;
        }

        $this->theFieldOfTheRetrievedPageHasTheValue($fieldName, $expectedValue);

        if (gettype($this->retrievedData[$fieldName]) !== $expectedType) {
            throw new RuntimeException(
                sprintf(
                    'The type of the field %s of the retrieved page doesn\'t match the expected type %s',
                    $fieldName,
                    $expectedType
                )
            );
        }
    }

    /**
     * @Given /^the field "([^"]*)" of the retrieved page has no value$/
     */
    public function theFieldOfTheRetrievedPageHasNoValue($fieldName)
    {
        if ($this->retrievedData == null) {
            throw new RuntimeException('There is no retrieved page');
        }

        if (!key_exists($fieldName, $this->retrievedData)) {
            throw new RuntimeException('The retrieved page doesn\'t have a property named \'' . $fieldName . '\'');
        }

        if (!is_null($this->retrievedData[$fieldName])) {
            throw new RuntimeException(
                sprintf(
                    'The value of the field %s of the retrieved page is \'%s\' and it should have been null',
                    $fieldName,
                    $this->retrievedData[$fieldName]
                )
            );
        }
    }

    /**
     * @Then /^the number of versions for page with id (\d+) should be (\d+)$/
     */
    public function theNumberOfVersionsForPageWithIdShouldBe($id, $expectedNumberOfVersions)
    {
        $retrievedNumberOfVersions = json_decode(self::console('get-version-count', $id))->count;

        if ($retrievedNumberOfVersions !== intval($expectedNumberOfVersions)) {
            throw new RuntimeException(
                sprintf(
                    'The retrieved number of versions (%s) doesn\'t match the expected value of %s',
                    $retrievedNumberOfVersions,
                    $expectedNumberOfVersions
                )
            );
        }
    }

    /**
     * @Given /^I change the field "([^"]*)" to "([^"]*)" on the page with id (\d+)$/
     */
    public function iChangeTheFieldToOnThePageWithId($fieldName, $value, $id)
    {
        self::console('change-property', $id, ['property' => $fieldName, 'value' => $value]);
    }

    /**
     * @Given /^I change the field "([^"]*)" to "([^"]*)" on the page with id (\d+) and save it as the active page$/
     */
    public function iChangeTheFieldToOnThePageWithIdAndSaveIsAsTheActivePage($fieldName, $value, $id)
    {
        self::console('change-property', $id, ['property' => $fieldName, 'value' => $value, 'save-as-active' => true]);
    }

    /**
     * @Given /^I change the field "([^"]*)" with type "([^"]*)" to "([^"]*)" on the page with id (\d+)$/
     */
    public function iChangeTheFieldWithTypeToOnThePageWithId($fieldName, $fieldType, $value, $id)
    {
        switch($fieldType) {
            case 'boolean':
                $value = boolval($value);
                break;

            case 'integer':
                $value = intval($value);
                break;
        }

        $this->iChangeTheFieldToOnThePageWithIdAndSaveIsAsTheActivePage($fieldName, $value, $id);
    }

    /**
     * @Given /^an existing page with id (\d+) with title "([^"]*)" with a new version with title "([^"]*)"$/
     */
    public function anExistingPageWithIdWithTitleWithANewVersionWithTitle($id, $oldTitle, $newTitle)
    {
        $this->aNewPageIsCreatedWithIdAndTitle($id, $oldTitle);
        $this->iChangeTheFieldToOnThePageWithId('title', $newTitle, $id);
    }

    /**
     * @When /^I change the active version for the page with id (\d+) to version (\d+)$/
     */
    public function iChangeTheActiveVersionForThePageWithIdToVersion($id, $versionNumber)
    {
        self::console('set-active', $id, ['version' => $versionNumber]);
    }

    /**
     * @Given /^a new page is created with id (\d+) and title "([^"]*)" with an old schema$/
     */
    public function aNewPageIsCreatedWithIdAndTitleWithAnOldSchema($id, $title)
    {
        $this->aNewPageIsCreatedWithIdAndTitle($id, $title);

        $jsonData = json_encode(['testingId' => $id, 'title' => 'A', 'introduction' => null], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

        self::console('inject-data', $id, ['version' => 1, 'data' => $jsonData]);
    }

    /**
     * @When /^the data of version (\d+) of page with id (\d+) has data for the unexisting field "([^"]*)" in it$/
     */
    public function theDataOfVersionOfPageWithIdHasDataForTheUnexistingFieldInIt($version, $id, $unexistingFieldName)
    {
        $jsonData = json_decode(self::console('retrieve-version', $id, ['version' => $version]), true);
        $jsonData[$unexistingFieldName] = 'non existing field value';
        $jsonData = json_encode($jsonData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

        self::console('inject-data', $id, ['version' => $version, 'data' => $jsonData]);
    }

    /**
     * @Then /^the field "([^"]*)" shouldn't exist in the retrieved page$/
     */
    public function theFieldShouldnTExistInTheRetrievedPage($fieldName)
    {
        if (key_exists($fieldName, $this->retrievedData)) {
            throw new RuntimeException(sprintf('The field %s shouldn\'t be present', $fieldName));
        }
    }

/**
     * @Then /^the active version for page with id (\d+) should be (\d+)$/
     */
    public function theActiveVersionForPageWithIdShouldBe($id, $expectedVersionNumber)
    {
        $activeVersion = $this->getActiveVersion($id);
        $retrievedActiveVersionNumber = $activeVersion->versionNumber;

        if ($retrievedActiveVersionNumber !== intval($expectedVersionNumber)) {
            throw new RuntimeException(
                sprintf(
                    'The retrieved active version number (%s) doesn\'t match the expected version number %s',
                    $retrievedActiveVersionNumber,
                    $expectedVersionNumber
                )
            );
        }
    }

    /**
     * @param $id
     * @return \stdClass | null
     */
    private function getActiveVersion($id)
    {
        return json_decode(self::console('get-active-version', $id));
    }

    /**
     * @Given /^throw error$/
     */
    public function throwError()
    {
        throw new RuntimeException('Thrown error to kill Behat');
    }

    /**
     * @Then /^the active version for page with id (\d+) should not be (\d+)$/
     */
    public function theActiveVersionForPageWithIdShouldNotBe($id, $notExpectedVersionNumber)
    {
        $activeVersion = $this->getActiveVersion($id);
        $retrievedActiveVersionNumber = $activeVersion->versionNumber;

        if ($retrievedActiveVersionNumber === intval($notExpectedVersionNumber)) {
            throw new RuntimeException(
                sprintf(
                    'The retrieved active version number (%s) is the unexpected version number %s',
                    $retrievedActiveVersionNumber,
                    $notExpectedVersionNumber
                )
            );
        }
    }

    /**
     * @Given /^the active version for page with id (\d+) should be based on (\d+)$/
     */
    public function theActiveVersionForPageWithIdShouldBeBasedOn($id, $expectedBasedOnId)
    {
        $activeVersion = $this->getActiveVersion($id);
        $retrievedBasedOnId = $activeVersion->basedOnVersion;

        if ($retrievedBasedOnId !== intval($expectedBasedOnId)) {
            throw new RuntimeException(
                sprintf(
                    'The retrieved based on version number (%s) is NOT the expected version number %s',
                    $retrievedBasedOnId,
                    $expectedBasedOnId
                )
            );
        }
    }

    /**
     * @Given /^I have 3 pages with titles "([^"]*)", "([^"]*)", "([^"]*)"$/
     */
    public function iHavePagesWithTitles($title1, $title2, $title3)
    {
        $this->aNewPageIsCreatedWithIdAndTitle(1, $title1);
        $this->aNewPageIsCreatedWithIdAndTitle(2, $title2);
        $this->aNewPageIsCreatedWithIdAndTitle(3, $title3);
    }

    /**
     * @Given /^I have a page with (\d+) versions where version (\d+) is active$/
     */
    public function iHaveAPageWithVersionsWhereVersionIsActive($numberOfVersions, $activeVersion)
    {
        $id = 1;

        for($i = 0; $i < $numberOfVersions; $i++) {
            $title = chr(65 + $i);

            if ($i == 0) {
                $this->aNewPageIsCreatedWithIdAndTitle($id, $title);
            } else {
                if ($activeVersion === $i) {
                    $this->iChangeTheFieldToOnThePageWithIdAndSaveIsAsTheActivePage("title", $title, $id);
                } else {
                    $this->iChangeTheFieldToOnThePageWithId("title", $title, $id);
                }
            }
        }
    }

    /**
     * @When /^I change the field "([^"]*)" to "([^"]*)" on version (\d+) of page with id (\d+)$/
     */
    public function iChangeTheFieldToOnVersionOfPageWithId($fieldName, $value, $version, $id)
    {
        self::console('change-property', $id, ['property' => $fieldName, 'value' => $value, 'version' => $version]);
    }

/**
     * @Given /^i retrieve the version based on version (\d+) of the page with id (\d+)$/
     */
    public function iRetrieveTheVersionBasedOnVersionOfThePageWithId($basedOnVersion, $id)
    {
        $this->retrievedData = json_decode(self::console('retrieve-based-on-version', $id, ['based-on-version' => $basedOnVersion]), true);
    }

    /**
     * @Given /^the page with id (\d+) has a contentitem with id (\d+) and title "([^"]*)"$/
     */
    public function thePageWithIdHasAContentitemWithIdAndTitle($id, $contentItemId, $contentItemTitle)
    {
        self::console('create-content-item', $id, ['id' => $contentItemId, 'title' => $contentItemTitle]);
    }

    /**
     * @Given /^the page with id (\d+) has a contentitem with id (\d+) and title "([^"]*)" and save it as the active page$/
     */
    public function thePageWithIdHasAContentitemWithIdAndTitleAndSaveItAsTheActivePage($id, $contentItemId, $contentItemTitle)
    {
        self::console('create-content-item', $id, ['id' => $contentItemId, 'title' => $contentItemTitle, 'save-as-active' => true]);
    }

    /**
     * @When /^i add another one to many entity with id (\d+) and title "([^"]*)" to page with id (\d+)$/
     */
    public function iAddAnotherOneToManyEntityWithIdAndTitleToPageWithId($entityId, $entityTitle, $pageId)
    {
        self::console('create-other-otmr', $pageId, ['id' => $entityId, 'title' => $entityTitle]);
    }

    /**
     * @Then /^the field "([^"]*)" of the contentitem with id (\d+) should have the value "([^"]*)"$/
     */
    public function theFieldOfTheContentitemWithIdShouldHaveTheValue($fieldName, $contentItemId, $expectedValue)
    {
        $contentItems = $this->retrievedData['contentItems'];

        if (empty($contentItems)) {
            throw new RuntimeException('There are no contentitems');
        }

        $found = false;

        foreach($contentItems as $ci) {
            if ($ci['testingId'] === intval($contentItemId)) {
                $found = true;

                if (!key_exists($fieldName, $ci)) {
                    throw new RuntimeException(
                        'The retrieved contentitem doesn\'t have a property named \'' . $fieldName . '\''
                    );
                }

                if ($ci[$fieldName] !== $expectedValue) {
                    throw new RuntimeException(
                        sprintf(
                            'The value of the field %s of the contentitem is \'%s\' and that doesn\'t match the expected value \'%s\'',
                            $fieldName,
                            $ci[$fieldName],
                            $expectedValue
                        )
                    );
                }
            }
        }

        if (!$found) {
            throw new RuntimeException(sprintf('The contentitem with id %d could not be found', $contentItemId));
        }
    }

    /**
     * @Given /^a page exists with id (\d+), title "([^"]*)" and a contentitem with id (\d+) and title "([^"]*)"$/
     */
    public function aPageExistsWithIdTitleAndAContentitemWithIdAndTitle($id, $title, $ci_id, $ci_title)
    {
        $this->aNewPageIsCreatedWithIdAndTitle($id, $title);
        $this->thePageWithIdHasAContentitemWithIdAndTitleAndSaveItAsTheActivePage($id, $ci_id, $ci_title);
    }

    /**
     * @Then /^the count of contentitems in the active version of the page with id (\d+) should be (\d+)$/
     */
    public function theCountOfContentitemsInTheActiveVersionOfThePageWithIdShouldBe($id, $expectedNumberOfContentItems)
    {
        $contentItems = $this->retrievedData['contentItems'];

        if (count($contentItems) != $expectedNumberOfContentItems) {
            throw new RuntimeException(sprintf('The page with id %d has a different count contentitems [%d] than expected [%d]', $id, count($contentItems), $expectedNumberOfContentItems));
        }
    }

    /**
     * @Given /^the count of other ony to many entities of the page with id (\d+) should be (\d+)$/
     */
    public function theCountOfOtherOnyToManyEntitiesOfThePageWithIdShouldBe($id, $expectedNumber)
    {
        $items = $this->retrievedData['otherOneToManyRelations'];

        if (count($items) != $expectedNumber) {
            throw new RuntimeException(sprintf('The page with id %d has a different count one to many entities [%d] than expected [%d]', $id, count($items), $expectedNumber));
        }
    }

    /**
     * @Given /^i add a nested contentitem with id (\d+) and title "([^"]*)" and a child contentitem with id (\d+) and title "([^"]*)" to page with id (\d+)$/
     */
    public function iAddANestedContentitemWithIdAndTitleAndAChildContentitemWithIdAndTitleToPageWithId(
        $nestedContentItemId,
        $nestedContentItemTitle,
        $childNestedContentItemId,
        $childNestedContentItemTitle,
        $pageId
    ) {
        self::console('create-nested-contenitem', $pageId, ['nestedContentItemId' => $nestedContentItemId, 'nestedContentItemTitle' => $nestedContentItemTitle, 'childNestedContentItemId' => $childNestedContentItemId, 'childNestedContentItemTitle' => $childNestedContentItemTitle, 'save-as-active' => true]);
    }
}
