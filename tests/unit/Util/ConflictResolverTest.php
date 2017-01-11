<?php
use MailPoet\Util\ConflictResolver;

class ConflictResolverTest extends MailPoetTest {
  public $conflict_resolver;
  public $wp_filter;

  function __construct() {
    $this->conflict_resolver = new ConflictResolver();
    $this->conflict_resolver = $this->conflict_resolver->init();
    global $wp_filter;
    $this->wp_filter = $wp_filter;
  }

  function testItResolvesRouterUrlQueryParametersConflict() {
    expect(!empty($this->wp_filter['mailpoet_conflict_url_query_parameters']))->true();
    // it should unset action & endpoint GET variables
    $_GET['endpoint'] = $_GET['action'] = $_GET['test'] = 'test';
    do_action('mailpoet_conflict_url_query_parameters');
    expect(empty($_GET['endpoint']))->true();
    expect(empty($_GET['action']))->true();
    expect(empty($_GET['test']))->false();
  }
}