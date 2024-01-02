<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use AcceptanceTester;
use DateTimeImmutable;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Test\DataFactories;

class AutomationListingCest {
  public function automationListing(AcceptanceTester $i): void {
    $i->wantTo('Open automation listing page');
    $i->login();

    // empty state - landing page
    $i->amOnMailpoetPage('Automation');
    $i->waitForText('Automations');
    $i->waitForText('Better engagement begins with automation');
    $i->waitForText('Start with a template');
    $i->waitForText('Explore essentials');
    $i->waitForText('Browse all templates');
    $i->waitForText('Build your own automations');

    // non-empty state - listing
    (new DataFactories\Automation())->withName('Test Automation 1')->create();

    $automation = (new DataFactories\Automation())
      ->withName('Test Automation 2')
      ->withStatus(Automation::STATUS_ACTIVE)
      ->withCreatedAt(new DateTimeImmutable('-1 day'))
      ->create();

    (new DataFactories\AutomationRun())->withAutomation($automation)->withStatus(AutomationRun::STATUS_COMPLETE)->create();
    (new DataFactories\AutomationRun())->withAutomation($automation)->withStatus(AutomationRun::STATUS_COMPLETE)->create();
    (new DataFactories\AutomationRun())->withAutomation($automation)->withStatus(AutomationRun::STATUS_RUNNING)->create();
    (new DataFactories\AutomationRun())->withAutomation($automation)->withStatus(AutomationRun::STATUS_CANCELLED)->create();
    (new DataFactories\AutomationRun())->withAutomation($automation)->withStatus(AutomationRun::STATUS_FAILED)->create();

    $i->reloadPage();
    $i->waitForText('Automations');
    $i->waitForText('All');
    $i->waitForText('Active');
    $i->waitForText('Draft');
    $i->waitForText('Trash');
    $i->waitForText('Test Automation 1');
    $i->waitForText('Test Automation 2');
    $i->waitForText('Explore essentials');
    $i->waitForText('Browse all templates');
    $i->waitForText('Build your own automations');

    // check automation 1
    $automation1row = '.mailpoet-automation-listing tr:nth-child(3)';
    $i->see('Test Automation 1', $automation1row);
    $i->see('Entered 0', $automation1row);
    $i->see('Processing 0', $automation1row);
    $i->see('Exited 0', $automation1row);
    $i->see('Draft', $automation1row);
    $i->see('Analytics', $automation1row);
    $i->see('Edit', $automation1row);

    // check automation 2
    $automation2row = '.mailpoet-automation-listing tr:nth-child(2)';
    $i->see('Test Automation 2', $automation2row);
    $i->see('Entered 5', $automation2row);
    $i->see('Processing 1', $automation2row);
    $i->see('Exited 4', $automation2row);
    $i->see('Active', $automation2row);
    $i->see('Analytics', $automation2row);
    $i->see('Edit', $automation2row);
  }
}
