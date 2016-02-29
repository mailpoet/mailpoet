<?php
namespace MailPoet\Settings;

class Pages {
  function __construct() {
  }

  function init() {
    register_post_type('mailpoet_page', array(
      'labels' => array(
        'name' => __('MailPoet Page'),
        'singular_name' => __('MailPoet Page')
      ),
      'public' => true,
      'has_archive' => false,
      'show_ui' => WP_DEBUG,
      'show_in_menu' => WP_DEBUG,
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
      'post_title' => __('MailPoet Page'),
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
      'preview_url' => get_permalink($page->ID),
      'edit_url' => get_edit_post_link($page->ID)
    );
  }
}