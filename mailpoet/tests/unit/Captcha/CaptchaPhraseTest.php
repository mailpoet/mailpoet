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
}
