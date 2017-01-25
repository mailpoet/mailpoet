<?php
namespace MailPoet\Settings;
use MailPoet\Subscription;

class Pages {
  function __construct() {
  }

  function init() {
    register_post_type('mailpoet_page', array(
      'labels' => array(
        'name' => __('MailPoet Page', 'mailpoet'),
        'singular_name' => __('MailPoet Page', 'mailpoet')
      ),
      'public' => true,
      'has_archive' => false,
      'show_ui' => false,
      'show_in_menu' => false,
      'rewrite' => false,
      'show_in_nav_menus' => false,
      'can_export' => false,
      'publicly_queryable' => true,
      'exclude_from_search' => true
    ));
  }

  static function createMailPoetPage() {
    remove_all_actions('pre_post_update');
    remove_all_actions('save_post');
    remove_all_actions('wp_insert_post');

    $id = wp_insert_post(array(
      'post_status' => 'publish',
      'post_type' => 'mailpoet_page',
      'post_author' => 1,
      'post_content' => '[mailpoet_page]',
      'post_title' => __('MailPoet Page', 'mailpoet'),
      'post_name' => 'subscriptions'
    ));
    flush_rewrite_rules();

    return ((int)$id > 0) ? (int)$id : false;
  }

  static function getMailPoetPages() {
    return get_posts(array(
      'post_type' => 'mailpoet_page'
    ));
  }

  static function getAll() {
    $all_pages = array_merge(
      static::getMailPoetPages(),
      get_pages()
    );

    $pages = array();
    foreach($all_pages as $page) {
      $pages[] = static::getPageData($page);
    }

    return $pages;
  }

  static function getPageData($page) {
    return array(
      'id' => $page->ID,
      'title' => $page->post_title,
      'url' => array(
        'unsubscribe' => Subscription\Url::getSubscriptionUrl($page, 'unsubscribe'),
        'manage' => Subscription\Url::getSubscriptionUrl($page, 'manage'),
        'confirm' => Subscription\Url::getSubscriptionUrl($page, 'confirm')
      )
    );
  }
}
