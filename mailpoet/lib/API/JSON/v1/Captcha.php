<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\Captcha\CaptchaSession;
use MailPoet\Captcha\CaptchaUrlFactory;
use MailPoet\Config\AccessControl;
use MailPoet\WP\Functions as WPFunctions;

class Captcha extends APIEndpoint {
  private CaptchaSession $captchaSession;
  private CaptchaUrlFactory $urlFactory;
  private WPFunctions $wp;

  public $permissions = [
    'global' => AccessControl::NO_ACCESS_RESTRICTION,
  ];

  public function __construct(
    CaptchaSession $captchaSession,
    CaptchaUrlFactory $urlFactory,
    WPFunctions $wp
  ) {
    $this->captchaSession = $captchaSession;
    $this->urlFactory = $urlFactory;
    $this->wp = $wp;
  }

  public function render(array $data = []) {
    $sessionId = $this->captchaSession->generateSessionId();
    $data = array_merge($data, ['captcha_session_id' => $sessionId]);
    $captchaUrl = $this->urlFactory->getCaptchaUrl($data);
    $this->allowCaptchaPageHost($captchaUrl);

    return $this->redirectResponse($captchaUrl);
  }

  /**
   * The captcha page permalink may live on a different host than home_url()
   * (multilingual domains, mapped domains). The host comes from the configured
   * page's permalink, not from request data, so it is safe to allow.
   */
  private function allowCaptchaPageHost(string $captchaUrl): void {
    $host = $this->wp->wpParseUrl($captchaUrl, PHP_URL_HOST);
    if (!is_string($host) || $host === '') {
      return;
    }
    $this->wp->addFilter('allowed_redirect_hosts', function ($hosts) use ($host) {
      $hosts = is_array($hosts) ? $hosts : [];
      if (!in_array($host, $hosts, true)) {
        $hosts[] = $host;
      }
      return $hosts;
    });
  }
}
