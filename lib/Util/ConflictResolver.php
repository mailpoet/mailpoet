<?php
namespace MailPoet\Util;

class ConflictResolver {
  public $allowed_assets = array(
    'styles' => array(
      // WP default
      'admin-bar',
      'colors',
      'ie',
      'wp-auth-check',
      // third-party
      'query-monitor'
    ),
    'scripts' => array(
      // WP default
      'common',
      'admin-bar',
      'utils',
      'svg-painter',
      'wp-auth-check',
      // third-party
      'query-monitor'
    )
  );

  function init() {
    add_action(
      'mailpoet_conflict_resolver_router_url_query_parameters',
      array(
        $this,
        'resolveRouterUrlQueryParametersConflict'
      )
    );
    add_action(
      'mailpoet_conflict_resolver_styles',
      array(
        $this,
        'resolveStylesConflict'
      )
    );
    add_action(
      'mailpoet_conflict_resolver_scripts',
      array(
        $this,
        'resolveScriptsConflict'
      )
    );
  }

  function resolveRouterUrlQueryParametersConflict() {
    // prevents other plugins from overtaking URL query parameters 'action=' and 'endpoint='
    unset($_GET['endpoint'], $_GET['action']);
  }

  function resolveStylesConflict() {
    // unload all styles except from the list of allowed
    $dequeue_styles = function() {
      global $wp_styles;
      foreach($wp_styles->queue as $wp_style) {
        if(!in_array($wp_style, $this->allowed_assets['styles'])) {
          wp_dequeue_style($wp_style);
        }
      }
    };
    add_action('wp_print_styles', $dequeue_styles);
    add_action('admin_print_styles', $dequeue_styles);
    add_action('admin_print_footer_scripts', $dequeue_styles);
    add_action('admin_footer', $dequeue_styles);
  }

  function resolveScriptsConflict() {
    // unload all scripts except from the list of allowed
    $dequeue_scripts = function() {
      global $wp_scripts;
      foreach($wp_scripts->queue as $wp_script) {
        if(!in_array($wp_script, $this->allowed_assets['scripts'])) {
          wp_dequeue_script($wp_script);
        }
      }
    };
    add_action('wp_print_scripts', $dequeue_scripts);
    add_action('admin_print_footer_scripts', $dequeue_scripts);
  }
}