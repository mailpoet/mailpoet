<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use AcceptanceTester;
use DateTimeImmutable;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Test\DataFactories;
use PHPUnit\Framework\Assert;
use Symfony\Component\CssSelector\XPath\Translator;

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
    $i->waitForText('Inactive');
    $i->waitForText('Trash');
    $i->waitForText('Test Automation 1');
    $i->waitForText('Test Automation 2');
    $i->waitForText('Explore essentials');
    $i->waitForText('Browse all templates');
    $i->waitForText('Build your own automations');

    // check automation 1
    $automation1row = $this->getAutomationRow('Test Automation 1');
    $i->see('Test Automation 1', $automation1row);
    $i->see('Entered 0', $automation1row);
    $i->see('Processing 0', $automation1row);
    $i->see('Exited 0', $automation1row);
    $i->see('Inactive', $automation1row);

    // check automation 2
    $automation2row = $this->getAutomationRow('Test Automation 2');
    $i->see('Test Automation 2', $automation2row);
    $i->see('Entered 5', $automation2row);
    $i->see('Processing 1', $automation2row);
    $i->see('Exited 4', $automation2row);
    $i->see('Active', $automation2row);
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
      ->withScheduledQueue(['count_to_process' => 2])
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
    $welcomeRow = $this->getAutomationRow('Welcome');
    $i->see('Welcome', $welcomeRow);
    $i->see('Sent when someone subscribes to the list: WooCommerce Customers.', $welcomeRow);
    $i->see('Entered 3', $welcomeRow);
    $i->see('Processing 2', $welcomeRow);
    $i->see('Exited 1', $welcomeRow);

    // abandoned cart email
    $abandonedCartRow = $this->getAutomationRow('Abandoned cart');
    $i->see('Abandoned cart', $abandonedCartRow);
    $i->see('Send the email when a customer abandons their cart. 1 week(s) later', $abandonedCartRow);

    // first purchase email
    $firstPurchaseRow = $this->getAutomationRow('First purchase');
    $i->see('First purchase', $firstPurchaseRow);
    $i->see('Email sent when a customer makes their first purchase.', $firstPurchaseRow);

    // product purchased email
    $productPurchasedRow = $this->getAutomationRow('Product purchased');
    $i->see('Product purchased', $productPurchasedRow);
    $i->see('Email sent when a customer buys product: Test product.', $productPurchasedRow);

    // product purchased in category email
    $productPurchasedInCategoryRow = $this->getAutomationRow('Product purchased in category');
    $i->see('Product purchased in category', $productPurchasedInCategoryRow);
    $i->see('Email sent when a customer buys a product in category: Uncategorized.', $productPurchasedInCategoryRow);
  }

  public function bulkAutomationActions(AcceptanceTester $i): void {
    $i->wantTo('Duplicate, trash, restore, and delete automations in bulk');
    $uniqueId = uniqid();
    $automation1Name = 'Bulk automation 1 ' . $uniqueId;
    $automation2Name = 'Bulk automation 2 ' . $uniqueId;

    $automation1 = (new DataFactories\Automation())->withName($automation1Name)->create();
    $automation2 = (new DataFactories\Automation())->withName($automation2Name)->create();

    $i->login();
    $i->amOnMailpoetPage('Automation');
    $i->waitForText($automation1Name, 20, '[data-automation-id="automation_listing"]');
    $i->waitForText($automation2Name, 20, '[data-automation-id="automation_listing"]');

    $i->wantTo('Bulk duplicate selected automations');
    $i->checkWooTableCheckboxForItemName($automation1Name);
    $i->checkWooTableCheckboxForItemName($automation2Name);
    $i->selectListingBulkAction('Duplicate');
    $i->waitForText('2 automations were duplicated.');
    $i->waitForText('Copy of ' . $automation1Name, 20, '[data-automation-id="automation_listing"]');
    $i->waitForText('Copy of ' . $automation2Name, 20, '[data-automation-id="automation_listing"]');

    $i->wantTo('Bulk trash selected automations');
    $i->checkWooTableCheckboxForItemName($automation1Name);
    $i->checkWooTableCheckboxForItemName($automation2Name);
    $i->selectListingBulkAction('Trash');
    $i->waitForText('Are you sure you want to move the automations');
    $i->clickModalButton('Yes, move to trash');
    $i->waitForText('2 automations were moved to the trash.');

    $i->wantTo('Bulk restore selected automations from trash');
    $i->changeWooTableTab('trash');
    $i->waitForText($automation1Name, 20, '[data-automation-id="automation_listing"]');
    $i->waitForText($automation2Name, 20, '[data-automation-id="automation_listing"]');
    $i->checkWooTableCheckboxForItemName($automation1Name);
    $i->checkWooTableCheckboxForItemName($automation2Name);
    $i->selectListingBulkAction('Restore');
    $i->waitForText('2 automations were restored from the trash.');
    $i->waitForText('Trash is empty.', 20, '[data-automation-id="automation_listing"]');

    $i->wantTo('Bulk delete selected automations permanently');
    $i->changeWooTableTab('all');
    $i->waitForText($automation1Name, 20, '[data-automation-id="automation_listing"]');
    $i->waitForText($automation2Name, 20, '[data-automation-id="automation_listing"]');
    $i->checkWooTableCheckboxForItemName($automation1Name);
    $i->checkWooTableCheckboxForItemName($automation2Name);
    $i->selectListingBulkAction('Trash');
    $i->clickModalButton('Yes, move to trash');
    $i->waitForText('2 automations were moved to the trash.');
    $i->changeWooTableTab('trash');
    $i->waitForText($automation1Name, 20, '[data-automation-id="automation_listing"]');
    $i->checkWooTableCheckboxForItemName($automation1Name);
    $i->checkWooTableCheckboxForItemName($automation2Name);
    $i->selectListingBulkAction('Delete permanently');
    $i->waitForText('Are you sure you want to permanently delete');
    $i->clickModalButton('Yes, permanently delete');
    $i->waitForText('2 automations were permanently deleted.');
    $i->waitForText('Trash is empty.', 20, '[data-automation-id="automation_listing"]');

    $automationStorage = ContainerWrapper::getInstance()->get(AutomationStorage::class);
    Assert::assertNull($automationStorage->getAutomation($automation1->getId()));
    Assert::assertNull($automationStorage->getAutomation($automation2->getId()));
    $i->seeNoJSErrors();
  }

  private function getAutomationRow(string $automationName): string {
    return sprintf(
      '//*[@data-automation-id="automation_listing"]//tr[contains(concat(" ", normalize-space(@class), " "), " dataviews-view-table__row ")][.//div[contains(concat(" ", normalize-space(@class), " "), " dataviews-title-field ")]//a[normalize-space()=%s]]',
      Translator::getXpathLiteral($automationName)
    );
  }
}
