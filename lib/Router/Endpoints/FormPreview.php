<?php

namespace MailPoet\Router\Endpoints;

use MailPoet\Config\AccessControl;
use MailPoet\WP\Functions as WPFunctions;

class FormPreview {
  const ENDPOINT = 'form_preview';
  const ACTION_VIEW = 'view';

  /** @var WPFunctions  */
  private $wp;

  /** @var array|null */
  private $data;

  public $allowedActions = [self::ACTION_VIEW];
  public $permissions = [
    'global' => AccessControl::NO_ACCESS_RESTRICTION,
  ];

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function view(array $data) {
    $this->data = $data;
    $this->wp->addFilter('the_content', [$this,'renderContent'], 10);
    $this->wp->addFilter('the_title', [$this,'renderTitle'], 10);
    $this->wp->addFilter('show_admin_bar', function () {
      return false;
    });
  }

  public function renderContent(): string {
    return '<h1>Todo render form</h1>';
  }

  public function renderTitle(): string {
    return __('Sample page to preview your form', 'mailpoet');
  }
}
