<?php

namespace MailPoet\Util\Notices;

use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\Captcha;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;

class HeadersAlreadySentNotice {

  const DISMISS_NOTICE_TIMEOUT_SECONDS = YEAR_IN_SECONDS;
  const OPTION_NAME = 'dismissed-headers-already-sent-notice';

  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  public function __construct(SettingsController $settings, WPFunctions $wp) {
    $this->settings = $settings;
    $this->wp = $wp;
  }

  public function init($shouldDisplay) {
    if (!$shouldDisplay) {
      return null;
    }
    $captchaEnabled = $this->settings->get('captcha.type') === Captcha::TYPE_BUILTIN;
    $trackingEnabled = $this->settings->get('tracking.enabled');
    if ($this->areHeadersAlreadySent()) {
      return $this->display($captchaEnabled, $trackingEnabled);
    }
  }

  public function areHeadersAlreadySent() {
    return !get_transient(self::OPTION_NAME) && $this->headersSent();
  }

  protected function headersSent() {
    return headers_sent();
  }

  public function display($captchaEnabled, $trackingEnabled) {
    if (!$captchaEnabled && !$trackingEnabled) {
      return null;
    }

    $errorString = __('It looks like there\'s an issue with some of the PHP files on your website which is preventing MailPoet from functioning correctly. If not resolved, you may experience:', 'mailpoet');
    $errorStringTracking = __('Inaccurate tracking of email opens and clicks', 'mailpoet');
    $errorStringCaptcha = __('CAPTCHA not rendering correctly', 'mailpoet');
    $errorString = $errorString . '<br>'
      . ($trackingEnabled ? ('<br> - ' . $errorStringTracking) : '')
      . ($captchaEnabled ? ('<br> - ' . $errorStringCaptcha) : '');

    $howToResolveString = __('[link]Learn how to fix this issue and restore functionality[/link]', 'mailpoet');
    $error = $errorString . '<br><br>' . Helpers::replaceLinkTags($howToResolveString, 'https://kb.mailpoet.com/article/325-the-captcha-image-doesnt-show-up', [
      'target' => '_blank',
      'data-beacon-article' => '5f20fb5904286306f8078acb',
      'class' => 'button-primary',
    ]);

    $extraClasses = 'mailpoet-dismissible-notice is-dismissible';

    return Notice::displayError($error, $extraClasses, self::OPTION_NAME, true, false);
  }

  public function disable() {
    $this->wp->setTransient(self::OPTION_NAME, true, self::DISMISS_NOTICE_TIMEOUT_SECONDS);
  }
}
