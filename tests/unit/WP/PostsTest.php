<?php
namespace MailPoet\Test\WP;

use MailPoet\WP\Posts;
use Codeception\Util\Stub;
use MailPoet\WP\Functions as WPFunctions;

class PostsTest extends \MailPoetUnitTest {

  function testGetTermsProxiesCallToWordPress() {
    $args = [
      'taxonomy' => 'post_tags',
      'hide_empty' => true,
    ];

    WPFunctions::set(Stub::make(new WPFunctions, [
      'getBloginfo' => function($key) {
        return '4.6.0';
      },
      'getTerms' => function($key) {
        return [
          'call check' => 'get_terms called',
          'arguments' => func_get_args(),
        ];
      },
    ]));

    $result = Posts::getTerms($args);
    expect($result['call check'])->equals('get_terms called');
    expect($result['arguments'][0])->equals($args);
  }

  function testGetTermsPassesTaxonomyAsFirstArgumentInOldVersions() {
    $args = [
      'taxonomy' => 'post_tags',
      'hide_empty' => true,
    ];

    WPFunctions::set(Stub::make(new WPFunctions, [
      'getBloginfo' => function($key) {
        return '4.4.0';
      },
      'getTerms' => function($key) {
        return [
          'call check' => 'get_terms called',
          'arguments' => func_get_args(),
        ];
      },
    ]));

    $result = Posts::getTerms($args);
    expect($result['call check'])->equals('get_terms called');
    expect($result['arguments'][0])->equals($args['taxonomy']);
    expect($result['arguments'][1])->equals(array_diff_key($args, ['taxonomy' => '']));
  }

  function _after() {
    WPFunctions::set(new WPFunctions);
  }
}
