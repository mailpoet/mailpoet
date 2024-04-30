<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Mailer\Mailer;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Settings;

/**
 * This class contains tests for the MP Marketing channel
 * @group woo
 */
class MPMarketingChannelCest {

  /** @var Settings */
  private $settingsFactory;

  public function _before(\AcceptanceTester $i) {
    $i->activateWooCommerce();
    $this->settingsFactory = new Settings();
    $this->settingsFactory->withWooCommerceListImportPageDisplayed(true);
    $this->settingsFactory->withCookieRevenueTrackingDisabled();
  }

  public function itShowsMailPoetSetup(\AcceptanceTester $i) {
    $this->settingsFactory->withWelcomeWizard();

    $i->login();
    $i->amOnPage('/wp-admin/admin.php?page=wc-admin&path=%2Fmarketing');
    $i->waitForText('Channels', 10, '.woocommerce-marketing-channels-card');

    $i->see('MailPoet', '.woocommerce-marketing-registered-channel-card-body');
    $i->see('Finish setup', '.woocommerce-marketing-registered-channel-card-body');
    $i->click('Finish setup', '.woocommerce-marketing-registered-channel-card-body');
    $i->waitForText('Start by configuring your sender information');
  }

  public function itShowsErrorCount(\AcceptanceTester $i) {
    (new Newsletter())->create();

    $i->login();

    $errorMessage = 'Could not instantiate mail function. Unprocessed subscriber: (test <test@test.test>)';

    $this->settingsFactory->withSendingMethod(Mailer::METHOD_PHPMAIL);
    $this->settingsFactory->withSendingError($errorMessage);

    $i->amOnPage('/wp-admin/admin.php?page=wc-admin&path=%2Fmarketing');
    $i->waitForText('Channels', 10, '.woocommerce-marketing-channels-card');
    $i->see('MailPoet', '.woocommerce-marketing-registered-channel-card-body');
    $i->see('1 issues to resolve', '.woocommerce-marketing-registered-channel-card-body');
  }

  public function itShowsMailPoetSyncStatus(\AcceptanceTester $i) {
    (new Newsletter())->create();

    $i->login();

    $this->settingsFactory->withSendingMethodMailPoet();

    $i->amOnPage('/wp-admin/admin.php?page=wc-admin&path=%2Fmarketing');
    $i->waitForText('Channels', 10, '.woocommerce-marketing-channels-card');
    $i->see('MailPoet', '.woocommerce-marketing-registered-channel-card-body');
    $i->see('Synced', '.woocommerce-marketing-registered-channel-card-body');
  }

  public function itShowsMailPoetSyncStatusWithErrorCount(\AcceptanceTester $i) {
    (new Newsletter())->create();

    $i->login();

    $this->settingsFactory->withSendingMethodMailPoet();
    $this->settingsFactory->withSendingError('Error message');

    $i->amOnPage('/wp-admin/admin.php?page=wc-admin&path=%2Fmarketing');
    $i->waitForText('Channels', 10, '.woocommerce-marketing-channels-card');
    $i->see('MailPoet', '.woocommerce-marketing-registered-channel-card-body');
    $i->see('Sync failed', '.woocommerce-marketing-registered-channel-card-body');
    $i->see('2 issues to resolve', '.woocommerce-marketing-registered-channel-card-body');
  }

  public function itCanCreateMailPoetCampaigns(\AcceptanceTester $i) {
      $i->login();

      $i->amOnPage('/wp-admin/admin.php?page=wc-admin&path=%2Fmarketing');
      $i->see('Create a campaign', '.woocommerce-marketing-introduction-banner-buttons');
      $i->click('Create a campaign', '.woocommerce-marketing-introduction-banner-buttons');
      $i->waitForText('Create a new campaign');
      $i->see('Where would you like to promote your products?');
      $i->see('MailPoet Newsletters'); // campaign types
      $i->see('MailPoet Post notifications');
      $i->see('MailPoet Automations');
      $i->click('Create', '.woocommerce-marketing-new-campaign-type');
      $i->waitForElement('[data-automation-id="templates-standard"]');
      $i->seeInCurrentUrl('page=mailpoet-newsletters&loadedvia=woo_multichannel_dashboard#'); // will be redirected to page=mailpoet-newsletters#/template
      $i->waitForText('Simple text'); // on template selection page
      $i->see('Template'); // on template selection page
      $i->see('Newsletters');
      $i->see('Your saved templates');
  }
}
