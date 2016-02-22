<?php

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Context\Context;
use Behat\Testwork\Hook\Scope\AfterSuiteScope;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;

/**
 * Features context.
 */
class FeatureContext extends MinkContext implements SnippetAcceptingContext
{
    private $retrievedData;

    public static function console($cmd)
    {
        $dir = __DIR__ . '/../../../../../';
        $cmd = "(cd $dir; php app/console -v $cmd)";
//        echo $cmd;
        $result = shell_exec($cmd);
//        echo $result;
        return $result;
    }

    /** @AfterSuite */
    public static function afterSuite($scope)
    {
        //not sure if we need this still

        // clean database after everything is done
//        self::console('zicht:versioning:client clear-test-records');

        //TODO should we also remove the table here? And add it at @beforeSuite?
    }

    /**
     * @Given /^I have a clean database$/
     */
    public function iHaveACleanDatabase()
    {
        // clean database after everything is done
        self::console('zicht:versioning:client clear-test-records');
    }

    /**
     * @Given /^a new page is created with title "([^"]*)"$/
     */
    public function aNewPageIsCreatedWithTitle($title)
    {
        self::console(sprintf('zicht:versioning:client create --title=%s', $title));
    }

    /**
     * @Given /^when i retrieve a page with title "([^"]*)"$/
     */
    public function whenIRetrieveAPageWithTitle($title)
    {
        $result = self::console(sprintf('zicht:versioning:client retrieve --title=%s', $title));
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
     * @Then I get the page back
     */
    public function iGetThePageBack()
    {
        throw new PendingException();
    }

    /**
     * @Given an existing page with title :arg1
     */
    public function anExistingPageWithTitle($arg1)
    {
        throw new PendingException();
    }

    /**
     * @When i change the title to :arg1
     */
    public function iChangeTheTitleTo($arg1)
    {
        throw new PendingException();
    }

    /**
     * @When i save it as a new version
     */
    public function iSaveItAsANewVersion()
    {
        throw new PendingException();
    }

    /**
     * @Then the title should be be :arg1
     */
    public function theTitleShouldBeBe($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Given an existing page with title :arg1 with a new version with title :arg2
     */
    public function anExistingPageWithTitleWithANewVersionWithTitle($arg1, $arg2)
    {
        throw new PendingException();
    }

    /**
     * @When I set the active version to title :arg1
     */
    public function iSetTheActiveVersionToTitle($arg1)
    {
        throw new PendingException();
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
