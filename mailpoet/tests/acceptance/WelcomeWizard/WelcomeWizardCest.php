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

  /**
   * @var SettingsFactory
   */
  private $settingsFactory;

  public function _before(\AcceptanceTester $i) {
    // for some unknown reason the SettingsController doesn't return the expected values here so the code uses the SettingsRepository
    $this->settingsRepository = ContainerWrapper::getInstance()->get(SettingsRepository::class);

    $i->login();
    $i->activateWooCommerce();

    $this->settingsFactory = new SettingsFactory();
    $this->settingsFactory->withWelcomeWizard();
  }

  public function welcomeWizardMainFlow(\AcceptanceTester $i, Scenario $scenario) {
    $mailPoetSendingKey = getenv('WP_TEST_MAILER_MAILPOET_API');
    if (!$mailPoetSendingKey) {
      $scenario->skip("Skipping, 'WP_TEST_MAILER_MAILPOET_API' not set.");
    }

    $senderName = 'WP Admin';
    $senderAddress = 'wp@example.com';

    $i->wantTo('Check user can go through the welcome wizard');
    $i->amOnMailpoetPage('Welcome-Wizard');

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
    $i->click('.mailpoet-form-yesno-yes');
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

    // wizard finished and the user was redirect to the home page
    $i->waitForText('Welcome to MailPoet', 10, '.mailpoet-homepage__container');

    Assert::assertSame($senderName, $this->findSetting('sender')->getValue()['name']);
    Assert::assertSame($senderAddress, $this->findSetting('sender')->getValue()['address']);
    Assert::assertSame(['enabled' => '1'], $this->findSetting('3rd_party_libs')->getValue());
    Assert::assertSame(['enabled' => '1'], $this->findSetting('analytics')->getValue());
    Assert::assertSame(['level' => 'full'], $this->findSetting('tracking')->getValue());
    Assert::assertSame($mailPoetSendingKey, $this->findSetting('mta')->getValue()['mailpoet_api_key']);
  }

  public function welcomeWizardShouldSkipMssStepIfKeyAlreadyExists(\AcceptanceTester $i, Scenario $scenario) {
    $mailPoetSendingKey = getenv('WP_TEST_MAILER_MAILPOET_API');
    if (!$mailPoetSendingKey) {
      $scenario->skip("Skipping, 'WP_TEST_MAILER_MAILPOET_API' not set.");
    }

    $senderName = 'WP Admin';
    $senderAddress = 'wp@example.com';

    $this->settingsFactory->withSendingMethodMailPoet();

    $i->wantTo('Check welcome wizard skips MSS step when a MSS key already exists');
    $i->amOnMailpoetPage('Welcome-Wizard');

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
    $i->click('.mailpoet-form-yesno-yes');
    $i->click('.mailpoet-wizard-continue-button');

    // wizard finished and the user was redirect to the home page
    $i->waitForText('Welcome to MailPoet', 10, '.mailpoet-homepage__container');

    Assert::assertSame($senderName, $this->findSetting('sender')->getValue()['name']);
    Assert::assertSame($senderAddress, $this->findSetting('sender')->getValue()['address']);
    Assert::assertSame(['enabled' => '1'], $this->findSetting('3rd_party_libs')->getValue());
    Assert::assertSame(['enabled' => '1'], $this->findSetting('analytics')->getValue());
    Assert::assertSame(['level' => 'full'], $this->findSetting('tracking')->getValue());
    Assert::assertSame($mailPoetSendingKey, $this->findSetting('mta')->getValue()['mailpoet_api_key']);
  }

  private function findSetting(string $name) {
    $setting = $this->settingsRepository->findOneByName($name);
    if (!$setting) {
      throw new \Exception("Setting '$name' not found");
    }
    return $setting;
  }
}
