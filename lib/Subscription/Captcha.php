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

  /** @var WPFunctions */
  private $wp;

  /** @var CaptchaSession  */
  private $captchaSession;

  public function __construct(WPFunctions $wp = null, CaptchaSession $captchaSession = null) {
    if ($wp === null) {
      $wp = new WPFunctions;
    }
    if ($captchaSession === null) {
      $captchaSession = new CaptchaSession($wp);
    }
    $this->wp = $wp;
    $this->captchaSession = $captchaSession;
  }

  public function isSupported() {
    return extension_loaded('gd') && function_exists('imagettftext');
  }

  public function isRequired($subscriberEmail = null) {
    if ($this->isUserExemptFromCaptcha()) {
      return false;
    }

    // Check limits per recipient
    $subscriptionCaptchaRecipientLimit = $this->wp->applyFilters('mailpoet_subscription_captcha_recipient_limit', 1);
    if ($subscriberEmail) {
      $subscriber = Subscriber::where('email', $subscriberEmail)->findOne();
      if ($subscriber instanceof Subscriber
        && $subscriber->countConfirmations >= $subscriptionCaptchaRecipientLimit
      ) {
        return true;
      }
    }

    // Check limits per IP address
    $subscriptionCaptchaWindow = $this->wp->applyFilters('mailpoet_subscription_captcha_window', MONTH_IN_SECONDS);

    $subscriberIp = Helpers::getIP();

    if (empty($subscriberIp)) {
      return false;
    }

    $subscriptionCount = SubscriberIP::where('ip', $subscriberIp)
      ->whereRaw(
        '(`created_at` >= NOW() - INTERVAL ? SECOND)',
        [(int)$subscriptionCaptchaWindow]
      )->count();

    if ($subscriptionCount > 0) {
      return true;
    }

    return false;
  }

  private function isUserExemptFromCaptcha() {
    if (!$this->wp->isUserLoggedIn()) {
      return false;
    }
    $user = $this->wp->wpGetCurrentUser();
    $roles = $this->wp->applyFilters('mailpoet_subscription_captcha_exclude_roles', ['administrator', 'editor']);
    return !empty(array_intersect($roles, (array)$user->roles));
  }

  public function renderImage($width = null, $height = null, $sessionId = null, $return = false) {
    if (!$this->isSupported()) {
      return false;
    }

    $fontNumbers = array_merge(range(0, 3), [5]); // skip font #4
    $fontNumber = $fontNumbers[mt_rand(0, count($fontNumbers) - 1)];

    $reflector = new \ReflectionClass(CaptchaBuilder::class);
    $captchaDirectory = dirname((string)$reflector->getFileName());
    $font = $captchaDirectory . '/Font/captcha' . $fontNumber . '.ttf';

    $builder = CaptchaBuilder::create()
      ->setBackgroundColor(255, 255, 255)
      ->setTextColor(1, 1, 1)
      ->setMaxBehindLines(0)
      ->build($width ?: 220, $height ?: 60, $font);

    $this->captchaSession->init($sessionId);
    $this->captchaSession->setCaptchaHash($builder->getPhrase());

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
