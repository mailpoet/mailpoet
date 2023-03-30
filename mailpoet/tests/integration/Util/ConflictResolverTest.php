<?php declare(strict_types = 1);

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
    global $wp_filter; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $this->wpFilter = $wp_filter; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
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
    $permittedAssetLocation = $this->conflictResolver->permittedAssetsLocations['styles'][array_rand($this->conflictResolver->permittedAssetsLocations['styles'], 1)];
    // enqueue styles
    wp_enqueue_style('select2', '/wp-content/some/offending/plugin/select2.css');
    wp_enqueue_style('permitted_style', trim($permittedAssetLocation, '^'));
    $this->conflictResolver->resolveStylesConflict();
    do_action('wp_print_styles');
    do_action('admin_print_styles');
    do_action('admin_print_footer_scripts');
    do_action('admin_footer');
    global $wp_styles; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    // it should dequeue all styles except those found on the list of permitted locations
    expect(in_array('select2', $wp_styles->queue))->false(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    expect(in_array('permitted_style', $wp_styles->queue))->true(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
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
    global $wp_styles; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    // it should not dequeue select2 style
    expect(in_array('select2', $wp_styles->queue))->true(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function testItUnloadsAllScriptsFromLocationsNotOnPermittedList() {
    expect(!empty($this->wpFilter['mailpoet_conflict_resolver_scripts']))->true();
    // grab a random permitted script location
    $permittedAssetLocation = $this->conflictResolver->permittedAssetsLocations['scripts'][array_rand($this->conflictResolver->permittedAssetsLocations['scripts'], 1)];
    // enqueue scripts
    wp_enqueue_script('select2', '/wp-content/some/offending/plugin/select2.js');
    wp_enqueue_script('some_random_script', 'http://example.com/some_script.js', [], null, $inFooter = true); // test inside footer
    wp_enqueue_script('permitted_script', trim($permittedAssetLocation, '^'));
    $this->conflictResolver->resolveScriptsConflict();
    do_action('wp_print_scripts');
    do_action('admin_print_footer_scripts');
    global $wp_scripts; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    // it should dequeue all scripts except those found on the list of permitted locations
    expect(in_array('select2', $wp_scripts->queue))->false(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    expect(in_array('some_random_script', $wp_scripts->queue))->false(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    expect(in_array('permitted_script', $wp_scripts->queue))->true(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
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
    global $wp_scripts; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    // it should not dequeue select2 script
    expect(in_array('select2', $wp_scripts->queue))->true(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function _after() {
    parent::_after();
  }
}
