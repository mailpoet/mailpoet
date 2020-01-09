<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Models\Subscriber;
use MailPoet\WP\Functions as WPFunctions;

class Premium {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var WPFunctions */
  private $wp;

  public function __construct(PageRenderer $pageRenderer, WPFunctions $wp) {
    $this->pageRenderer = $pageRenderer;
    $this->wp = $wp;
  }

  public function render() {
    $data = [
      'current_wp_user' => $this->wp->wpGetCurrentUser()->to_array(),
      'subscriber_count' => Subscriber::getTotalSubscribers(),
    ];
    $this->pageRenderer->displayPage('premium.html', $data);
  }
}
