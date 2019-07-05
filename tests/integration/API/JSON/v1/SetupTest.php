<?php
namespace MailPoet\Test\API\JSON\v1;

use Codeception\Stub;
use MailPoet\Config\Activator;
use MailPoet\Config\Populator;
use MailPoet\Models\Setting;
use MailPoet\API\JSON\v1\Setup;
use MailPoet\WP\Functions as WPFunctions;
use Helper\WordPressHooks as WPHooksHelper;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\Captcha;

class SetupTest extends \MailPoetTest {
  function _before() {
    parent::_before();
    $settings = new SettingsController();
    $settings->set('signup_confirmation.enabled', false);
  }

  function testItCanReinstall() {
    $wp = Stub::make(new WPFunctions, [
      'doAction' => asCallable([WPHooksHelper::class, 'doAction']),
    ]);

    $settings = new SettingsController();
    $populator = new Populator($settings, $wp, new Captcha());
    $router = new Setup($wp, new Activator($settings, $populator));
    $response = $router->reset();
    expect($response->status)->equals(APIResponse::STATUS_OK);

    $settings = new SettingsController();
    $signup_confirmation = $settings->fetch('signup_confirmation.enabled');
    expect($signup_confirmation)->true();

    $captcha = $settings->fetch('captcha');
    $subscription_captcha = new Captcha;
    $captcha_type = $subscription_captcha->isSupported() ? Captcha::TYPE_BUILTIN : Captcha::TYPE_DISABLED;
    expect($captcha['type'])->equals($captcha_type);
    expect($captcha['recaptcha_site_token'])->equals('');
    expect($captcha['recaptcha_secret_token'])->equals('');

    $woocommerce_optin_on_checkout = $settings->fetch('woocommerce.optin_on_checkout');
    expect($woocommerce_optin_on_checkout['enabled'])->true();

    $hook_name = 'mailpoet_setup_reset';
    expect(WPHooksHelper::isActionDone($hook_name))->true();
  }

  function _after() {
    Setting::deleteMany();
  }
}
