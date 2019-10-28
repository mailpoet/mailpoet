<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Models\Subscriber;

class Premium {
  /** @var PageRenderer */
  private $page_renderer;

  function __construct(PageRenderer $page_renderer) {
    $this->page_renderer = $page_renderer;
  }

  function render() {
    $data = [
      'subscriber_count' => Subscriber::getTotalSubscribers(),
    ];
    $this->page_renderer->displayPage('premium.html', $data);
  }
}
