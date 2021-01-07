<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WP\Functions as WPFunctions;

class Premium {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var WPFunctions */
  private $wp;

  /** @var SubscribersFeature */
  private $subscribersFeature;

  public function __construct(PageRenderer $pageRenderer, WPFunctions $wp, SubscribersFeature $subscribersFeature) {
    $this->pageRenderer = $pageRenderer;
    $this->wp = $wp;
    $this->subscribersFeature = $subscribersFeature;
  }

  public function render() {
    $data = [
      'current_wp_user' => $this->wp->wpGetCurrentUser()->to_array(),
      'subscriber_count' => $this->subscribersFeature->getSubscribersCount(),
    ];
    $this->pageRenderer->displayPage('premium.html', $data);
  }
}
