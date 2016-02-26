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
        $debug = false;
        if ($cmd == 'create') {
//            $debug = true;
        }

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

        if ($debug) {
            var_dump($cmd);
            exit;
        }

        $cmd = "(cd $dir; php app/console -v zicht:versioning:client $cmd)";
        $result = shell_exec($cmd);
        return $result;
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

        $this->iChangeTheFieldToOnThePageWithId($fieldName, $value, $id);
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
        self::console('set-active --id=%d', $id, ['version' => $versionNumber]);
    }

    /**
     * @Given /^a new page is created with id (\d+) and title "([^"]*)" with an old schema$/
     */
    public function aNewPageIsCreatedWithIdAndTitleWithAnOldSchema($id, $title)
    {
        $this->aNewPageIsCreatedWithIdAndTitle($id, $title);

        $jsonData = json_encode(['id' => $id, 'title' => 'A', 'introduction' => null], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

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
}
