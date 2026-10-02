<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use MailPoet\Router\Endpoints\Captcha as CaptchaEndpoint;
use MailPoet\Router\Router;
use MailPoet\Settings\MailPoetPageResolver;
use MailPoet\Settings\Pages;
use MailPoet\WP\Functions as WPFunctions;

class CaptchaUrlFactory {
  private WPFunctions $wp;
  private MailPoetPageResolver $pageResolver;

  const REFERER_MP_FORM = 'mp_form';
  const REFERER_WP_FORM = 'wp_register_form';
  const REFERER_WC_FORM = 'wc_register_form';

  public function __construct(
    WPFunctions $wp,
    MailPoetPageResolver $pageResolver
  ) {
    $this->wp = $wp;
    $this->pageResolver = $pageResolver;
  }

  public function getCaptchaUrl(array $data) {
    return $this->getUrl(CaptchaEndpoint::ACTION_RENDER, $data);
  }

  public function getCaptchaUrlForMPForm(string $sessionId) {
    $data = [
      'captcha_session_id' => $sessionId,
      'referrer_form' => self::REFERER_MP_FORM,
    ];

    return $this->getCaptchaUrl($data);
  }

  public function getCaptchaImageUrl(string $sessionId) {
    return $this->getUrl(
      CaptchaEndpoint::ACTION_IMAGE,
      [
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

  public function getCaptchaPreviewUrl($post = null) {
    if ($post === null) {
      $post = $this->getCaptchaPage();
    }

    $url = $this->getBaseUrl($post);

    // Use preview session ID for preview
    $data = [
      'captcha_session_id' => 'preview',
      'referrer_form' => self::REFERER_MP_FORM,
      'preview' => 1,
    ];

    $params = [
      Router::NAME,
      'endpoint=' . CaptchaEndpoint::ENDPOINT,
      'action=' . CaptchaEndpoint::ACTION_RENDER,
      'data=' . Router::encodeRequestData($data),
    ];

    $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . join('&', $params);

    $urlParams = parse_url($url);
    if (!is_array($urlParams) || empty($urlParams['scheme'])) {
      $url = $this->wp->getBloginfo('url') . $url;
    }

    return $url;
  }

  private function getUrl(string $action, array $data) {
    $url = $this->getBaseUrl($this->getCaptchaPage());

    $params = [
      Router::NAME,
      'endpoint=' . CaptchaEndpoint::ENDPOINT,
      'action=' . $action,
      'data=' . Router::encodeRequestData($data),
    ];

    $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . join('&', $params);
    return $url;
  }

  private function getCaptchaPage(): ?\WP_Post {
    return $this->pageResolver->getPage('subscription.pages.captcha', Pages::PAGE_CAPTCHA);
  }

  private function getBaseUrl(?\WP_Post $post): string {
    $url = $post ? $this->wp->getPermalink($post) : false;
    return is_string($url) && $url !== '' ? $url : $this->wp->homeUrl('/');
  }
}
