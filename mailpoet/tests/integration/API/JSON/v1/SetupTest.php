<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Stub;
use Helper\WordPressHooks as WPHooksHelper;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\Setup;
use MailPoet\Config\Activator;
use MailPoet\Config\Populator;
use MailPoet\Cron\ActionScheduler\ActionScheduler;
use MailPoet\Migrator\Migrator;
use MailPoet\Referrals\ReferralDetector;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\Captcha\CaptchaConstants;
use MailPoet\Subscription\Captcha\CaptchaRenderer;
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
    $populator = $this->getServiceWithOverrides(Populator::class, ['wp' => $wpStub, 'referralDetector' => $referralDetector]);
    $captchaRenderer = $this->diContainer->get(CaptchaRenderer::class);
    $migrator = $this->diContainer->get(Migrator::class);
    $cronActionScheduler = $this->diContainer->get(ActionScheduler::class);
    $router = new Setup($wpStub, new Activator($this->connection, $settings, $populator, $wpStub, $migrator, $cronActionScheduler));
    $response = $router->reset();
    expect($response->status)->equals(APIResponse::STATUS_OK);

    $settings = SettingsController::getInstance();
    $signupConfirmation = $settings->fetch('signup_confirmation.enabled');
    expect($signupConfirmation)->true();

    $captcha = $settings->fetch('captcha');
    $captchaType = $captchaRenderer->isSupported() ? CaptchaConstants::TYPE_BUILTIN : CaptchaConstants::TYPE_DISABLED;
    expect($captcha['type'])->equals($captchaType);
    expect($captcha['recaptcha_site_token'])->equals('');
    expect($captcha['recaptcha_secret_token'])->equals('');

    $woocommerceOptinOnCheckout = $settings->fetch('woocommerce.optin_on_checkout');
    expect($woocommerceOptinOnCheckout['enabled'])->true();

    $hookName = 'mailpoet_setup_reset';
    expect(WPHooksHelper::isActionDone($hookName))->true();
  }

  public function _after() {
    parent::_after();
  }
}
