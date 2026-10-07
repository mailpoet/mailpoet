<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use MailPoet\Config\Env;
use MailPoet\Util\Headers;
use MailPoetVendor\Gregwar\Captcha\CaptchaBuilder;

class CaptchaRenderer {
  const DEFAULT_WIDTH = 220;
  const DEFAULT_HEIGHT = 60;

  private CaptchaPhrase $phrase;
  private CaptchaSession $session;

  public function __construct(
    CaptchaPhrase $phrase,
    CaptchaSession $session
  ) {
    $this->phrase = $phrase;
    $this->session = $session;
  }

  public function isSupported(): bool {
    return extension_loaded('gd') && function_exists('imagettftext');
  }

  public function renderAudio(string $sessionId): void {
    $audioPath = Env::$assetsPath . '/audio/';
    $phrase = $this->phrase->getPhrase($sessionId);
    if (!$phrase) {
      return;
    }

    $files = [];
    foreach (str_split($phrase) as $character) {
      $file = $audioPath . strtolower($character) . '.mp3';
      if (!file_exists($file)) {
        throw new \RuntimeException("File not found.");
      }
      $files[] = $file;
    }

    Headers::setNoCacheHeaders();
    header('Content-Type: audio/mpeg');
    foreach ($files as $file) {
      readfile($file);
    }
  }

  public function renderImage(string $sessionId): void {
    if (!$this->isSupported()) {
      return;
    }

    $width = self::DEFAULT_WIDTH;
    $height = self::DEFAULT_HEIGHT;

    $fontNumbers = array_merge(range(0, 3), [5]); // skip font #4
    $fontNumber = $fontNumbers[mt_rand(0, count($fontNumbers) - 1)];

    $reflector = new \ReflectionClass(CaptchaBuilder::class);
    $captchaDirectory = dirname((string)$reflector->getFileName());
    $font = $captchaDirectory . '/Font/captcha' . $fontNumber . '.ttf';

    $phrase = $this->phrase->getPhrase($sessionId);
    if (!$phrase) {
      return;
    }

    $builder = CaptchaBuilder::create($phrase)
      ->setBackgroundColor(255, 255, 255)
      ->setTextColor(1, 1, 1)
      ->setMaxBehindLines(0)
      ->build($width, $height, $font);

    Headers::setNoCacheHeaders();
    header('Content-Type: image/jpeg');
    $builder->output();
  }

  public function refreshPhrase(string $sessionId): string {
    if (!$this->session->exists($sessionId)) {
      return '';
    }
    return $this->phrase->createPhrase($sessionId);
  }
}
