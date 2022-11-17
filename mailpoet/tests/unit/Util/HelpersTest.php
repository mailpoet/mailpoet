<?php declare(strict_types = 1);

namespace MailPoet\Test\Util;

use MailPoet\Util\Helpers;

class HelpersTest extends \MailPoetUnitTest {
  public function testItReplacesLinkTags() {
    $source = '[link]example link[/link]';
    $link = 'http://example.com';
    expect(Helpers::replaceLinkTags($source, $link))
      ->equals('<a href="' . $link . '">example link</a>');
  }

  public function testItReplacesLinkTagsAndAddsAttributes() {
    $source = '[link]example link[/link]';
    $link = 'http://example.com';
    $attributes = [
      'class' => 'test class',
      'target' => '_blank',
    ];
    expect(Helpers::replaceLinkTags($source, $link, $attributes))
      ->equals('<a class="test class" target="_blank" href="' . $link . '">example link</a>');
  }

  public function testItAcceptsCustomLinkTag() {
    $source = '[custom_link_tag]example link[/custom_link_tag]';
    $link = 'http://example.com';
    expect(Helpers::replaceLinkTags($source, $link, [], 'custom_link_tag'))
      ->equals('<a href="' . $link . '">example link</a>');
  }

  public function testItChecksForValidJsonString() {
    expect(Helpers::isJson(123))->false();
    $json = json_encode(['one' => 1, 'two' => 2]);
    expect(Helpers::isJson($json))->true();
  }

  public function testItTrimStringsRecursively() {
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

  public function testSanitizeSearch() {
    expect(Helpers::escapeSearch('Hello'))->equals('Hello');
    expect(Helpers::escapeSearch('Hello '))->equals('Hello');
    expect(Helpers::escapeSearch(' Hello '))->equals('Hello');
    expect(Helpers::escapeSearch('%Hello '))->equals('\%Hello');
    expect(Helpers::escapeSearch('%Hello %'))->equals('\%Hello \%');
    expect(Helpers::escapeSearch('He%llo'))->equals('He\%llo');
    expect(Helpers::escapeSearch('He_llo'))->equals('He\_llo');
    expect(Helpers::escapeSearch('He\\llo'))->equals('He\\\llo');
  }
}
