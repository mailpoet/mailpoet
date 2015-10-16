<?php
use \MailPoet\Settings\Pages;

class PagesCest {
  function itReturnsAListOfPages() {
    $pages = Pages::getAll();
    expect($pages)->notEmpty();

    foreach($pages as $page) {
      expect($page['id'])->greaterThan(0);
      expect($page['title'])->notEmpty();
      expect($page['preview_url'])->notEmpty();
    }
  }
}
