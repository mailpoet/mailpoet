<?php declare(strict_types = 1);

namespace MailPoet\Subscription\Captcha;

use Codeception\Stub;
use MailPoetVendor\Gregwar\Captcha\PhraseBuilder;

class CaptchaPhraseTest extends \MailPoetUnitTest {
  public function testItGeneratesPhraseWhenNewSession() {

    $expectedPhrase = 'abc';
    $session = Stub::make(
      CaptchaSession::class,
      [
        'init' => function($sessionId) {
        },
        'getCaptchaHash' => false,
        'setCaptchaHash' => Stub\Expected::once(function($data) use ($expectedPhrase) {
          expect($data['phrase'])->equals($expectedPhrase);
        }),
      ],
      $this
    );
    $phraseBuilder = Stub::make(
      PhraseBuilder::class,
      [
        'build' => Stub\Expected::once(function() use ($expectedPhrase) { return $expectedPhrase; 
        }),
      ],
      $this
    );
    $testee = new CaptchaPhrase($session, $phraseBuilder);
    $phrase = $testee->getPhraseForType('type-a', null);
    expect($phrase)->equals($expectedPhrase);
  }

  public function testItRegeneratesPhraseWhenCalledTwice() {

    $expectedFirstPhrase = 'abc';
    $expectedSecondPhrase = 'def';
    $session = Stub::make(
      CaptchaSession::class,
      [
        'init' => function($sessionId) {
        },
        'getCaptchaHash' => false,
        'setCaptchaHash' => Stub\Expected::exactly(2, function($data) use ($expectedFirstPhrase, $expectedSecondPhrase) {
          static $count;
          if (!$count) {
            $count = 1;
            expect($data['phrase'])->equals($expectedFirstPhrase);
            return;
          }
          expect($data['phrase'])->equals($expectedSecondPhrase);
        }),
      ],
      $this
    );
    $phraseBuilder = Stub::make(
      PhraseBuilder::class,
      [
        'build' => Stub\Expected::exactly(2, function() use ($expectedFirstPhrase, $expectedSecondPhrase) {
          static $count;
          if (!$count) {
            $count = 1;
            return $expectedFirstPhrase;
          }
          return $expectedSecondPhrase;
        }),
      ],
      $this
    );
    $testee = new CaptchaPhrase($session, $phraseBuilder);
    $phrase = $testee->getPhraseForType('type-a', null);
    expect($phrase)->equals($expectedFirstPhrase);
    $phrase = $testee->getPhraseForType('type-a', null);
    expect($phrase)->equals($expectedSecondPhrase);
  }

  public function testItKeepsDifferentTypesInSync() {

    $phrase = 'abc';
    $expectedFirstStorage = [
      'phrase' => $phrase,
      'total_loaded' => 1,
      'loaded_by_types' => [
        'type-a' => 1,
      ],
    ];
    $session = Stub::make(
      CaptchaSession::class,
      [
        'init' => function($sessionId) {
        },
        'getCaptchaHash' => Stub\Expected::exactly(2, function() use ($expectedFirstStorage){
          static $count;
          if (!$count) {
            $count = 1;
            return false;
          }
          return $expectedFirstStorage;
        }),
        'setCaptchaHash' => Stub\Expected::exactly(2, function($storage) use ($expectedFirstStorage) {
          static $count;
          if ($count) {
            return;
          }
          $count = 1;
          expect($storage)->equals($expectedFirstStorage);
        }),
      ],
      $this
    );
    $phraseBuilder = Stub::make(
      PhraseBuilder::class,
      [
        'build' => Stub\Expected::once(function() use ($phrase) { return $phrase; 
        }),
      ],
      $this
    );
    $testee = new CaptchaPhrase($session, $phraseBuilder);
    $phraseTypeA = $testee->getPhraseForType('type-a', null);
    $phraseTypeB = $testee->getPhraseForType('type-b', null);
    expect($phraseTypeA)->equals($phraseTypeB);
  }
}
