<?php

namespace MailPoet\Test\Util;

use MailPoet\Util\ConflictResolver;
use MailPoet\WP\Functions as WPFunctions;

class ConflictResolverTest extends \MailPoetTest {
  public $conflictResolver;
  public $wpFilter;

  public function __construct() {
    parent::__construct();
    $this->conflictResolver = new ConflictResolver();
    $this->conflictResolver->init();
    global $wpFilter;
    $this->wpFilter = $wpFilter;
  }

  public function testItResolvesRouterUrlQueryParametersConflict() {
    expect(!empty($this->wpFilter['mailpoet_conflict_resolver_router_url_query_parameters']))->true();
    // it should unset action & endpoint GET variables
    $_GET['endpoint'] = $_GET['action'] = $_GET['test'] = 'test';
    do_action('mailpoet_conflict_resolver_router_url_query_parameters');
    expect(empty($_GET['endpoint']))->true();
    expect(empty($_GET['action']))->true();
    expect(empty($_GET['test']))->false();
  }

  public function testItUnloadsAllStylesFromLocationsNotOnPermittedList() {
    expect(!empty($this->wpFilter['mailpoet_conflict_resolver_styles']))->true();
    // grab a random permitted style location
    $permittedAssetLocation = $this->conflictResolver->permitted_assets_locations['styles'][array_rand($this->conflictResolver->permitted_assets_locations['styles'], 1)];
    // enqueue styles
    wp_enqueue_style('select2', '/wp-content/some/offending/plugin/select2.css');
    wp_enqueue_style('permitted_style', trim($permittedAssetLocation, '^'));
    $this->conflictResolver->resolveStylesConflict();
    do_action('wp_print_styles');
    do_action('admin_print_styles');
    do_action('admin_print_footer_scripts');
    do_action('admin_footer');
    global $wpStyles;
    // it should dequeue all styles except those found on the list of permitted locations
    expect(in_array('select2', $wpStyles->queue))->false();
    expect(in_array('permitted_style', $wpStyles->queue))->true();
  }

  public function testItWhitelistsStyles() {
    wp_enqueue_style('select2', '/wp-content/some/offending/plugin/select2.css');
    $wp = new WPFunctions;
    $wp->addFilter(
      'mailpoet_conflict_resolver_whitelist_style',
      function($whitelistedStyles) {
        $whitelistedStyles[] = '^/wp-content/some/offending/plugin';
        return $whitelistedStyles;
      }
    );
    $this->conflictResolver->resolveStylesConflict();
    do_action('wp_print_styles');
    do_action('admin_print_styles');
    do_action('admin_print_footer_scripts');
    do_action('admin_footer');
    global $wpStyles;
    // it should not dequeue select2 style
    expect(in_array('select2', $wpStyles->queue))->true();
  }

  public function testItUnloadsAllScriptsFromLocationsNotOnPermittedList() {
    expect(!empty($this->wpFilter['mailpoet_conflict_resolver_scripts']))->true();
    // grab a random permitted script location
    $permittedAssetLocation = $this->conflictResolver->permitted_assets_locations['scripts'][array_rand($this->conflictResolver->permitted_assets_locations['scripts'], 1)];
    // enqueue scripts
    wp_enqueue_script('select2', '/wp-content/some/offending/plugin/select2.js');
    wp_enqueue_script('some_random_script', 'http://example.com/some_script.js', [], null, $inFooter = true); // test inside footer
    wp_enqueue_script('permitted_script', trim($permittedAssetLocation, '^'));
    $this->conflictResolver->resolveScriptsConflict();
    do_action('wp_print_scripts');
    do_action('admin_print_footer_scripts');
    global $wpScripts;
    // it should dequeue all scripts except those found on the list of permitted locations
    expect(in_array('select2', $wpScripts->queue))->false();
    expect(in_array('some_random_script', $wpScripts->queue))->false();
    expect(in_array('permitted_script', $wpScripts->queue))->true();
  }

  public function testItWhitelistsScripts() {
    wp_enqueue_script('select2', '/wp-content/some/offending/plugin/select2.js');
    $wp = new WPFunctions;
    $wp->addFilter(
      'mailpoet_conflict_resolver_whitelist_script',
      function($whitelistedScripts) {
        $whitelistedScripts[] = '^/wp-content/some/offending/plugin';
        return $whitelistedScripts;
      }
    );
    $this->conflictResolver->resolveStylesConflict();
    do_action('wp_print_scripts');
    do_action('admin_print_footer_scripts');
    global $wpScripts;
    // it should not dequeue select2 script
    expect(in_array('select2', $wpScripts->queue))->true();
  }

  public function _after() {
  }
}