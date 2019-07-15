<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;

if (!defined('ABSPATH')) exit;

class SubscribersLimitExceeded {
  /** @var PageRenderer */
  private $page_renderer;

  function __construct(PageRenderer $page_renderer) {
    $this->page_renderer = $page_renderer;
  }

  function render() {
    $this->page_renderer->displayPage('limit.html', [
      'limit' => SubscribersFeature::SUBSCRIBERS_LIMIT,
    ]);
  }
}
