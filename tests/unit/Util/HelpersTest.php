<?php
namespace MailPoet\Test\Util;

use MailPoet\Util\Helpers;

class HelpersTest extends \MailPoetTest {
  function testItReplacesLinkTags() {
    $source = '[link]example link[/link]';
    $link = 'http://example.com';
    expect(Helpers::replaceLinkTags($source, $link))
      ->equals('<a href="' . $link . '">example link</a>');
  }

  function testItReplacesLinkTagsAndAddsAttributes() {
    $source = '[link]example link[/link]';
    $link = 'http://example.com';
    $attributes = array(
      'class' => 'test class',
      'target' => '_blank'
    );
    expect(Helpers::replaceLinkTags($source, $link, $attributes))
      ->equals('<a class="test class" target="_blank" href="' . $link . '">example link</a>');
  }

  function testItAcceptsCustomLinkTag() {
    $source = '[custom_link_tag]example link[/custom_link_tag]';
    $link = 'http://example.com';
    expect(Helpers::replaceLinkTags($source, $link, array(), 'custom_link_tag'))
      ->equals('<a href="' . $link . '">example link</a>');
  }
}