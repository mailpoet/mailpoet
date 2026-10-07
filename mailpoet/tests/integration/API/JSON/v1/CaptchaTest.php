<?php declare(strict_types = 1);

namespace integration\API\JSON\v1;

use MailPoet\API\JSON\Response;
use MailPoet\API\JSON\v1\Captcha;
use MailPoet\Captcha\CaptchaConstants;
use MailPoet\Captcha\CaptchaHooks;
use MailPoet\Captcha\CaptchaSession;
use MailPoet\Captcha\CaptchaUrlFactory;
use MailPoet\Config\Populator;
use MailPoet\Router\Router;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class CaptchaTest extends \MailPoetTest {
  private SettingsController $settings;
  private CaptchaSession $session;

  public function _before() {
    $populator = $this->diContainer->get(Populator::class);
    $populator->up();

    parent::_before();
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->session = $this->diContainer->get(CaptchaSession::class);
    $this->settings->set(CaptchaConstants::TYPE_SETTING_NAME, CaptchaConstants::TYPE_BUILTIN);
    $this->settings->set(CaptchaConstants::ON_REGISTER_FORMS_SETTING_NAME, true);
  }

  public function _after() {
    $this->settings->set('captcha', []);
    $this->settings->set(CaptchaConstants::ON_REGISTER_FORMS_SETTING_NAME, false);
    parent::_after();
  }

  public function testItCanRenderCaptcha(): void {
    $response = $this->createCaptchaEndpoint()->render($this->getRegisterFormData());

    verify($response->status)->equals(Response::REDIRECT);
    verify($response->location)->stringContainsString('mailpoet_router&endpoint=captcha&action=render&data=');
  }

  public function testItKeepsCaptchaPagePermalinkOnDifferentHost(): void {
    $wp = $this->diContainer->get(WPFunctions::class);
    $permalinkOnOtherHost = function () {
      return 'https://captcha.example.com/captcha-page/';
    };
    $wp->addFilter('post_type_link', $permalinkOnOtherHost);
    try {
      $response = $this->createCaptchaEndpoint()->render($this->getRegisterFormData());
    } finally {
      $wp->removeFilter('post_type_link', $permalinkOnOtherHost);
    }

    verify($response->status)->equals(Response::REDIRECT);
    verify($response->location)->stringContainsString('https://captcha.example.com/captcha-page/');
  }

  public function testTheRedirectCarriesOnlyTheSessionIdAndTheReferrer(): void {
    $data = $this->getRegisterFormData();
    $response = $this->createCaptchaEndpoint()->render($data);

    $decoded = $this->decodeLocationData($response->location);
    verify(array_keys($decoded))->equals(['captcha_session_id', 'referrer_form']);
    verify($decoded['referrer_form'])->equals(CaptchaUrlFactory::REFERER_WP_FORM);
    verify($this->session->isValidId($decoded['captcha_session_id']))->true();
    verify($response->location)->stringNotContainsString('jane@example.com');
    verify($response->location)->stringNotContainsString('jane_doe');
  }

  public function testTheFormFieldsAreKeptInTheServerSideStash(): void {
    $response = $this->createCaptchaEndpoint()->render($this->getRegisterFormData());

    $sessionId = $this->decodeLocationData($response->location)['captcha_session_id'];
    $stash = $this->session->getFormData($sessionId);
    verify($stash)->isArray();
    verify($stash['user_login'])->equals('jane_doe');
    verify($stash['user_email'])->equals('jane@example.com');
    verify($stash['referrer_form'])->equals(CaptchaUrlFactory::REFERER_WP_FORM);
    verify($stash['referrer_form_url'])->equals('/wp-login.php?action=register');
  }

  public function testItIgnoresASessionIdChosenByTheCaller(): void {
    $chosen = 'cafe1234cafe1234cafe1234cafe1234';
    $response = $this->createCaptchaEndpoint()->render($this->getRegisterFormData() + ['captcha_session_id' => $chosen]);

    $sessionId = $this->decodeLocationData($response->location)['captcha_session_id'];
    verify($sessionId)->notEquals($chosen);
    verify($this->session->exists($chosen))->false();
  }

  public function testItDoesNotStashNonScalarValues(): void {
    $response = $this->createCaptchaEndpoint()->render($this->getRegisterFormData() + ['nested' => ['a' => 'b']]);

    $sessionId = $this->decodeLocationData($response->location)['captcha_session_id'];
    $stash = $this->session->getFormData($sessionId);
    verify(is_array($stash) && !array_key_exists('nested', $stash))->true();
  }

  public function testItRejectsTheRequestWhenRegisterFormCaptchaIsOff(): void {
    $this->settings->set(CaptchaConstants::ON_REGISTER_FORMS_SETTING_NAME, false);

    $response = $this->createCaptchaEndpoint()->render($this->getRegisterFormData());

    verify($response->status)->equals(Response::STATUS_BAD_REQUEST);
  }

  public function testItRejectsAnUnknownReferrer(): void {
    $data = $this->getRegisterFormData();
    $data['referrer_form'] = CaptchaUrlFactory::REFERER_MP_FORM;
    verify($this->createCaptchaEndpoint()->render($data)->status)->equals(Response::STATUS_BAD_REQUEST);

    unset($data['referrer_form']);
    verify($this->createCaptchaEndpoint()->render($data)->status)->equals(Response::STATUS_BAD_REQUEST);
  }

  public function testItAcceptsTheWooCommerceReferrer(): void {
    $data = $this->getRegisterFormData();
    $data['referrer_form'] = CaptchaUrlFactory::REFERER_WC_FORM;

    $response = $this->createCaptchaEndpoint()->render($data);

    verify($response->status)->equals(Response::REDIRECT);
    verify($this->decodeLocationData($response->location)['referrer_form'])->equals(CaptchaUrlFactory::REFERER_WC_FORM);
  }

  public function testItRejectsAnOversizedForm(): void {
    $data = $this->getRegisterFormData();
    $data['user_login'] = str_repeat('a', 9000);

    $response = $this->createCaptchaEndpoint()->render($data);

    verify($response->status)->equals(Response::STATUS_BAD_REQUEST);
  }

  public function testItAcceptsAFormJustUnderTheSizeCap(): void {
    $data = $this->getRegisterFormData();
    $data['user_login'] = str_repeat('a', 4000);

    $response = $this->createCaptchaEndpoint()->render($data);

    verify($response->status)->equals(Response::REDIRECT);
  }

  public function testItReportsAnErrorWhenTheSessionBudgetIsUsedUp(): void {
    $wp = $this->diContainer->get(WPFunctions::class);
    $noBudget = function () {
      return 0;
    };
    $wp->addFilter('mailpoet_captcha_session_limit', $noBudget);
    $_SERVER['REMOTE_ADDR'] = '203.0.113.7';
    try {
      $response = $this->createCaptchaEndpoint()->render($this->getRegisterFormData());
    } finally {
      $wp->removeFilter('mailpoet_captcha_session_limit', $noBudget);
      unset($_SERVER['REMOTE_ADDR']);
    }

    verify($response->status)->equals(Response::STATUS_BAD_REQUEST);
  }

  private function getRegisterFormData(): array {
    return [
      'referrer_form' => CaptchaUrlFactory::REFERER_WP_FORM,
      'referrer_form_url' => '/wp-login.php?action=register',
      'user_login' => 'jane_doe',
      'user_email' => 'jane@example.com',
      'wp-submit' => 'Register',
    ];
  }

  private function decodeLocationData(string $location): array {
    $encoded = substr($location, (int)strrpos($location, '&data=') + strlen('&data='));
    return Router::decodeRequestData($encoded);
  }

  private function createCaptchaEndpoint(): Captcha {
    return new Captcha(
      $this->diContainer->get(CaptchaSession::class),
      $this->diContainer->get(CaptchaUrlFactory::class),
      $this->diContainer->get(CaptchaHooks::class),
      $this->diContainer->get(WPFunctions::class)
    );
  }
}
