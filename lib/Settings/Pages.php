<?php
namespace MailPoet\Settings;
use MailPoet\Subscription;
use MailPoet\WP\Functions as WPFunctions;

class Pages {
  function __construct() {
  }

  function init() {
    WPFunctions::get()->registerPostType('mailpoet_page', [
      'labels' => [
        'name' => WPFunctions::get()->__('MailPoet Page', 'mailpoet'),
        'singular_name' => WPFunctions::get()->__('MailPoet Page', 'mailpoet'),
      ],
      'public' => true,
      'has_archive' => false,
      'show_ui' => false,
      'show_in_menu' => false,
      'rewrite' => false,
      'show_in_nav_menus' => false,
      'can_export' => false,
      'publicly_queryable' => true,
      'exclude_from_search' => true,
    ]);
  }

  static function createMailPoetPage() {
    WPFunctions::get()->removeAllActions('pre_post_update');
    WPFunctions::get()->removeAllActions('save_post');
    WPFunctions::get()->removeAllActions('wp_insert_post');

    $id = WPFunctions::get()->wpInsertPost([
      'post_status' => 'publish',
      'post_type' => 'mailpoet_page',
      'post_author' => 1,
      'post_content' => '[mailpoet_page]',
      'post_title' => WPFunctions::get()->__('MailPoet Page', 'mailpoet'),
      'post_name' => 'subscriptions',
    ]);

    return ((int)$id > 0) ? (int)$id : false;
  }

  static function getMailPoetPages() {
    return WPFunctions::get()->getPosts([
      'post_type' => 'mailpoet_page',
    ]);
  }

  /**
   * @param int $id
   *
   * @return bool
   */
  static function isMailpoetPage($id) {
    $mailpoetPages = static::getMailPoetPages();
    foreach ($mailpoetPages as $mailpoetPage) {
      if ($mailpoetPage->ID === $id) {
        return true;
      }
    }
    return false;
  }

  static function getAll() {
    $all_pages = array_merge(
      static::getMailPoetPages(),
      WPFunctions::get()->getPages()
    );

    $pages = [];
    foreach ($all_pages as $page) {
      $pages[] = static::getPageData($page);
    }

    return $pages;
  }

  static function getPageData($page) {
    return [
      'id' => $page->ID,
      'title' => $page->post_title,
      'url' => [
        'unsubscribe' => Subscription\Url::getSubscriptionUrl($page, 'unsubscribe'),
        'manage' => Subscription\Url::getSubscriptionUrl($page, 'manage'),
        'confirm' => Subscription\Url::getSubscriptionUrl($page, 'confirm'),
      ],
    ];
  }
}
