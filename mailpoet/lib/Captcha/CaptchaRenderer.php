<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use MailPoet\Config\Env;
use MailPoet\Util\Headers;
use MailPoetVendor\Gregwar\Captcha\CaptchaBuilder;

class CaptchaRenderer {
  const DEFAULT_WIDTH = 220;
  const DEFAULT_HEIGHT = 60;

  private CaptchaPhrase $phrase;

  public function __construct(
    CaptchaPhrase $phrase
  ) {
    $this->phrase = $phrase;
  }

  public function isSupported(): bool {
    return extension_loaded('gd') && function_exists('imagettftext');
  }

  public function renderAudio(string $sessionId): void {
    $audioPath = Env::$assetsPath . '/audio/';
    $phrase = $this->getPhrase($sessionId);

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

  public function renderImage(string $sessionId, $width = null, $height = null): void {
    if (!$this->isSupported()) {
      return;
    }

    $width = (isset($width) && $width > 0) ? intval($width) : self::DEFAULT_WIDTH;
    $height = (isset($height) && $height > 0) ? intval($height) : self::DEFAULT_HEIGHT;

    $fontNumbers = array_merge(range(0, 3), [5]); // skip font #4
    $fontNumber = $fontNumbers[mt_rand(0, count($fontNumbers) - 1)];

    $reflector = new \ReflectionClass(CaptchaBuilder::class);
    $captchaDirectory = dirname((string)$reflector->getFileName());
    $font = $captchaDirectory . '/Font/captcha' . $fontNumber . '.ttf';

    $phrase = $this->getPhrase($sessionId);
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
    return $this->phrase->createPhrase($sessionId);
  }

  private function getPhrase(string $sessionId): string {
    $phrase = $this->phrase->getPhrase($sessionId);
    if (!$phrase) {
      throw new \RuntimeException("No CAPTCHA phrase was generated.");
    }
    return $phrase;
  }
}
