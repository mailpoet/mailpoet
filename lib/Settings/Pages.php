<?php
namespace MailPoet\Settings;

class Pages {

  static function getAll() {
    $mailpoet_pages = get_posts(array(
      'post_type' => 'mailpoet_page'
    ));

    $pages = array_merge($mailpoet_pages, get_pages());

    foreach($pages as $key => $page) {
      $page = (array)$page;
      $page['preview_url'] = get_permalink($page['ID']);
      $page['edit_url'] = get_edit_post_link($page['ID']);

      $pages[$key] = $page;
    }

    return $pages;
  }
}