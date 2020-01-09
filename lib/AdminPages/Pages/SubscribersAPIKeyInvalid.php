<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Models\Subscriber;
use MailPoet\WP\Functions as WPFunctions;

class SubscribersAPIKeyInvalid {
  /** @var PageRenderer */
  private $page_renderer;

  /** @var WPFunctions */
  private $wp;

  public function __construct(PageRenderer $pageRenderer, WPFunctions $wp) {
    $this->pageRenderer = $pageRenderer;
    $this->wp = $wp;
  }

  public function render() {
    $this->pageRenderer->displayPage('invalidkey.html', [
      'current_wp_user' => $this->wp->wpGetCurrentUser()->to_array(),
      'subscriber_count' => Subscriber::getTotalSubscribers(),
    ]);
  }
}
