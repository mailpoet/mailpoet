<?php
namespace MailPoet\Util;

class ConflictResolver {
  public $permitted_assets_locations = array(
    'styles' => array(
      // WP default
      '^/wp-admin',
      '^/wp-includes',
      // third-party
      'query-monitor',
      'wpt-tx-updater-network'
    ),
    'scripts' => array(
      // WP default
      '^/wp-admin',
      '^/wp-includes',
      'googleapis.com/ajax/libs',
      // third-party
      'query-monitor',
      'wpt-tx-updater-network'
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
    $_this = $this;
    // unload all styles except from the list of allowed
    $dequeue_styles = function() use($_this) {
      global $wp_styles;
      if(empty($wp_styles->queue)) return;
      foreach($wp_styles->queue as $wp_style) {
        if(empty($wp_styles->registered[$wp_style])) continue;
        $registered_style = $wp_styles->registered[$wp_style];
        if(!preg_match('!' . implode('|', $_this->permitted_assets_locations['styles']) . '!i', $registered_style->src)) {
          wp_dequeue_style($wp_style);
        }
      }
    };
    add_action('wp_print_styles', $dequeue_styles, PHP_INT_MAX);
    add_action('admin_print_styles', $dequeue_styles, PHP_INT_MAX);
    add_action('admin_print_footer_scripts', $dequeue_styles, PHP_INT_MAX);
    add_action('admin_footer', $dequeue_styles, PHP_INT_MAX);
  }

  function resolveScriptsConflict() {
    $_this = $this;
    // unload all scripts except from the list of allowed
    $dequeue_scripts = function() use($_this) {
      global $wp_scripts;
      foreach($wp_scripts->queue as $wp_script) {
        if(empty($wp_scripts->registered[$wp_script])) continue;
        $registered_script = $wp_scripts->registered[$wp_script];
        if(!preg_match('!' . implode('|', $_this->permitted_assets_locations['scripts']) . '!i', $registered_script->src)) {
          wp_dequeue_script($wp_script);
        }
      }
    };
    add_action('wp_print_scripts', $dequeue_scripts, PHP_INT_MAX);
    add_action('admin_print_footer_scripts', $dequeue_scripts, PHP_INT_MAX);
    add_action('admin_footer', $dequeue_scripts, PHP_INT_MAX);
  }
}