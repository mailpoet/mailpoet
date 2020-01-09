<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Listing\PageLimit;
use MailPoet\Models\Newsletter;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;

class DynamicSegments {
  /** @var PageRenderer */
  private $page_renderer;

  /** @var PageLimit */
  private $listing_page_limit;

  /** @var WPFunctions */
  private $wp;

  /** @var WooCommerceHelper */
  private $woocommerce_helper;

  public function __construct(
    PageRenderer $pageRenderer,
    PageLimit $listingPageLimit,
    WPFunctions $wp,
    WooCommerceHelper $woocommerceHelper
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->listingPageLimit = $listingPageLimit;
    $this->wp = $wp;
    $this->woocommerceHelper = $woocommerceHelper;
  }

  public function render() {
    $data = [];
    $data['items_per_page'] = $this->listingPageLimit->getLimitPerPage('dynamic_segments');

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

    $data['product_categories'] = $this->wp->getCategories(['taxonomy' => 'product_cat']);

    usort($data['product_categories'], function ($a, $b) {
      return strcmp($a->catName, $b->catName);
    });

    $data['products'] = $this->getProducts();
    $data['is_woocommerce_active'] = $this->woocommerceHelper->isWooCommerceActive();

    $this->pageRenderer->displayPage('dynamicSegments.html', $data);
  }

  private function getProducts() {
    $args = ['post_type' => 'product', 'orderby' => 'title', 'order' => 'ASC', 'numberposts' => -1];
    $products = $this->wp->getPosts($args);
    return array_map(function ($product) {
      return [
        'title' => $product->postTitle,
        'ID' => $product->ID,
      ];
    }, $products);
  }
}
