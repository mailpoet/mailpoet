<?php
namespace MailPoet\Util;

class ConflictResolver {
  public $conflicting_assets = array(
    'select2',
    'select-2',
  );

  function init() {
    add_action('mailpoet_conflict_resolver_router_url_query_parameters', array($this, 'resolveRouterUrlQueryParametersConflict'));
    add_action('mailpoet_conflict_resolver_styles', array($this, 'resolveStylesConflict'));
    add_action('mailpoet_conflict_resolver_scripts', array($this, 'resolveScriptsConflict'));
  }

  function resolveRouterUrlQueryParametersConflict() {
    // prevents other plugins from overtaking URL query parameters 'action=' and 'endpoint='
    unset($_GET['endpoint'], $_GET['action']);
  }

  function resolveStylesConflict() {
    // unload styles that interfere with plugin pages
    $dequeue_styles = function() {
      global $wp_styles;
      foreach($wp_styles->registered as $name => $details) {
        if(preg_match('/' . implode('|', $this->conflicting_assets) . '/i', $details->src)) {
          wp_dequeue_style($name);
        }
      }
    };
    add_action('wp_print_styles', $dequeue_styles);
    add_action('admin_print_styles', $dequeue_styles);
    add_action('admin_print_footer_scripts', $dequeue_styles);
    add_action('admin_footer', $dequeue_styles);
  }

  function resolveScriptsConflict() {
    // unload scripts that interfere with plugin pages
    $dequeue_scripts = function() {
      global $wp_scripts;
      foreach($wp_scripts->registered as $name => $details) {
        if(preg_match('/' . implode('|', $this->conflicting_assets) . '/i', $details->src)) {
          wp_dequeue_script($name);
        }
      }
    };
    add_action('wp_print_scripts', $dequeue_scripts);
    add_action('admin_print_footer_scripts', $dequeue_scripts);
  }
}