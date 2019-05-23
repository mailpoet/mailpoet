<?php
namespace MailPoet\Util;

use MailPoet\WP\Functions as WPFunctions;

class ConflictResolver {
  public $permitted_assets_locations = [
    'styles' => [
      // WP default
      '^/wp-admin',
      '^/wp-includes',
      // CDN
      'googleapis.com/ajax/libs',
      'wp.com',
      // third-party
      'query-monitor',
      'wpt-tx-updater-network',
    ],
    'scripts' => [
      // WP default
      '^/wp-admin',
      '^/wp-includes',
      // CDN
      'googleapis.com/ajax/libs',
      'wp.com',
      // third-party
      'query-monitor',
      'wpt-tx-updater-network',
    ],
  ];

  function init() {
    WPFunctions::get()->addAction(
      'mailpoet_conflict_resolver_router_url_query_parameters',
      [
        $this,
        'resolveRouterUrlQueryParametersConflict',
      ]
    );
    WPFunctions::get()->addAction(
      'mailpoet_conflict_resolver_styles',
      [
        $this,
        'resolveStylesConflict',
      ]
    );
    WPFunctions::get()->addAction(
      'mailpoet_conflict_resolver_scripts',
      [
        $this,
        'resolveScriptsConflict',
      ]
    );
  }

  function resolveRouterUrlQueryParametersConflict() {
    // prevents other plugins from overtaking URL query parameters 'action=' and 'endpoint='
    unset($_GET['endpoint'], $_GET['action']);
  }

  function resolveStylesConflict() {
    $_this = $this;
    $_this->permitted_assets_locations['styles'] = WPFunctions::get()->applyFilters('mailpoet_conflict_resolver_whitelist_style', $_this->permitted_assets_locations['styles']);
    // unload all styles except from the list of allowed
    $dequeue_styles = function() use($_this) {
      global $wp_styles;
      if (!isset($wp_styles->registered)) return;
      if (empty($wp_styles->queue)) return;
      foreach ($wp_styles->queue as $wp_style) {
        if (empty($wp_styles->registered[$wp_style])) continue;
        $registered_style = $wp_styles->registered[$wp_style];
        if (!is_string($registered_style->src)) {
          continue;
        }
        if (!preg_match('!' . implode('|', $_this->permitted_assets_locations['styles']) . '!i', $registered_style->src)) {
          WPFunctions::get()->wpDequeueStyle($wp_style);
        }
      }
    };

    // execute last in the following hooks
    $execute_last = PHP_INT_MAX;
    WPFunctions::get()->addAction('admin_enqueue_scripts', $dequeue_styles, $execute_last); // used also for styles
    WPFunctions::get()->addAction('admin_footer', $dequeue_styles, $execute_last);

    // execute first in hooks for printing (after printing is too late)
    $execute_first = defined('PHP_INT_MIN') ? constant('PHP_INT_MIN') : ~PHP_INT_MAX;
    WPFunctions::get()->addAction('admin_print_styles', $dequeue_styles, $execute_first);
    WPFunctions::get()->addAction('admin_print_footer_scripts', $dequeue_styles, $execute_first);
  }

  function resolveScriptsConflict() {
    $_this = $this;
    $_this->permitted_assets_locations['scripts'] = WPFunctions::get()->applyFilters('mailpoet_conflict_resolver_whitelist_script', $_this->permitted_assets_locations['scripts']);
    // unload all scripts except from the list of allowed
    $dequeue_scripts = function() use($_this) {
      global $wp_scripts;
      foreach ($wp_scripts->queue as $wp_script) {
        if (empty($wp_scripts->registered[$wp_script])) continue;
        $registered_script = $wp_scripts->registered[$wp_script];
        if (!is_string($registered_script->src)) {
          continue;
        }
        if (!preg_match('!' . implode('|', $_this->permitted_assets_locations['scripts']) . '!i', $registered_script->src)) {
          WPFunctions::get()->wpDequeueScript($wp_script);
        }
      }
    };

    // execute last in the following hooks
    $execute_last = PHP_INT_MAX;
    WPFunctions::get()->addAction('admin_enqueue_scripts', $dequeue_scripts, $execute_last);
    WPFunctions::get()->addAction('admin_footer', $dequeue_scripts, $execute_last);

    // execute first in hooks for printing (after printing is too late)
    $execute_first = defined('PHP_INT_MIN') ? constant('PHP_INT_MIN') : ~PHP_INT_MAX;
    WPFunctions::get()->addAction('admin_print_scripts', $dequeue_scripts, $execute_first);
    WPFunctions::get()->addAction('admin_print_footer_scripts', $dequeue_scripts, $execute_first);
  }
}
