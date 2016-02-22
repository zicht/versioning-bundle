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


    public static function console($cmd)
    {
        $arg_list = func_get_args();
        $arg_list = array_splice($arg_list, 1);

        if (count($arg_list)) {
            $cmd = vsprintf($cmd, $arg_list);
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
     * @Given /^a new page is created with title "([^"]*)"$/
     */
    public function aNewPageIsCreatedWithTitle($title)
    {
        $this->createdPageIds[] = json_decode(self::console('create --title=%s', $title))->id;
    }

    /**
     * @Given /^when i retrieve the first created page$/
     */
    public function whenIRetrieveTheFirstCreatedPage()
    {
        $result = self::console('retrieve --id=%s', reset($this->createdPageIds));
        $this->retrievedData = json_decode($result);
    }

    /**
     * @Given /^when i retrieve the last created page$/
     */
    public function whenIRetrieveTheLastCreatedPage()
    {
        $result = self::console('retrieve --id=%s', end($this->createdPageIds));
        $this->retrievedData = json_decode($result);
    }


    /**
     * @Then /^the retrieved page has title "([^"]*)"$/
     */
    public function theRetrievedPageHasTitle($title)
    {
        if ($this->retrievedData == null) {
            throw new Exception('There is no retrieved page');
        }

        if (!key_exists('title', $this->retrievedData)) {
            throw new Exception('The retrieved page doesn\'t have a title property');
        }

        if ($this->retrievedData->title != $title) {
            throw new Exception(
                sprintf(
                    'Title %s of the retrieved page doesn\'t match the given the title %s',
                    $this->retrievedData->title,
                    $title
                )
            );
        }
    }

    /**
     * @Given /^when i check the number of versions for last created page$/
     */
    public function whenICheckTheNumberOfVersionsForLastCreatedPage()
    {
        $this->numberOfVersions = json_decode(self::console('get-version-count --id=%s', end($this->createdPageIds)))->count;
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
     * @When i change the title to :arg1
     */
    public function iChangeTheTitleTo($newTitle)
    {
        self::console('change-property --title=%s --property=%s  --value=%s', $this->retrievedData->title, 'title', $newTitle);
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
