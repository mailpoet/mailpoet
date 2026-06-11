<?php declare(strict_types = 1);

namespace MailPoet\Test\Util;

use MailPoet\Util\Helpers;

class HelpersTest extends \MailPoetUnitTest {
  public function testItReplacesLinkTags() {
    $source = '[link]example link[/link]';
    $link = 'http://example.com';
    verify(Helpers::replaceLinkTags($source, $link))
      ->equals('<a href="' . $link . '">example link</a>');
  }

  public function testItReplacesLinkTagsAndAddsAttributes() {
    $source = '[link]example link[/link]';
    $link = 'http://example.com';
    $attributes = [
      'class' => 'test class',
      'target' => '_blank',
    ];
    verify(Helpers::replaceLinkTags($source, $link, $attributes))
      ->equals('<a class="test class" target="_blank" href="' . $link . '">example link</a>');
  }

  public function testItAcceptsCustomLinkTag() {
    $source = '[custom_link_tag]example link[/custom_link_tag]';
    $link = 'http://example.com';
    verify(Helpers::replaceLinkTags($source, $link, [], 'custom_link_tag'))
      ->equals('<a href="' . $link . '">example link</a>');
  }

  public function testItChecksForValidJsonString() {
    verify(Helpers::isJson(123))->false();
    $json = json_encode(['one' => 1, 'two' => 2]);
    verify(Helpers::isJson($json))->true();
  }

  public function testItTrimStringsRecursively() {
    verify(Helpers::recursiveTrim('  foo'))->equals('foo');
    verify(Helpers::recursiveTrim('foo  '))->equals('foo');
    verify(Helpers::recursiveTrim(123))->equals(123);
    verify(Helpers::recursiveTrim([
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
    verify(Helpers::escapeSearch('Hello'))->equals('Hello');
    verify(Helpers::escapeSearch('Hello '))->equals('Hello');
    verify(Helpers::escapeSearch(' Hello '))->equals('Hello');
    verify(Helpers::escapeSearch('%Hello '))->equals('\%Hello');
    verify(Helpers::escapeSearch('%Hello %'))->equals('\%Hello \%');
    verify(Helpers::escapeSearch('He%llo'))->equals('He\%llo');
    verify(Helpers::escapeSearch('He_llo'))->equals('He\_llo');
    verify(Helpers::escapeSearch('He\\llo'))->equals('He\\\llo');
  }

  public function testItBuildsSearchLikePattern() {
    // prefix-anchored by default (trailing %)
    verify(Helpers::buildSearchLikePattern('Hello'))->equals('Hello%');
    verify(Helpers::buildSearchLikePattern(' Hello '))->equals('Hello%');
    // '*' is the user-facing wildcard, translated to SQL '%'
    verify(Helpers::buildSearchLikePattern('*search'))->equals('%search%');
    verify(Helpers::buildSearchLikePattern('*se*arch'))->equals('%se%arch%');
    verify(Helpers::buildSearchLikePattern('se*arch'))->equals('se%arch%');
    // literal LIKE metacharacters typed by the user stay escaped
    verify(Helpers::buildSearchLikePattern('He%llo'))->equals('He\%llo%');
    verify(Helpers::buildSearchLikePattern('He_llo'))->equals('He\_llo%');
    verify(Helpers::buildSearchLikePattern('*He%llo*'))->equals('%He\%llo%%');
  }
}
