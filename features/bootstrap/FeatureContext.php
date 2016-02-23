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

    /** @BeforeSuite */
    public static function beforeSuite($scope)
    {
        // clean database after everything is done
//        self::console('clear-test-records');

        //TODO should we also remove the table at the @AfterSuite? And add it at @BeforeSuite?
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

        if ($this->retrievedData[$fieldName] != $expectedValue && !(is_null($this->retrievedData[$fieldName]) && $expectedValue === 'NULL')) {
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
     * @When /^i check the number of versions for the page with id (\d+)$/
     */
    public function iCheckTheNumberOfVersionsForThePageWithId($id)
    {
        $this->numberOfVersions = json_decode(self::console('get-version-count', $id))->count;
    }

    /**
     * @Then /^the number of versions is (\d+)$/
     */
    public function theNumberOfVersionsIs($expectedNumberOfVersions)
    {
        if ($this->numberOfVersions != $expectedNumberOfVersions) {
            throw new RuntimeException(sprintf('The retrieved number of versions (%s) doesn\'t match the expected value of %s', $this->numberOfVersions,  $expectedNumberOfVersions));
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
     * @When i add a content item with title :arg1
     */
    public function iAddAContentItemWithTitle($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Then a content item with title :arg1 should be present
     */
    public function aContentItemWithTitleShouldBePresent($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Given I add a content item with title :arg1
     */
    public function iAddAContentItemWithTitle2($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Then a content item with title :arg1 should not be present
     */
    public function aContentItemWithTitleShouldNotBePresent($arg1)
    {
        throw new PendingException();
    }
}
