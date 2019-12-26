<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Listing\PageLimit;

class Segments {
  /** @var PageRenderer */
  private $page_renderer;

  /** @var PageLimit */
  private $listing_page_limit;

  public function __construct(PageRenderer $page_renderer, PageLimit $listing_page_limit) {
    $this->page_renderer = $page_renderer;
    $this->listing_page_limit = $listing_page_limit;
  }

  public function render() {
    $data = [];
    $data['items_per_page'] = $this->listing_page_limit->getLimitPerPage('segments');
    $this->page_renderer->displayPage('segments.html', $data);
  }
}
