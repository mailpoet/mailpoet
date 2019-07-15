<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\Menu;
use MailPoet\Models\Subscriber;

if (!defined('ABSPATH')) exit;

class Premium {
  /** @var PageRenderer */
  private $page_renderer;

  function __construct(PageRenderer $page_renderer) {
    $this->page_renderer = $page_renderer;
  }

  function render() {
    $data = [
      'subscriber_count' => Subscriber::getTotalSubscribers(),
      'sub_menu' => Menu::MAIN_PAGE_SLUG,
      'display_discount' => time() <= strtotime('2018-11-30 23:59:59'),
    ];

    $this->page_renderer->displayPage('premium.html', $data);
  }
}
