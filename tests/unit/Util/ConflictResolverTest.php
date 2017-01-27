<?php
use MailPoet\Util\ConflictResolver;

class ConflictResolverTest extends MailPoetTest {
  public $conflict_resolver;
  public $wp_filter;

  function __construct() {
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
    $permitted_asset_location = ConflictResolver::$permitted_assets_locations['styles'][array_rand(ConflictResolver::$permitted_assets_locations['styles'], 1)];
    // enqueue styles
    wp_enqueue_style('select2', '/wp-content/some/offending/plugin/select2.css');
    wp_enqueue_style('permitted_style', trim($permitted_asset_location, '^'));
    $this->conflict_resolver->resolveStylesConflict();
    do_action('wp_print_styles');
    do_action('admin_print_styles');
    do_action('admin_print_footer_scripts');
    do_action('admin_footer');
    global $wp_styles;
    $queued_styles = array_flip($wp_styles->queue);
    // it should dequeue all styles except those found on the list of permitted locations
    expect(empty($queued_styles['select2']))->true();
    expect(empty($queued_styles['permitted_style']))->false();
  }

  function testItUnloadsAllScriptsFromLocationsNotOnPermittedList() {
    expect(!empty($this->wp_filter['mailpoet_conflict_resolver_scripts']))->true();
    // grab a random permitted script location
    $permitted_asset_location = ConflictResolver::$permitted_assets_locations['scripts'][array_rand(ConflictResolver::$permitted_assets_locations['scripts'], 1)];
    // enqueue scripts
    wp_enqueue_script('select2', '/wp-content/some/offending/plugin/select2.js');
    wp_enqueue_script('some_random_script', 'http://example.com/some_script.js', null, null, $in_footer = true); // test inside footer
    wp_enqueue_script('permitted_script', trim($permitted_asset_location, '^'));
    $this->conflict_resolver->resolveScriptsConflict();
    do_action('wp_print_scripts');
    do_action('admin_print_footer_scripts');
    global $wp_scripts;
    $queued_scripts = array_flip($wp_scripts->queue);
    // it should dequeue all scripts except those found on the list of permitted locations
    expect(empty($queued_scripts['select2']))->true();
    expect(empty($queued_scripts['some_random_script']))->true();
    expect(empty($queued_scripts['permitted_script']))->false();
  }
}