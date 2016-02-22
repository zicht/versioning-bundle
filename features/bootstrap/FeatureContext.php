<?php

use Behat\Behat\Tester\Exception\PendingException;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Context\SnippetAcceptingContext;

/**
 * Features context.
 */
class FeatureContext extends MinkContext implements SnippetAcceptingContext
{
    public static function console($cmd)
    {
        $dir = __DIR__ . '/../../../../../../../';
        $cmd = "(cd $dir; php app/console -v $cmd)";
        echo $cmd;
//        $result = shell_exec($cmd);
//        echo $result;
//        return $result;
    }

    /**
     * @Given /^a new page with title "([^"]*)"$/
     */
    public function aNewPageWithTitle($title)
    {
        self::console(sprintf('zicht:versioning:client create-new %s', $title));
    }

    /**
     * @When i save it
     */
    public function iSaveIt()
    {
        throw new PendingException();
    }

    /**
     * @When i retrieve it
     */
    public function iRetrieveIt()
    {
        throw new PendingException();
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
