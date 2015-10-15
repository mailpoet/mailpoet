<?php
namespace MailPoet\Settings;

class Pages {

  static function getAll() {
    $mailpoet_pages = get_posts(array(
      'post_type' => 'mailpoet_page'
    ));

    $pages = array();
    foreach(array_merge($mailpoet_pages, get_pages()) as $page) {
      $pages[] = array(
        'id' => $page->ID,
        'title' => $page->post_title,
        'preview_url' => get_permalink($page->ID),
        'edit_url' => get_edit_post_link($page->ID)
      );
    }

    return $pages;
  }
}