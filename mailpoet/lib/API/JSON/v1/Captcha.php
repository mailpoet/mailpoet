<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Captcha\CaptchaHooks;
use MailPoet\Captcha\CaptchaSession;
use MailPoet\Captcha\CaptchaSessionLimitException;
use MailPoet\Captcha\CaptchaUrlFactory;
use MailPoet\Config\AccessControl;
use MailPoet\WP\Functions as WPFunctions;

class Captcha extends APIEndpoint {
  private const MAX_STASH_SIZE = 8192;

  private CaptchaSession $captchaSession;
  private CaptchaUrlFactory $urlFactory;
  private CaptchaHooks $captchaHooks;
  private WPFunctions $wp;

  public $permissions = [
    'global' => AccessControl::NO_ACCESS_RESTRICTION,
  ];

  public function __construct(
    CaptchaSession $captchaSession,
    CaptchaUrlFactory $urlFactory,
    CaptchaHooks $captchaHooks,
    WPFunctions $wp
  ) {
    $this->captchaSession = $captchaSession;
    $this->urlFactory = $urlFactory;
    $this->captchaHooks = $captchaHooks;
    $this->wp = $wp;
  }

  public function render(array $data = []) {
    $referrer = $data['referrer_form'] ?? null;
    $isRegisterForm = in_array($referrer, [CaptchaUrlFactory::REFERER_WP_FORM, CaptchaUrlFactory::REFERER_WC_FORM], true);
    if (!$isRegisterForm || !$this->captchaHooks->isEnabled()) {
      return $this->badRequest();
    }

    // The form fields stay on the server and are shown once on the CAPTCHA page.
    // Only the session ID and the referrer travel in the URL.
    $stash = array_filter($data, 'is_scalar');
    unset($stash['captcha_session_id'], $stash['rendered'], $stash['action_url']);
    if (strlen(serialize($stash)) > self::MAX_STASH_SIZE) {
      return $this->badRequest();
    }

    try {
      $this->captchaSession->registerNewSession();
    } catch (CaptchaSessionLimitException $e) {
      return $this->badRequest([APIError::BAD_REQUEST => $e->getMessage()]);
    }
    $sessionId = $this->captchaSession->generateSessionId();
    $this->captchaSession->setFormData($sessionId, $stash);

    $captchaUrl = $this->urlFactory->getCaptchaUrl([
      'captcha_session_id' => $sessionId,
      'referrer_form' => $referrer,
    ]);
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
