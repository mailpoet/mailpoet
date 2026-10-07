<?php declare(strict_types = 1);

namespace MailPoet\Test\Captcha;

use MailPoet\Captcha\CaptchaHooks;
use MailPoet\Captcha\CaptchaSession;

class CaptchaHooksTest extends \MailPoetTest {
  const SESSION_ID = 'abcd1234abcd1234abcd1234abcd1234';

  private CaptchaHooks $testee;
  private CaptchaSession $session;

  public function _before() {
    parent::_before();
    $this->testee = $this->diContainer->get(CaptchaHooks::class);
    $this->session = $this->diContainer->get(CaptchaSession::class);
  }

  public function _after() {
    unset($_POST['data']);
    $this->session->reset(self::SESSION_ID);
    wp_set_current_user(0);
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

  public function testItAcceptsACorrectAnswer(): void {
    $this->session->setCaptchaHash(self::SESSION_ID, ['phrase' => 'xyz']);
    $_POST['data'] = ['captcha_session_id' => self::SESSION_ID, 'captcha' => 'xyz'];

    $errors = $this->testee->validate(new \WP_Error());

    verify($errors->get_error_codes())->equals([]);
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
