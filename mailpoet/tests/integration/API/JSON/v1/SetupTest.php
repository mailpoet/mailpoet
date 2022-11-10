<?php

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Stub;
use Helper\WordPressHooks as WPHooksHelper;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\Setup;
use MailPoet\Config\Activator;
use MailPoet\Config\Populator;
use MailPoet\Cron\ActionScheduler\ActionScheduler;
use MailPoet\Form\FormsRepository;
use MailPoet\Features\FeatureFlagsController;
use MailPoet\Features\FeaturesController;
use MailPoet\Migrator\Migrator;
use MailPoet\Referrals\ReferralDetector;
use MailPoet\Segments\WP;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;
use MailPoet\Subscription\Captcha;
use MailPoet\WP\Functions as WPFunctions;

class SetupTest extends \MailPoetTest {
  public function _before() {
    parent::_before();
    $settings = SettingsController::getInstance();
    $settings->set('signup_confirmation.enabled', false);
  }

  public function testItCanReinstall() {
    $wpStub = Stub::make(new WPFunctions, [
      'doAction' => asCallable([WPHooksHelper::class, 'doAction']),
    ]);

    $settings = SettingsController::getInstance();
    $referralDetector = new ReferralDetector($wpStub, $settings);
    $subscriptionCaptcha = $this->diContainer->get(Captcha::class);
    $populator = $this->getServiceWithOverrides(Populator::class, ['wp' => $wpStub, 'referralDetector' => $referralDetector]);
    $migrator = $this->diContainer->get(Migrator::class);
    $cronActionScheduler = $this->diContainer->get(ActionScheduler::class);
    $router = new Setup($wpStub, new Activator($this->connection, $settings, $populator, $wpStub, $migrator, $cronActionScheduler));
    $response = $router->reset();
    expect($response->status)->equals(APIResponse::STATUS_OK);

    $settings = SettingsController::getInstance();
    $signupConfirmation = $settings->fetch('signup_confirmation.enabled');
    expect($signupConfirmation)->true();

    $captcha = $settings->fetch('captcha');
    $captchaType = $subscriptionCaptcha->isSupported() ? Captcha::TYPE_BUILTIN : Captcha::TYPE_DISABLED;
    expect($captcha['type'])->equals($captchaType);
    expect($captcha['recaptcha_site_token'])->equals('');
    expect($captcha['recaptcha_secret_token'])->equals('');

    $woocommerceOptinOnCheckout = $settings->fetch('woocommerce.optin_on_checkout');
    expect($woocommerceOptinOnCheckout['enabled'])->true();

    $hookName = 'mailpoet_setup_reset';
    expect(WPHooksHelper::isActionDone($hookName))->true();
  }

  public function _after() {
    $this->diContainer->get(SettingsRepository::class)->truncate();

    // Temporarily hotfix a side effect of this test, that is, removing automation feature flag
    // and tables and not setting them back. This will be removed very soon, before MVP release.
    $this->diContainer->get(FeatureFlagsController::class)->set(FeaturesController::AUTOMATION, true);
  }
}
