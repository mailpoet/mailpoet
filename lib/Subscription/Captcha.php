<?php

namespace MailPoet\Subscription;

use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberIP;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Gregwar\Captcha\CaptchaBuilder;

class Captcha {
  const TYPE_BUILTIN = 'built-in';
  const TYPE_RECAPTCHA = 'recaptcha';
  const TYPE_DISABLED = null;

  const SESSION_KEY = 'mailpoet_captcha';
  const SESSION_FORM_KEY = 'mailpoet_captcha_form';

  /** @var WPFunctions */
  private $wp;

  function __construct(WPFunctions $wp = null) {
    if ($wp === null) {
      $wp = new WPFunctions;
    }
    $this->wp = $wp;
  }

  function isSupported() {
    return extension_loaded('gd') && function_exists('imagettftext');
  }

  function isRequired($subscriber_email = null) {
    if ($this->wp->isUserLoggedIn()) {
      return false;
    }

    // Check limits per recipient
    $subscription_captcha_recipient_limit = $this->wp->applyFilters('mailpoet_subscription_captcha_recipient_limit', 1);
    if ($subscriber_email) {
      $subscriber = Subscriber::where('email', $subscriber_email)->findOne();
      if ($subscriber instanceof Subscriber
        && $subscriber->count_confirmations >= $subscription_captcha_recipient_limit
      ) {
        return true;
      }
    }

    // Check limits per IP address
    $subscription_captcha_window = $this->wp->applyFilters('mailpoet_subscription_captcha_window', MONTH_IN_SECONDS);

    $subscriber_ip = Helpers::getIP();

    if (empty($subscriber_ip)) {
      return false;
    }

    $subscription_count = SubscriberIP::where('ip', $subscriber_ip)
      ->whereRaw(
        '(`created_at` >= NOW() - INTERVAL ? SECOND)',
        [(int)$subscription_captcha_window]
      )->count();

    if ($subscription_count > 0) {
      return true;
    }

    return false;
  }

  function renderImage($width = null, $height = null, $return = false) {
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

    if ($return) {
      return $builder->get();
    }

    header("Expires: Sat, 01 Jan 2019 01:00:00 GMT"); // time in the past
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header('X-Cache-Enabled: False');
    header('X-LiteSpeed-Cache-Control: no-cache');

    header('Content-Type: image/jpeg');
    $builder->output();
    exit;
  }
}
