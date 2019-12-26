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
    PageRenderer $page_renderer,
    PageLimit $listing_page_limit,
    WPFunctions $wp,
    WooCommerceHelper $woocommerce_helper
  ) {
    $this->page_renderer = $page_renderer;
    $this->listing_page_limit = $listing_page_limit;
    $this->wp = $wp;
    $this->woocommerce_helper = $woocommerce_helper;
  }

  public function render() {
    $data = [];
    $data['items_per_page'] = $this->listing_page_limit->getLimitPerPage('dynamic_segments');

    $wp_roles = $this->wp->getEditableRoles();
    $data['wordpress_editable_roles_list'] = array_map(function($role_id, $role) {
      return [
        'role_id' => $role_id,
        'role_name' => $role['name'],
      ];
    }, array_keys($wp_roles), $wp_roles);

    $data['newsletters_list'] = Newsletter::select(['id', 'subject', 'sent_at'])
      ->whereNull('deleted_at')
      ->where('type', Newsletter::TYPE_STANDARD)
      ->orderByExpr('ISNULL(sent_at) DESC, sent_at DESC')->findArray();

    $data['product_categories'] = $this->wp->getCategories(['taxonomy' => 'product_cat']);

    usort($data['product_categories'], function ($a, $b) {
      return strcmp($a->cat_name, $b->cat_name);
    });

    $data['products'] = $this->getProducts();
    $data['is_woocommerce_active'] = $this->woocommerce_helper->isWooCommerceActive();

    $this->page_renderer->displayPage('dynamicSegments.html', $data);
  }

  private function getProducts() {
    $args = ['post_type' => 'product', 'orderby' => 'title', 'order' => 'ASC', 'numberposts' => -1];
    $products = $this->wp->getPosts($args);
    return array_map(function ($product) {
      return [
        'title' => $product->post_title,
        'ID' => $product->ID,
      ];
    }, $products);
  }
}
