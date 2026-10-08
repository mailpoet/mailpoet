<?php declare(strict_types = 1);

namespace MailPoet\Test\Captcha;

use MailPoet\Captcha\CaptchaConstants;
use MailPoet\Captcha\CaptchaHooks;
use MailPoet\Captcha\CaptchaSession;
use MailPoet\Config\Hooks;
use MailPoet\Settings\SettingsController;

class CaptchaHooksTest extends \MailPoetTest {
  const SESSION_ID = 'abcd1234abcd1234abcd1234abcd1234';

  private CaptchaHooks $testee;
  private CaptchaSession $session;
  private SettingsController $settings;
  private Hooks $configHooks;

  public function _before() {
    parent::_before();
    $this->testee = $this->diContainer->get(CaptchaHooks::class);
    $this->session = $this->diContainer->get(CaptchaSession::class);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->configHooks = $this->diContainer->get(Hooks::class);
  }

  public function _after() {
    unset($_POST['data']);
    $this->session->reset(self::SESSION_ID);
    wp_set_current_user(0);
    remove_filter('registration_errors', [$this->testee, 'validate'], 10);
    remove_filter('woocommerce_process_registration_errors', [$this->testee, 'validate'], 10);
    $this->settings->set('captcha', []);
    $this->settings->set(CaptchaConstants::ON_REGISTER_FORMS_SETTING_NAME, false);
    parent::_after();
  }

  public function testItRejectsAnUnknownSessionWithoutCreatingOne(): void {
    $_POST['data'] = ['captcha_session_id' => self::SESSION_ID, 'captcha' => 'abc'];
    $before = $this->countHashTransients();

    $errors = $this->testee->validate(new \WP_Error());

    verify($errors->get_error_messages('captcha_failed'))->equals(['CAPTCHA verification failed. Please try again.']);
    verify($this->session->exists(self::SESSION_ID))->false();
    $this->assertEquals($before, $this->countHashTransients());
  }

  public function testItRejectsRequestsWithoutSessionData(): void {
    unset($_POST['data']);
    $before = $this->countHashTransients();
    $errors = $this->testee->validate(new \WP_Error());
    verify($errors->get_error_codes())->equals(['captcha_failed']);
    $this->assertEquals($before, $this->countHashTransients());
  }

  public function testItResetsTheSessionOnAWrongAnswer(): void {
    $this->session->setCaptchaHash(self::SESSION_ID, ['phrase' => 'xyz']);
    $_POST['data'] = ['captcha_session_id' => self::SESSION_ID, 'captcha' => 'abc'];

    $errors = $this->testee->validate(new \WP_Error());

    verify($errors->get_error_codes())->equals(['captcha_failed']);
    verify($this->session->exists(self::SESSION_ID))->false();
  }

  public function testItAcceptsACorrectAnswerOnlyOnce(): void {
    $this->session->setCaptchaHash(self::SESSION_ID, ['phrase' => 'xyz']);
    $this->session->setFormData(self::SESSION_ID, ['user_email' => 'a@example.com']);
    $_POST['data'] = ['captcha_session_id' => self::SESSION_ID, 'captcha' => 'xyz'];

    $errors = $this->testee->validate(new \WP_Error());

    verify($errors->get_error_codes())->equals([]);
    verify($this->session->exists(self::SESSION_ID))->false();
    $errors = $this->testee->validate(new \WP_Error());
    verify($errors->get_error_codes())->equals(['captcha_failed']);
  }

  public function testWordPressRegistrationFilterAcceptsAnAnswerOnlyOnce(): void {
    $this->assertRegistrationFilterAcceptsAnAnswerOnlyOnce('registration_errors');
  }

  /**
   * @group woo
   */
  public function testWooCommerceRegistrationFilterAcceptsAnAnswerOnlyOnce(): void {
    if (!class_exists(\WC_Order::class)) {
      $this->markTestSkipped('WooCommerce is not active.');
    }
    $this->assertRegistrationFilterAcceptsAnAnswerOnlyOnce('woocommerce_process_registration_errors');
  }

  /**
   * @param non-empty-string $filter
   */
  private function assertRegistrationFilterAcceptsAnAnswerOnlyOnce(string $filter): void {
    $this->settings->set('captcha', ['type' => CaptchaConstants::TYPE_BUILTIN]);
    $this->settings->set(CaptchaConstants::ON_REGISTER_FORMS_SETTING_NAME, true);
    $this->configHooks->setupCaptchaOnRegisterForm();
    verify(has_filter($filter, [$this->testee, 'validate']))->notFalse();

    $this->session->setCaptchaHash(self::SESSION_ID, ['phrase' => 'xyz']);
    $this->session->setFormData(self::SESSION_ID, ['user_email' => 'a@example.com']);
    $_POST['data'] = ['captcha_session_id' => self::SESSION_ID, 'captcha' => 'xyz'];

    $errors = apply_filters($filter, new \WP_Error(), 'user', 'password', 'a@example.com');
    verify($errors->get_error_codes())->equals([]);
    verify($this->session->exists(self::SESSION_ID))->false();

    $errors = apply_filters($filter, new \WP_Error(), 'user', 'password', 'a@example.com');
    verify($errors->get_error_codes())->equals(['captcha_failed']);
  }

  public function testItSkipsTheCheckForExemptUsers(): void {
    $adminId = $this->tester->createWordPressUser('captcha-hooks-admin@example.com', 'administrator');
    wp_set_current_user($adminId);
    unset($_POST['data']);

    $errors = $this->testee->validate(new \WP_Error());

    verify($errors->get_error_codes())->equals([]);
  }

  private function countHashTransients(): int {
    global $wpdb;
    return (int)$wpdb->get_var(
      "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_MAILPOET\\_%\\_hash'"
    );
  }
}
