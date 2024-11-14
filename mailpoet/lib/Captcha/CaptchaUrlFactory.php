<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use MailPoet\Router\Endpoints\Captcha as CaptchaEndpoint;
use MailPoet\Router\Router;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class CaptchaUrlFactory {
  private WPFunctions $wp;
  private SettingsController $settings;

  const REFERER_MP_FORM = 'mp_form';

  public function __construct(
    WPFunctions $wp,
    SettingsController $settings
  ) {
    $this->wp = $wp;
    $this->settings = $settings;
  }

  public function getCaptchaUrlForMPForm(string $sessionId) {
    return $this->getCaptchaUrl($sessionId, self::REFERER_MP_FORM);
  }

  public function getCaptchaImageUrl(int $width, int $height, string $sessionId) {
    return $this->getUrl(
      CaptchaEndpoint::ACTION_IMAGE,
      [
        'width' => $width,
        'height' => $height,
        'captcha_session_id' => $sessionId,
      ]
    );
  }

  public function getCaptchaAudioUrl(string $sessionId) {
    return $this->getUrl(
      CaptchaEndpoint::ACTION_AUDIO,
      [
        'cacheBust' => time(),
        'captcha_session_id' => $sessionId,
      ]
    );
  }

  private function getUrl(string $action, array $data) {
    $post = $this->wp->getPost($this->settings->get('subscription.pages.captcha'));
    $url = $this->wp->getPermalink($post);

    $params = [
      Router::NAME,
      'endpoint=' . CaptchaEndpoint::ENDPOINT,
      'action=' . $action,
      'data=' . Router::encodeRequestData($data),
    ];

    $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . join('&', $params);
    return $url;
  }

  private function getCaptchaUrl(string $sessionId, string $referer) {
    return $this->getUrl(
      CaptchaEndpoint::ACTION_RENDER,
      [
        'captcha_session_id' => $sessionId,
        'referrer_form' => $referer,
      ]
    );
  }
}
