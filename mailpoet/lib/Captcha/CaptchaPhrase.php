<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use MailPoetVendor\Gregwar\Captcha\PhraseBuilder;

class CaptchaPhrase {
  const MAX_ATTEMPTS = 10;

  private CaptchaSession $session;
  private PhraseBuilder $phraseBuilder;

  public function __construct(
    CaptchaSession $session,
    ?PhraseBuilder $phraseBuilder = null
  ) {
    $this->session = $session;
    $this->phraseBuilder = $phraseBuilder ?? new PhraseBuilder();
  }

  public function createPhrase(string $sessionId): string {
    $storage = [
      'phrase' => $this->phraseBuilder->build(),
      'attempts' => $this->getAttempts($sessionId),
    ];
    $this->session->setCaptchaHash($sessionId, $storage);
    return $storage['phrase'];
  }

  public function getPhrase(string $sessionId): ?string {
    $storage = $this->session->getCaptchaHash($sessionId);
    return (isset($storage['phrase']) && is_string($storage['phrase'])) ? $storage['phrase'] : null;
  }

  /**
   * Counts a wrong answer for the session and returns the new total.
   */
  public function registerFailedAttempt(string $sessionId): int {
    $storage = $this->session->getCaptchaHash($sessionId);
    if (!is_array($storage)) {
      return self::MAX_ATTEMPTS;
    }
    $storage['attempts'] = $this->getAttempts($sessionId) + 1;
    $this->session->setCaptchaHash($sessionId, $storage);
    return $storage['attempts'];
  }

  public function consume(string $sessionId): void {
    $this->session->deleteCaptchaHash($sessionId);
  }

  private function getAttempts(string $sessionId): int {
    $storage = $this->session->getCaptchaHash($sessionId);
    return (is_array($storage) && is_int($storage['attempts'] ?? null)) ? $storage['attempts'] : 0;
  }
}
