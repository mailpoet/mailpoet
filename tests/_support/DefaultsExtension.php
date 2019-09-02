<?php

use Codeception\Event\SuiteEvent;
use Codeception\Events;
use Codeception\Extension;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\UserFlags;

class DefaultsExtension extends Extension {

  public static $events = [
    Events::SUITE_BEFORE => 'setupDefaults',
  ];

  public function setupDefaults(SuiteEvent $e) {
    $this->setupWordPress();
    $this->setupWooCommerce();

    $settings = new Settings();
    $settings->withDefaultSettings();

    $user_flags = new UserFlags(1);
    $user_flags->withDefaultFlags();
  }

  private function setupWordPress() {
    update_option('siteurl', 'http://test.local', 'yes');
    update_option('home', 'http://test.local', 'yes');
    update_option('blogname', 'MP Dev', 'yes');
    update_option('admin_email', 'test@example.com', 'yes');
    update_option('gmt_offset', '0', 'yes');
    update_option('users_can_register', '1', 'yes');
    update_site_option('registration', 'user');
    update_option('permalink_structure', '/%year%/%monthnum%/%day%/%postname%/', 'yes');
    update_option('template', 'twentysixteen', 'yes');
    update_option('stylesheet', 'twentysixteen', 'yes');

    // posts & pages
    $this->createPost('post', 'hello-world', 'Hello world!', 'Hello from WordPress.');
    $this->createPost('mailpoet_page', '', 'MailPoet Page', '[mailpoet_page]');

    // get rid of 'blog/' prefix that is added automatically to rewrite rules on multisite by default
    // (init() loads 'permalink_structure' option from DB, flush_rules() regenerates 'rewrite_rules')
    global $wp_rewrite;
    $wp_rewrite->init();
    $wp_rewrite->flush_rules();
  }

  private function setupWooCommerce() {
    // address
    update_option('woocommerce_store_address', 'Address', 'yes');
    update_option('woocommerce_store_address_2', '', 'yes');
    update_option('woocommerce_store_city', 'Paris', 'yes');
    update_option('woocommerce_default_country', 'FR:*', 'yes');
    update_option('woocommerce_store_postcode', '75000', 'yes');

    // currency
    update_option('woocommerce_currency', 'EUR', 'yes');
    update_option('woocommerce_currency_pos', 'right', 'yes');
    update_option('woocommerce_price_thousand_sep', ' ', 'yes');
    update_option('woocommerce_price_decimal_sep', ',', 'yes');

    // pages
    $shop_page_id = $this->createPage('shop', 'Shop', '');
    $cart_page_id = $this->createPage('cart', 'Cart', '[woocommerce_cart]');
    $checkout_page_id = $this->createPage('checkout', 'Checkout', '[woocommerce_checkout]');
    $my_account_page_id = $this->createPage('my-account', 'My account', '[woocommerce_my_account]');

    update_option('woocommerce_shop_page_id', $shop_page_id, 'yes');
    update_option('woocommerce_cart_page_id', $cart_page_id, 'yes');
    update_option('woocommerce_checkout_page_id', $checkout_page_id, 'yes');
    update_option('woocommerce_myaccount_page_id', $my_account_page_id, 'yes');

    // other
    update_option('woocommerce_bacs_settings', ['enabled' => 'yes'], 'yes');
    update_option('woocommerce_cod_settings', ['enabled' => 'yes'], 'yes');
    update_option('woocommerce_enable_signup_and_login_from_checkout', 'yes', 'no');
  }

  private function createPage($name, $tile, $content) {
    return $this->createPost('page', $name, $tile, $content);
  }

  private function createPost($type, $name, $tile, $content) {
    return wp_insert_post([
      'post_author' => 1,
      'post_type' => $type,
      'post_name' => $name,
      'post_title' => $tile,
      'post_content' => $content,
      'post_status' => 'publish',
    ]);
  }
}
