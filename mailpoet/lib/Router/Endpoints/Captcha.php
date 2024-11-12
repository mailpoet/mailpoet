<?php declare(strict_types = 1);

namespace MailPoet\Router\Endpoints;

use MailPoet\Captcha\PageRenderer;
use MailPoet\Config\AccessControl;

class Captcha {
  const ENDPOINT = 'captcha';
  const ACTION_RENDER = 'render';

  private PageRenderer $renderer;

  public $allowedActions = [
    self::ACTION_RENDER,
  ];

  public $permissions = [
    'global' => AccessControl::NO_ACCESS_RESTRICTION,
  ];

  // phpcs:ignore
  public function __construct(PageRenderer $renderer) {
    $this->renderer = $renderer;
  }

  public function render($data) {
    $this->renderer->render($data);
  }
}
