<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use AcceptanceTester;
use DateTimeImmutable;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Entities\ScheduledTaskEntity;
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

  public function legacyAutomaticEmailListing(AcceptanceTester $i): void {
    $i->wantTo('See legacy automatic emails on automation listing page');
    $i->activateWooCommerce();
    $i->login();

    // empty state - landing page
    $i->amOnMailpoetPage('Automation');
    $i->waitForText('Automations');
    $i->waitForText('Better engagement begins with automation');

    // non-empty state - listing
    $product = (new DataFactories\WooCommerceProduct($i))->withName('Test product')->create();

    (new DataFactories\Newsletter())
      ->withSubject('Welcome')
      ->withWelcomeTypeForSegment()
      ->withCreatedAt('2020-01-20 12:00:00')
      ->withActiveStatus()
      ->withScheduledQueue()
      ->withScheduledQueue()
      ->withScheduledQueue(['status' => ScheduledTaskEntity::STATUS_COMPLETED, 'count_processed' => 1])
      ->create();

    (new DataFactories\Newsletter())
      ->withSubject('Abandoned cart')
      ->withAutomaticTypeWooCommerceAbandonedCart()
      ->withCreatedAt('2020-01-19 12:00:00')
      ->create();

    (new DataFactories\Newsletter())
      ->withSubject('First purchase')
      ->withAutomaticTypeWooCommerceFirstPurchase()
      ->withCreatedAt('2020-01-18 12:00:00')
      ->create();

    (new DataFactories\Newsletter())
      ->withSubject('Product purchased')
      ->withAutomaticTypeWooCommerceProductPurchased([$product])
      ->withCreatedAt('2020-01-17 12:00:00')
      ->create();

    (new DataFactories\Newsletter())
      ->withSubject('Product purchased in category')
      ->withAutomaticTypeWooCommerceProductInCategoryPurchased([$product])
      ->withCreatedAt('2020-01-16 12:00:00')
      ->create();

    $i->reloadPage();
    $i->waitForText('Automations');
    $i->waitForText('Welcome');
    $i->waitForText('Abandoned cart');
    $i->waitForText('First purchase');
    $i->waitForText('Product purchased');
    $i->waitForText('Product purchased in category');

    // welcome email
    $welcomeRow = '.mailpoet-automation-listing tr:nth-child(2)';
    $i->see('Welcome', $welcomeRow);
    $i->see('Sent when someone subscribes to the list: WooCommerce Customers.', $welcomeRow);
    $i->see('Entered 3', $welcomeRow);
    $i->see('Processing 2', $welcomeRow);
    $i->see('Exited 1', $welcomeRow);

    // abandoned cart email
    $abandonedCartRow = '.mailpoet-automation-listing tr:nth-child(3)';
    $i->see('Abandoned cart', $abandonedCartRow);
    $i->see('Send the email when a customer abandons their cart. 1 week(s) later', $abandonedCartRow);

    // first purchase email
    $firstPurchaseRow = '.mailpoet-automation-listing tr:nth-child(4)';
    $i->see('First purchase', $firstPurchaseRow);
    $i->see('Email sent when a customer makes their first purchase.', $firstPurchaseRow);

    // product purchased email
    $productPurchasedRow = '.mailpoet-automation-listing tr:nth-child(5)';
    $i->see('Product purchased', $productPurchasedRow);
    $i->see('Email sent when a customer buys product: Test product.', $productPurchasedRow);

    // product purchased in category email
    $productPurchasedInCategoryRow = '.mailpoet-automation-listing tr:nth-child(6)';
    $i->see('Product purchased in category', $productPurchasedInCategoryRow);
    $i->see('Email sent when a customer buys a product in category: Uncategorized.', $productPurchasedInCategoryRow);
  }
}
