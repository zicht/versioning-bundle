<?php

use Behat\Behat\Tester\Exception\PendingException;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Context\SnippetAcceptingContext;

/**
 * Features context.
 */
class FeatureContext extends MinkContext implements SnippetAcceptingContext
{
    private $createdPageIds;
    private $retrievedData;
    private $numberOfVersions;

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
                $cmd .= sprintf(' --data="%s:%s"', $key, $value);
            }
        }

        $dir = __DIR__ . '/../../../../../';
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
            throw new Exception('There is no retrieved page');
        }

        if (!key_exists($fieldName, $this->retrievedData)) {
            throw new Exception('The retrieved page doesn\'t have a property named \'' . $fieldName . '\'');
        }

        if ($this->retrievedData[$fieldName] != $expectedValue) {
            throw new Exception(
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
     * @Given /^the field "([^"]*)" of the retrieved page has no value$/
     */
    public function theFieldOfTheRetrievedPageHasNoValue($fieldName)
    {
        if ($this->retrievedData == null) {
            throw new Exception('There is no retrieved page');
        }

        if (!key_exists($fieldName, $this->retrievedData)) {
            throw new Exception('The retrieved page doesn\'t have a property named \'' . $fieldName . '\'');
        }

        if (!is_null($this->retrievedData[$fieldName])) {
            throw new Exception(
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

        if ($retrievedNumberOfVersions != $expectedNumberOfVersions) {
            throw new RuntimeException(sprintf('The retrieved number of versions (%s) doesn\'t match the expected value of %s', $retrievedNumberOfVersions,  $expectedNumberOfVersions));
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

        self::console('inject-data', $id, ['version' => 1, 'data' => '{\"id\":\"1\",\"title\":\"A\",\"introduction\":null}']);
    }
}
