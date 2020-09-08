<?php

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Stub;
use Helper\WordPressHooks as WPHooksHelper;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\Setup;
use MailPoet\Config\Activator;
use MailPoet\Config\Populator;
use MailPoet\Form\FormFactory;
use MailPoet\Form\FormsRepository;
use MailPoet\Referrals\ReferralDetector;
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
    $wp = Stub::make(new WPFunctions, [
      'doAction' => asCallable([WPHooksHelper::class, 'doAction']),
    ]);

    $settings = SettingsController::getInstance();
    $referralDetector = new ReferralDetector($wp, $settings);
    $populator = new Populator(
      $settings,
      $wp,
      new Captcha(),
      $referralDetector,
      $this->diContainer->get(FormsRepository::class),
      $this->diContainer->get(FormFactory::class)
    );
    $router = new Setup($wp, new Activator($settings, $populator));
    $response = $router->reset();
    expect($response->status)->equals(APIResponse::STATUS_OK);

    $settings = SettingsController::getInstance();
    $signupConfirmation = $settings->fetch('signup_confirmation.enabled');
    expect($signupConfirmation)->true();

    $captcha = $settings->fetch('captcha');
    $subscriptionCaptcha = new Captcha;
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
  }
}
