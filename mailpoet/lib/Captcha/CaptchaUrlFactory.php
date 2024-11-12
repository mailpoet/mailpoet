<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use MailPoet\Router\Endpoints\Captcha as CaptchaEndpoint;
use MailPoet\Router\Router;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class CaptchaUrlFactory {
  private WPFunctions $wp;
  private SettingsController $settings;

  public function __construct(
    WPFunctions $wp,
    SettingsController $settings
  ) {
    $this->wp = $wp;
    $this->settings = $settings;
  }

  public function getCaptchaUrl(string $sessionId) {
    $post = $this->wp->getPost($this->settings->get('subscription.pages.captcha'));
    $url = $this->wp->getPermalink($post);

    $params = [
      Router::NAME,
      'endpoint=' . CaptchaEndpoint::ENDPOINT,
      'action=' . CaptchaEndpoint::ACTION_RENDER,
      'data=' . Router::encodeRequestData(['captcha_session_id' => $sessionId]),
    ];

    $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . join('&', $params);
    return $url;
  }
}
