<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Listing\PageLimit;
use MailPoet\Models\Segment;
use MailPoet\Util\Installation;

class Forms {
  /** @var PageRenderer */
  private $page_renderer;

  /** @var PageLimit */
  private $listing_page_limit;

  /** @var Installation */
  private $installation;

  public function __construct(
    PageRenderer $pageRenderer,
    PageLimit $listingPageLimit,
    Installation $installation
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->listingPageLimit = $listingPageLimit;
    $this->installation = $installation;
  }

  public function render() {
    $data = [];
    $data['items_per_page'] = $this->listingPageLimit->getLimitPerPage('forms');
    $data['segments'] = Segment::findArray();
    $data['is_new_user'] = $this->installation->isNewInstallation();

    $this->pageRenderer->displayPage('forms.html', $data);
  }
}
