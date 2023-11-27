<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Features\FeaturesController;
use MailPoet\Mailer\Mailer;
use MailPoet\Test\DataFactories\Features;
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
    (new Features())->withFeatureEnabled(FeaturesController::MAILPOET_WOOCOMMERCE_MULTICHANNEL_INTEGRATION);

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
    (new Features())->withFeatureEnabled(FeaturesController::MAILPOET_WOOCOMMERCE_MULTICHANNEL_INTEGRATION);

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
    (new Features())->withFeatureEnabled(FeaturesController::MAILPOET_WOOCOMMERCE_MULTICHANNEL_INTEGRATION);

    $i->login();

    $this->settingsFactory->withSendingMethodMailPoet();

    $i->amOnPage('/wp-admin/admin.php?page=wc-admin&path=%2Fmarketing');
    $i->waitForText('Channels', 10, '.woocommerce-marketing-channels-card');
    $i->see('MailPoet', '.woocommerce-marketing-registered-channel-card-body');
    $i->see('Synced', '.woocommerce-marketing-registered-channel-card-body');
  }

  public function itShowsMailPoetSyncStatusWithErrorCount(\AcceptanceTester $i) {
    (new Newsletter())->create();
    (new Features())->withFeatureEnabled(FeaturesController::MAILPOET_WOOCOMMERCE_MULTICHANNEL_INTEGRATION);

    $i->login();

    $this->settingsFactory->withSendingMethodMailPoet();
    $this->settingsFactory->withSendingError('Error message');

    $i->amOnPage('/wp-admin/admin.php?page=wc-admin&path=%2Fmarketing');
    $i->waitForText('Channels', 10, '.woocommerce-marketing-channels-card');
    $i->see('MailPoet', '.woocommerce-marketing-registered-channel-card-body');
    $i->see('Sync failed', '.woocommerce-marketing-registered-channel-card-body');
    $i->see('2 issues to resolve', '.woocommerce-marketing-registered-channel-card-body');
  }
}
