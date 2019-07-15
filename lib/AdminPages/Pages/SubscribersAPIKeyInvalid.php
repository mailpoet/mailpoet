<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Models\Subscriber;

if (!defined('ABSPATH')) exit;

class SubscribersAPIKeyInvalid {
  /** @var PageRenderer */
  private $page_renderer;

  function __construct(PageRenderer $page_renderer) {
    $this->page_renderer = $page_renderer;
  }

  function render() {
    $this->page_renderer->displayPage('invalidkey.html', [
      'subscriber_count' => Subscriber::getTotalSubscribers(),
    ]);
  }
}
