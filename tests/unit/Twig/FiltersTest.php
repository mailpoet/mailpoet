<?php

use MailPoet\Twig\Filters;

class FiltersTest extends MailPoetTest {
  function _before() {
    $this->filters = new Filters();
  }

  function testItReplacesLink() {
    $source = '[link]example link[/link]';
    $link = 'http://example.com';
    expect($this->filters->replaceLink($source, $link))
      ->equals('<a href="' . $link . '">example link</a>');
  }

  function testItReplacesLinkAndAddsAttributes() {
    $source = '[link]example link[/link]';
    $link = 'http://example.com';
    $attributes = array(
      'class' => 'test class',
      'target' => '_blank'
    );
    expect($this->filters->replaceLink($source, $link, $attributes))
      ->equals('<a class="test class" target="_blank" href="' . $link . '">example link</a>');
  }
}