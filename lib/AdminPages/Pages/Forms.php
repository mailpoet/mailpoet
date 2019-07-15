<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Listing\PageLimit;
use MailPoet\Models\Segment;
use MailPoet\Util\Installation;

if (!defined('ABSPATH')) exit;

class Forms {
  /** @var PageRenderer */
  private $page_renderer;

  /** @var PageLimit */
  private $listing_page_limit;

  /** @var Installation */
  private $installation;

  function __construct(
    PageRenderer $page_renderer,
    PageLimit $listing_page_limit,
    Installation $installation
  ) {
    $this->page_renderer = $page_renderer;
    $this->listing_page_limit = $listing_page_limit;
    $this->installation = $installation;
  }

  function render() {
    $data = [];
    $data['items_per_page'] = $this->listing_page_limit->getLimitPerPage('forms');
    $data['segments'] = Segment::findArray();
    $data['is_new_user'] = $this->installation->isNewInstallation();

    $this->page_renderer->displayPage('forms.html', $data);
  }
}
