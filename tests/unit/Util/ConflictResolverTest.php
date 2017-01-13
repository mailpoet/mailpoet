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

  function testItUnloadsConflictingStyles() {
    expect(!empty($this->wp_filter['mailpoet_conflict_resolver_styles']))->true();
    wp_enqueue_style('select2', 'select2.css');
    wp_enqueue_style('select-2', 'select-2.css');
    wp_enqueue_style('test', 'test.css');
    $this->conflict_resolver->resolveStylesConflict();
    do_action('wp_print_styles');
    do_action('admin_print_styles');
    do_action('admin_print_footer_scripts');
    do_action('admin_footer');
    global $wp_styles;
    $queued_styles = array_flip($wp_styles->queue);
    // it should unset all select* styles
    expect(empty($queued_styles['select2']))->true();
    expect(empty($queued_styles['select-2']))->true();
    expect(empty($queued_styles['test']))->false();
  }

  function testItUnloadsConflictingScripts() {
    expect(!empty($this->wp_filter['mailpoet_conflict_resolver_scripts']))->true();
    wp_enqueue_script('select2', 'select2.js');
    wp_enqueue_script('select-2', 'select-2.js', null, null, $in_footer = true);
    wp_enqueue_script('test', 'test.js');
    $this->conflict_resolver->resolveScriptsConflict();
    do_action('wp_print_scripts');
    do_action('admin_print_footer_scripts');
    global $wp_scripts;
    $queued_scripts = array_flip($wp_scripts->queue);
    // it should unset all select* scripts
    expect(empty($queued_scripts['select2']))->true();
    expect(empty($queued_scripts['select-2']))->true();
    expect(empty($queued_scripts['test']))->false();
  }
}