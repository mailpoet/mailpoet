<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use Codeception\Stub;
use MailPoetVendor\Gregwar\Captcha\PhraseBuilder;

class CaptchaPhraseTest extends \MailPoetUnitTest {
  public function testItCreatesPhrase(): void {
    $expectedSessionId = '123';
    $expectedPhrase = 'abc';

    $session = $this->make(CaptchaSession::class, [
      'setCaptchaHash' => Stub\Expected::once(function ($sessionId, $data) use ($expectedSessionId, $expectedPhrase) {
        $this->assertSame($expectedSessionId, $sessionId);
        $this->assertSame($expectedPhrase, $data['phrase']);
      }),
    ]);
    $phraseBuilder = $this->make(PhraseBuilder::class, ['build' => $expectedPhrase]);

    $captchaPhrase = new CaptchaPhrase($session, $phraseBuilder);
    $phrase = $captchaPhrase->createPhrase($expectedSessionId);
    $this->assertSame($expectedPhrase, $phrase);
  }

  public function testItReturnsPhrase(): void {
    $expectedSessionId = '123';
    $expectedPhrase = 'abc';

    $session = $this->make(CaptchaSession::class, [
      'getCaptchaHash' => Stub\Expected::once(function ($sessionId) use ($expectedSessionId, $expectedPhrase) {
        $this->assertSame($expectedSessionId, $sessionId);
        return ['phrase' => $expectedPhrase];
      }),
    ]);
    $phraseBuilder = $this->make(PhraseBuilder::class, ['build' => $expectedPhrase]);

    $captchaPhrase = new CaptchaPhrase($session, $phraseBuilder);
    $phrase = $captchaPhrase->getPhrase($expectedSessionId);
    $this->assertSame($expectedPhrase, $phrase);
  }

  public function testItStartsNewSessionsWithZeroAttempts(): void {
    $session = $this->make(CaptchaSession::class, [
      'getCaptchaHash' => false,
      'setCaptchaHash' => Stub\Expected::once(function ($sessionId, $data) {
        $this->assertSame(['phrase' => 'abc', 'attempts' => 0], $data);
      }),
    ]);
    $captchaPhrase = new CaptchaPhrase($session, $this->make(PhraseBuilder::class, ['build' => 'abc']));
    $captchaPhrase->createPhrase('123');
  }

  public function testItKeepsFailedAttemptsWhenThePhraseIsRefreshed(): void {
    $session = $this->make(CaptchaSession::class, [
      'getCaptchaHash' => ['phrase' => 'old', 'attempts' => 3],
      'setCaptchaHash' => Stub\Expected::once(function ($sessionId, $data) {
        $this->assertSame(['phrase' => 'new', 'attempts' => 3], $data);
      }),
    ]);
    $captchaPhrase = new CaptchaPhrase($session, $this->make(PhraseBuilder::class, ['build' => 'new']));
    $captchaPhrase->createPhrase('123');
  }

  public function testItReadsStoredValuesWithoutAttemptsAsZeroAttempts(): void {
    $stored = null;
    $session = $this->make(CaptchaSession::class, [
      'getCaptchaHash' => ['phrase' => 'old'],
      'setCaptchaHash' => function ($sessionId, $data) use (&$stored) {
        $stored = $data;
      },
    ]);
    $captchaPhrase = new CaptchaPhrase($session, $this->make(PhraseBuilder::class, ['build' => 'new']));
    $captchaPhrase->createPhrase('123');
    $this->assertSame(['phrase' => 'new', 'attempts' => 0], $stored);
    $this->assertSame(1, $captchaPhrase->registerFailedAttempt('123'));
  }

  public function testItCountsFailedAttemptsAndKeepsThePhrase(): void {
    $stored = null;
    $session = $this->make(CaptchaSession::class, [
      'getCaptchaHash' => ['phrase' => 'abc', 'attempts' => 4],
      'setCaptchaHash' => function ($sessionId, $data) use (&$stored) {
        $stored = $data;
      },
    ]);
    $captchaPhrase = new CaptchaPhrase($session, $this->make(PhraseBuilder::class));
    $this->assertSame(5, $captchaPhrase->registerFailedAttempt('123'));
    $this->assertSame(['phrase' => 'abc', 'attempts' => 5], $stored);
  }
}
