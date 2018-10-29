<?php
namespace MailPoet\Test\Util;

use Helper\WordPressHooks as WPHooksHelper;
use MailPoet\Util\ConflictResolver;
use MailPoet\WP\Hooks;

class ConflictResolverTest extends \MailPoetTest {
  public $conflict_resolver;
  public $wp_filter;

  function __construct() {
    parent::__construct();
    $this->conflict_resolver = new ConflictResolver();
    $this->conflict_resolver->init();
    global $wp_filter;
    $this->wp_filter = $wp_filter;
  }

  function testItResolvesRouterUrlQueryParametersConflict() {
    expect(!empty($this->wp_filter['mailpoet_conflict_resolver_router_url_query_parameters']))->true();
    // it should unset action & endpoint GET variables
    $_GET['endpoint'] = $_GET['action'] = $_GET['test'] = 'test';
    do_action('mailpoet_conflict_resolver_router_url_query_parameters');
    expect(empty($_GET['endpoint']))->true();
    expect(empty($_GET['action']))->true();
    expect(empty($_GET['test']))->false();
  }

  function testItUnloadsAllStylesFromLocationsNotOnPermittedList() {
    expect(!empty($this->wp_filter['mailpoet_conflict_resolver_styles']))->true();
    // grab a random permitted style location
    $permitted_asset_location = $this->conflict_resolver->permitted_assets_locations['styles'][array_rand($this->conflict_resolver->permitted_assets_locations['styles'], 1)];
    // enqueue styles
    wp_enqueue_style('select2', '/wp-content/some/offending/plugin/select2.css');
    wp_enqueue_style('permitted_style', trim($permitted_asset_location, '^'));
    $this->conflict_resolver->resolveStylesConflict();
    do_action('wp_print_styles');
    do_action('admin_print_styles');
    do_action('admin_print_footer_scripts');
    do_action('admin_footer');
    global $wp_styles;
    // it should dequeue all styles except those found on the list of permitted locations
    expect(in_array('select2', $wp_styles->queue))->false();
    expect(in_array('permitted_style', $wp_styles->queue))->true();
  }

  function testItWhitelistsStyles() {
    wp_enqueue_style('select2', '/wp-content/some/offending/plugin/select2.css');
    Hooks::addFilter(
      'mailpoet_conflict_resolver_whitelist_style',
      function($whitelisted_styles) {
        $whitelisted_styles[] = '^/wp-content/some/offending/plugin';
        return $whitelisted_styles;
      }
    );
    $this->conflict_resolver->resolveStylesConflict();
    do_action('wp_print_styles');
    do_action('admin_print_styles');
    do_action('admin_print_footer_scripts');
    do_action('admin_footer');
    global $wp_styles;
    // it should not dequeue select2 style
    expect(in_array('select2', $wp_styles->queue))->true();
  }

  function testItUnloadsAllScriptsFromLocationsNotOnPermittedList() {
    expect(!empty($this->wp_filter['mailpoet_conflict_resolver_scripts']))->true();
    // grab a random permitted script location
    $permitted_asset_location = $this->conflict_resolver->permitted_assets_locations['scripts'][array_rand($this->conflict_resolver->permitted_assets_locations['scripts'], 1)];
    // enqueue scripts
    wp_enqueue_script('select2', '/wp-content/some/offending/plugin/select2.js');
    wp_enqueue_script('some_random_script', 'http://example.com/some_script.js', null, null, $in_footer = true); // test inside footer
    wp_enqueue_script('permitted_script', trim($permitted_asset_location, '^'));
    $this->conflict_resolver->resolveScriptsConflict();
    do_action('wp_print_scripts');
    do_action('admin_print_footer_scripts');
    global $wp_scripts;
    // it should dequeue all scripts except those found on the list of permitted locations
    expect(in_array('select2', $wp_scripts->queue))->false();
    expect(in_array('some_random_script', $wp_scripts->queue))->false();
    expect(in_array('permitted_script', $wp_scripts->queue))->true();
  }

  function testItWhitelistsScripts() {
    wp_enqueue_script('select2', '/wp-content/some/offending/plugin/select2.js');
    Hooks::addFilter(
      'mailpoet_conflict_resolver_whitelist_script',
      function($whitelisted_scripts) {
        $whitelisted_scripts[] = '^/wp-content/some/offending/plugin';
        return $whitelisted_scripts;
      }
    );
    $this->conflict_resolver->resolveStylesConflict();
    do_action('wp_print_scripts');
    do_action('admin_print_footer_scripts');
    global $wp_scripts;
    // it should not dequeue select2 script
    expect(in_array('select2', $wp_scripts->queue))->true();
  }

  function _after() {
    WPHooksHelper::releaseAllHooks();
  }
}