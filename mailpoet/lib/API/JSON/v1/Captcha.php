<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\Captcha\CaptchaSession;
use MailPoet\Captcha\CaptchaUrlFactory;
use MailPoet\Config\AccessControl;

class Captcha extends APIEndpoint {
  private CaptchaSession $captchaSession;
  private CaptchaUrlFactory $urlFactory;

  public $permissions = [
    'global' => AccessControl::NO_ACCESS_RESTRICTION,
  ];

  public function __construct(
    CaptchaSession $captchaSession,
    CaptchaUrlFactory $urlFactory
  ) {
    $this->captchaSession = $captchaSession;
    $this->urlFactory = $urlFactory;
  }

  public function render(array $data = []) {
    $sessionId = $this->captchaSession->generateSessionId();
    $data = array_merge($data, ['captcha_session_id' => $sessionId]);
    $captchaUrl = $this->urlFactory->getCaptchaUrl($data);

    return $this->redirectResponse($captchaUrl);
  }
}
