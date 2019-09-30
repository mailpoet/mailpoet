<?php

namespace MailPoet\Test\Util;

use MailPoet\Util\Helpers;

class HelpersTest extends \MailPoetUnitTest {
  function testItReplacesLinkTags() {
    $source = '[link]example link[/link]';
    $link = 'http://example.com';
    expect(Helpers::replaceLinkTags($source, $link))
      ->equals('<a href="' . $link . '">example link</a>');
  }

  function testItReplacesLinkTagsAndAddsAttributes() {
    $source = '[link]example link[/link]';
    $link = 'http://example.com';
    $attributes = [
      'class' => 'test class',
      'target' => '_blank',
    ];
    expect(Helpers::replaceLinkTags($source, $link, $attributes))
      ->equals('<a class="test class" target="_blank" href="' . $link . '">example link</a>');
  }

  function testItAcceptsCustomLinkTag() {
    $source = '[custom_link_tag]example link[/custom_link_tag]';
    $link = 'http://example.com';
    expect(Helpers::replaceLinkTags($source, $link, [], 'custom_link_tag'))
      ->equals('<a href="' . $link . '">example link</a>');
  }

  function testItChecksForValidJsonString() {
    expect(Helpers::isJson(123))->false();
    $json = json_encode(['one' => 1, 'two' => 2]);
    expect(Helpers::isJson($json))->true();
  }

  function testItTrimStringsRecursively() {
    expect(Helpers::recursiveTrim('  foo'))->equals('foo');
    expect(Helpers::recursiveTrim('foo  '))->equals('foo');
    expect(Helpers::recursiveTrim(123))->equals(123);
    expect(Helpers::recursiveTrim([
      'name' => '   some text here   ',
      'list' => [
        'string 1',
        'string 2   ',
        '  string 3   ',
      ],
      'number' => 523,
    ]))->equals([
      'name' => 'some text here',
      'list' => [
        'string 1',
        'string 2',
        'string 3',
      ],
      'number' => 523,
    ]);
  }
}