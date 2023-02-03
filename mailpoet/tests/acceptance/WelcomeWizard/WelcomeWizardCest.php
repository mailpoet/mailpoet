<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use Codeception\Scenario;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Settings\SettingsRepository;
use MailPoet\Test\DataFactories\Settings as SettingsFactory;
use PHPUnit\Framework\Assert;

class WelcomeWizardCest {
  /**
   * @var SettingsRepository
   */
  private $settingsRepository;

  public function _before(\AcceptanceTester $i) {
    // for some unknown reason the SettingsController doesn't return the expected values here so the code uses the SettingsRepository
    $this->settingsRepository = ContainerWrapper::getInstance()->get(SettingsRepository::class);

    $i->login();
    $i->activateWooCommerce();

    $settingsFactory = new SettingsFactory();
    $settingsFactory->withWelcomeWizard();

    $i->amOnMailpoetPage('Welcome-Wizard');
  }

  public function welcomeWizardMainFlow(\AcceptanceTester $i, Scenario $scenario) {
    $mailPoetSendingKey = getenv('WP_TEST_MAILER_MAILPOET_API');
    if (!$mailPoetSendingKey) {
      $scenario->skip("Skipping, 'WP_TEST_MAILER_MAILPOET_API' not set.");
    }

    $senderName = 'WP Admin';
    $senderAddress = 'wp@example.com';

    $i->wantTo('Check user can go through the welcome wizard');

    // First step of the wizard
    $i->fillField('senderName', $senderName);
    $i->fillField('senderAddress', $senderAddress);
    $i->click('.mailpoet-wizard-continue-button');

    // Second step of the wizard
    $i->waitForText('Confirm privacy and data settings');
    $i->click('#mailpoet-wizard-3rd-party-libs .mailpoet-form-yesno-yes');
    $i->click('#mailpoet-wizard-tracking .mailpoet-form-yesno-yes');
    $i->click('.mailpoet-wizard-continue-button');

    // Third step of the wizard
    $i->waitForText('Power up your WooCommerce store');
    $i->click('.mailpoet-form-yesno-no');
    $i->click('.mailpoet-wizard-continue-button');

    // Fourth step of the wizard
    $i->waitForText('Connect your MailPoet account');
    $i->click('.mailpoet-wizard-continue-button');
    $i->waitForText('Activate your MailPoet account');
    $i->fillField('#mailpoet_premium_key', 'invalid key');
    $i->click('.mailpoet-verify-key-button');
    $i->waitForElementVisible('.mailpoet_error');
    $i->fillField('#mailpoet_premium_key', $mailPoetSendingKey);
    $i->click('.mailpoet-verify-key-button');
    $i->waitForText('MailPoet account connected', 20);
    $i->click('.mailpoet-wizard-continue-button');

    // wizard finished and the user was redirect to the newsletter page
    $i->waitForElement('#newsletters_container');

    Assert::assertSame($senderName, $this->settingsRepository->findOneByName('sender')->getValue()['name']);
    Assert::assertSame($senderAddress, $this->settingsRepository->findOneByName('sender')->getValue()['address']);
    Assert::assertSame(['enabled' => '1'], $this->settingsRepository->findOneByName('3rd_party_libs')->getValue());
    Assert::assertSame(['enabled' => '1'], $this->settingsRepository->findOneByName('analytics')->getValue());
    Assert::assertSame(['level' => 'full'], $this->settingsRepository->findOneByName('tracking')->getValue());
    Assert::assertSame($mailPoetSendingKey, $this->settingsRepository->findOneByName('mta')->getValue()['mailpoet_api_key']);
  }
}
