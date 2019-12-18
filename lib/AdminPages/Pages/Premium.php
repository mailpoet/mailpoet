<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Models\Subscriber;
use MailPoet\WP\Functions as WPFunctions;

class Premium {
  /** @var PageRenderer */
  private $page_renderer;

  /** @var WPFunctions */
  private $wp;

  function __construct(PageRenderer $page_renderer, WPFunctions $wp) {
    $this->page_renderer = $page_renderer;
    $this->wp = $wp;
  }

  function render() {
    $data = [
      'current_wp_user' => $this->wp->wpGetCurrentUser()->to_array(),
      'subscriber_count' => Subscriber::getTotalSubscribers(),
    ];
    $this->page_renderer->displayPage('premium.html', $data);
  }
}
