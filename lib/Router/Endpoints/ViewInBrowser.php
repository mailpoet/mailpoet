<?php

namespace MailPoet\Router\Endpoints;

use MailPoet\Config\AccessControl;
use MailPoet\Newsletter\ViewInBrowser\ViewInBrowserController;
use MailPoet\WP\Functions as WPFunctions;

class ViewInBrowser {
  const ENDPOINT = 'view_in_browser';
  const ACTION_VIEW = 'view';

  public $allowedActions = [self::ACTION_VIEW];
  public $permissions = [
    'global' => AccessControl::NO_ACCESS_RESTRICTION,
  ];

  /** @var ViewInBrowserController */
  private $viewInBrowserController;

  public function __construct(ViewInBrowserController $viewInBrowserController) {
    $this->viewInBrowserController = $viewInBrowserController;
  }

  public function view(array $data) {
    try {
      $viewData = $this->viewInBrowserController->view($data);
      $this->_displayNewsletter($viewData);
    } catch (\InvalidArgumentException $e) {
      $this->_abort();
    }
  }

  public function _displayNewsletter($result) {
    header('Content-Type: text/html; charset=utf-8');
    echo $result;
    exit;
  }

  public function _abort() {
    WPFunctions::get()->statusHeader(404);
    exit;
  }
}
