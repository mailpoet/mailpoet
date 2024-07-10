<?php declare(strict_types = 1);

namespace MailPoet\Subscription\Captcha;

use MailPoetVendor\Gregwar\Captcha\PhraseBuilder;

class CaptchaPhrase {
  private CaptchaSession $session;
  private PhraseBuilder $phraseBuilder;

  public function __construct(
    CaptchaSession $session,
    PhraseBuilder $phraseBuilder = null
  ) {
    $this->session = $session;
    $this->phraseBuilder = $phraseBuilder ?? new PhraseBuilder();
  }

  public function createPhrase(string $sessionId): string {
    $this->session->init($sessionId);
    $storage = ['phrase' => $this->phraseBuilder->build()];
    $this->session->setCaptchaHash($storage);
    return $storage['phrase'];
  }

  public function getPhrase(string $sessionId): ?string {
    $this->session->init($sessionId);
    $storage = $this->session->getCaptchaHash();
    return (isset($storage['phrase']) && is_string($storage['phrase'])) ? $storage['phrase'] : null;
  }
}
