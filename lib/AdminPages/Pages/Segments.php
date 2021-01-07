<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\ServicesChecker;
use MailPoet\Listing\PageLimit;
use MailPoet\Models\Newsletter;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Services\Bridge;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\AutocompletePostListLoader as WPPostListLoader;
use MailPoet\WP\Functions as WPFunctions;

class Segments {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var PageLimit */
  private $listingPageLimit;

  /** @var SubscribersFeature */
  private $subscribersFeature;

  /** @var ServicesChecker */
  private $servicesChecker;

  /** @var WPFunctions */
  private $wp;

  /** @var WooCommerceHelper */
  private $woocommerceHelper;

  /** @var WPPostListLoader */
  private $wpPostListLoader;

  public function __construct(
    PageRenderer $pageRenderer,
    PageLimit $listingPageLimit,
    ServicesChecker $servicesChecker,
    WPFunctions $wp,
    WooCommerceHelper $woocommerceHelper,
    WPPostListLoader $wpPostListLoader,
    SubscribersFeature $subscribersFeature
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->listingPageLimit = $listingPageLimit;
    $this->subscribersFeature = $subscribersFeature;
    $this->servicesChecker = $servicesChecker;
    $this->wp = $wp;
    $this->woocommerceHelper = $woocommerceHelper;
    $this->wpPostListLoader = $wpPostListLoader;
  }

  public function render() {
    $data = [];
    $data['items_per_page'] = $this->listingPageLimit->getLimitPerPage('segments');

    $data['mss_active'] = Bridge::isMPSendingServiceEnabled();

    $data['subscribers_limit'] = $this->subscribersFeature->getSubscribersLimit();
    $data['subscribers_limit_reached'] = $this->subscribersFeature->check();
    $data['has_valid_api_key'] = $this->subscribersFeature->hasValidApiKey();
    $data['subscriber_count'] = $this->subscribersFeature->getSubscribersCount();
    $data['has_premium_support'] = $this->subscribersFeature->hasPremiumSupport();

    $data['wp_users_count'] = false;
    if (!$data['has_premium_support']) {
      $wpSegment = Segment::getWPSegment()->withSubscribersCount();
      $subscribersCount = $wpSegment->subscribersCount;
      $data['wp_users_count'] = $subscribersCount[Subscriber::STATUS_SUBSCRIBED]
        + $subscribersCount[Subscriber::STATUS_UNCONFIRMED]
        + $subscribersCount[Subscriber::STATUS_INACTIVE];
    }

    $data['mss_key_invalid'] = ($this->servicesChecker->isMailPoetAPIKeyValid() === false);

    $wpRoles = $this->wp->getEditableRoles();
    $data['wordpress_editable_roles_list'] = array_map(function($roleId, $role) {
      return [
        'role_id' => $roleId,
        'role_name' => $role['name'],
      ];
    }, array_keys($wpRoles), $wpRoles);

    $data['newsletters_list'] = Newsletter::select(['id', 'subject', 'sent_at'])
      ->whereNull('deleted_at')
      ->where('type', Newsletter::TYPE_STANDARD)
      ->orderByExpr('ISNULL(sent_at) DESC, sent_at DESC')->findArray();

    $data['product_categories'] = $this->wpPostListLoader->getWooCommerceCategories();

    $data['products'] = $this->wpPostListLoader->getProducts();
    $data['is_woocommerce_active'] = $this->woocommerceHelper->isWooCommerceActive();

    $this->pageRenderer->displayPage('segments.html', $data);
  }
}
