<?php

namespace MailPoet\Subscription;

use MailPoetVendor\Gregwar\Captcha\CaptchaBuilder;

class Captcha {
  const TYPE_BUILTIN = 'built-in';
  const TYPE_RECAPTCHA = 'recaptcha';
  const TYPE_DISABLED = null;

  const SESSION_KEY = 'mailpoet_captcha';
  const SESSION_FORM_KEY = 'mailpoet_captcha_form';

  function isSupported() {
    return extension_loaded('gd') && function_exists('imagettftext');
  }

  function renderImage($width = null, $height = null) {
    if (!$this->isSupported()) {
      return false;
    }

    $font_numbers = array_merge(range(0, 3), [5]); // skip font #4
    $font_number = $font_numbers[mt_rand(0, count($font_numbers) - 1)];

    $reflector = new \ReflectionClass(CaptchaBuilder::class);
    $captcha_directory = dirname($reflector->getFileName());
    $font = $captcha_directory . '/Font/captcha' . $font_number . '.ttf';

    $builder = CaptchaBuilder::create()
      ->setBackgroundColor(255, 255, 255)
      ->setTextColor(1, 1, 1)
      ->setMaxBehindLines(0)
      ->build($width ?: 220, $height ?: 60, $font);

    $_SESSION[self::SESSION_KEY] = $builder->getPhrase();

    header('Content-Type: image/jpeg');
    $builder->output();
    exit;
  }
}
