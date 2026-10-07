<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use Codeception\Stub;
use MailPoet\WP\Functions as WPFunctions;

class FormRenderStampTest extends \MailPoetUnitTest {
  private const SALT = 'test-salt';

  private function makeTestee(string $salt = self::SALT): FormRenderStamp {
    $wp = Stub::make(WPFunctions::class, ['wpSalt' => $salt], $this);
    return new FormRenderStamp($wp);
  }

  private function makeStamp(int $timestamp, string $salt = self::SALT): string {
    return $timestamp . '.' . hash_hmac('sha256', (string)$timestamp, $salt);
  }

  public function testIssuedStampReportsNoElapsedTimeRightAway() {
    $testee = $this->makeTestee();
    verify($testee->elapsedSeconds($testee->issue()))->same(0);
  }

  public function testReportsSecondsSinceIssue() {
    $testee = $this->makeTestee();
    verify($testee->elapsedSeconds($this->makeStamp(time() - 30)))->same(30);
  }

  public function testRejectsStampWithChangedTimestamp() {
    $testee = $this->makeTestee();
    $stamp = $this->makeStamp(time() - 30);
    [$timestamp, $hmac] = explode('.', $stamp);
    verify($testee->elapsedSeconds(((int)$timestamp - 100) . '.' . $hmac))->null();
  }

  public function testRejectsStampWithChangedSignature() {
    $testee = $this->makeTestee();
    $stamp = $this->makeStamp(time() - 30);
    $changed = substr($stamp, 0, -1) . (substr($stamp, -1) === '0' ? '1' : '0');
    verify($testee->elapsedSeconds($changed))->null();
  }

  public function testRejectsStampSignedWithAnotherSalt() {
    $testee = $this->makeTestee();
    verify($testee->elapsedSeconds($this->makeStamp(time() - 30, 'other-salt')))->null();
  }

  public function testRejectsMalformedStamps() {
    $testee = $this->makeTestee();
    foreach ([null, '', 'abc', '123', '12.zz', '-5.' . hash_hmac('sha256', '-5', self::SALT), ['a'], 12345] as $stamp) {
      verify($testee->elapsedSeconds($stamp))->null();
    }
  }

  public function testRejectsStampIssuedFarInTheFuture() {
    $testee = $this->makeTestee();
    verify($testee->elapsedSeconds($this->makeStamp(time() + 3600)))->null();
  }

  public function testAcceptsStampIssuedSlightlyInTheFuture() {
    $testee = $this->makeTestee();
    verify($testee->elapsedSeconds($this->makeStamp(time() + 30)))->same(0);
  }
}
