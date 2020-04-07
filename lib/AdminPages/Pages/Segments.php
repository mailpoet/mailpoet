<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\ServicesChecker;
use MailPoet\Listing\PageLimit;
use MailPoet\Models\Subscriber;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;

class Segments {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var PageLimit */
  private $listingPageLimit;

  /** @var SubscribersFeature */
  private $subscribersFeature;

  /** @var ServicesChecker */
  private $servicesChecker;

  public function __construct(
    PageRenderer $pageRenderer,
    PageLimit $listingPageLimit,
    ServicesChecker $servicesChecker,
    SubscribersFeature $subscribersFeature
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->listingPageLimit = $listingPageLimit;
    $this->subscribersFeature = $subscribersFeature;
    $this->servicesChecker = $servicesChecker;
  }

  public function render() {
    $data = [];
    $data['items_per_page'] = $this->listingPageLimit->getLimitPerPage('segments');

    $data['subscribers_limit'] = $this->subscribersFeature->getSubscribersLimit();
    $data['subscribers_limit_reached'] = $this->subscribersFeature->check();
    $data['has_valid_api_key'] = $this->subscribersFeature->hasValidApiKey();
    $data['subscriber_count'] = Subscriber::getTotalSubscribers();

    $data['mss_key_invalid'] = ($this->servicesChecker->isMailPoetAPIKeyValid() === false);

    $this->pageRenderer->displayPage('segments.html', $data);
  }
}
