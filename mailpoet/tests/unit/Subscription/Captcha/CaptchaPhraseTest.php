<?php declare(strict_types = 1);

namespace MailPoet\Subscription\Captcha;

use Codeception\Stub;
use MailPoetVendor\Gregwar\Captcha\PhraseBuilder;

class CaptchaPhraseTest extends \MailPoetUnitTest {
  public function testItCreatesPhrase(): void {
    $expectedPhrase = 'abc';

    $session = $this->make(CaptchaSession::class, [
      'setCaptchaHash' => Stub\Expected::once(function ($data) use ($expectedPhrase) {
        $this->assertSame($expectedPhrase, $data['phrase']);
      }),
    ]);
    $phraseBuilder = $this->make(PhraseBuilder::class, ['build' => $expectedPhrase]);

    $captchaPhrase = new CaptchaPhrase($session, $phraseBuilder);
    $phrase = $captchaPhrase->createPhrase('123');
    $this->assertSame($expectedPhrase, $phrase);
  }

  public function testItReturnsPhrase(): void {
    $expectedPhrase = 'abc';

    $session = $this->make(CaptchaSession::class, [
      'getCaptchaHash' => Stub\Expected::once(function () use ($expectedPhrase) {
        return ['phrase' => $expectedPhrase];
      }),
    ]);
    $phraseBuilder = $this->make(PhraseBuilder::class, ['build' => $expectedPhrase]);

    $captchaPhrase = new CaptchaPhrase($session, $phraseBuilder);
    $phrase = $captchaPhrase->getPhrase('123');
    $this->assertSame($expectedPhrase, $phrase);
  }
}
