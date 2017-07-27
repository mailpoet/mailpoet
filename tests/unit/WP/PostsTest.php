<?php
namespace MailPoet\Test\WP;

use Helper\WordPress as WordPressHelper;
use MailPoet\WP\Posts;

class PostsTest extends \MailPoetTest {

  function testGetTermsProxiesCallToWordPress() {
    $args = array(
      'taxonomy' => 'post_tags',
      'hide_empty' => true
    );

    WordPressHelper::interceptFunction('get_bloginfo', function($key) {
      return '4.6.0';
    });

    WordPressHelper::interceptFunction('get_terms', function($key) {
      return array(
        'call check' => 'get_terms called',
        'arguments' => func_get_args()
      );
    });

    $result = Posts::getTerms($args);
    expect($result['call check'])->equals('get_terms called');
    expect($result['arguments'][0])->equals($args);
  }

  function testGetTermsPassesTaxonomyAsFirstArgumentInOldVersions() {
    $args = array(
      'taxonomy' => 'post_tags',
      'hide_empty' => true
    );

    WordPressHelper::interceptFunction('get_bloginfo', function($key) {
      return '4.4.0';
    });

    WordPressHelper::interceptFunction('get_terms', function($key) {
      return array(
        'call check' => 'get_terms called',
        'arguments' => func_get_args()
      );
    });

    $result = Posts::getTerms($args);
    expect($result['call check'])->equals('get_terms called');
    expect($result['arguments'][0])->equals($args['taxonomy']);
    expect($result['arguments'][1])->equals(array_diff_key($args, array('taxonomy' => '')));
  }

  function _afterStep() {
    WordPressHelper::releaseAllFunctions();
  }
}
