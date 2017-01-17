<?php
use MailPoet\Util\ConflictResolver;

class ConflictResolverTest extends MailPoetTest {
  public $conflict_resolver;
  public $wp_filter;

  function __construct() {
    $this->conflict_resolver = new ConflictResolver();
    $this->conflict_resolver->allowed_assets = array(
      'scripts' => array('abc', 'xyz'),
      'styles' => array('abc', 'xyz'),
    );
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
    // grab a random permitted style
    $permitted_style_name = $this->conflict_resolver->allowed_assets['styles'][array_rand($this->conflict_resolver->allowed_assets['styles'], 1)];
    // enqueue styles
    wp_enqueue_style('select2', 'select2');
    wp_enqueue_style($permitted_style_name, 'permitted_style.css');
    $this->conflict_resolver->resolveStylesConflict();
    do_action('wp_print_styles');
    do_action('admin_print_styles');
    do_action('admin_print_footer_scripts');
    do_action('admin_footer');
    global $wp_styles;
    $queued_styles = array_flip($wp_styles->queue);
    // it should dequeue all styles except those found on the permitted list
    expect(empty($queued_styles['select2']))->true();
    expect(empty($queued_styles[$permitted_style_name]))->false();
  }

  function testItUnloadsAllScriptsFromLocationsNotOnPermittedList() {
    expect(!empty($this->wp_filter['mailpoet_conflict_resolver_scripts']))->true();
    // grab a random permitted script
    $permitted_script_name = $this->conflict_resolver->allowed_assets['scripts'][array_rand($this->conflict_resolver->allowed_assets['scripts'], 1)];
    // enqueue scripts
    wp_enqueue_script('select2', 'select2');
    wp_enqueue_script('random_script_in_footer', 'http://example.com/random-script.js', null, null, $in_footer = true);
    wp_enqueue_script($permitted_script_name, 'permitted_script.js');
    $this->conflict_resolver->resolveScriptsConflict();
    do_action('wp_print_scripts');
    do_action('admin_print_footer_scripts');
    global $wp_scripts;
    $queued_scripts = array_flip($wp_scripts->queue);
    // it should dequeue all scripts except those found on the permitted list
    expect(empty($queued_scripts['select2']))->true();
    expect(empty($queued_scripts['random_script_in_footer']))->true();
    expect(empty($queued_scripts[$permitted_script_name]))->false();
  }
}