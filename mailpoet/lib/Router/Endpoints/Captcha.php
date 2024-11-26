<?php declare(strict_types = 1);

namespace MailPoet\Router\Endpoints;

use MailPoet\Captcha\CaptchaRenderer;
use MailPoet\Captcha\PageRenderer;
use MailPoet\Config\AccessControl;

class Captcha {
  const ENDPOINT = 'captcha';
  const ACTION_RENDER = 'render';
  const ACTION_IMAGE = 'image';
  const ACTION_AUDIO = 'audio';
  const ACTION_REFRESH = 'refresh';

  private PageRenderer $pageRenderer;
  private CaptchaRenderer $captchaRenderer;

  public $allowedActions = [
    self::ACTION_RENDER,
    self::ACTION_IMAGE,
    self::ACTION_AUDIO,
    self::ACTION_REFRESH,
  ];

  public $permissions = [
    'global' => AccessControl::NO_ACCESS_RESTRICTION,
  ];

  public function __construct(
    PageRenderer $renderer,
    CaptchaRenderer $captchaRenderer
  ) {
    $this->pageRenderer = $renderer;
    $this->captchaRenderer = $captchaRenderer;
  }

  public function render($data) {
    $this->pageRenderer->render($data);
  }

  public function image($data) {
    $width = !empty($data['width']) ? (int)$data['width'] : null;
    $height = !empty($data['height']) ? (int)$data['height'] : null;
    $sessionId = $data['captcha_session_id'] ?? null;
    if (!$sessionId) {
      return;
    }

    $this->captchaRenderer->renderImage($sessionId, $width, $height);
    exit;
  }

  public function audio($data) {
    $sessionId = $data['captcha_session_id'] ?? null;
    if (!$sessionId) {
      return;
    }

    $this->captchaRenderer->renderAudio($sessionId);
    exit;
  }

  public function refresh($data) {
    $sessionId = $data['captcha_session_id'] ?? null;
    if (!$sessionId) {
      return;
    }

    $this->captchaRenderer->refreshPhrase($sessionId);
  }
}
