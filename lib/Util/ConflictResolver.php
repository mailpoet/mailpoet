<?php
namespace MailPoet\Util;

use MailPoet\WP\Functions as WPFunctions;

class ConflictResolver {
  public $permitted_assets_locations = [
    'styles' => [
      'mailpoet',
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
      'mailpoet',
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
    WPFunctions::get()->addAction(
      'mailpoet_conflict_resolver_scripts',
      [
        $this,
        'resolveEditorConflict',
      ]
    );
    WPFunctions::get()->addAction(
      'mailpoet_conflict_resolver_scripts',
      [
        $this,
        'resolveTinyMceConflict',
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

  function resolveEditorConflict() {

    // mark editor as already enqueued to prevent loading its assets
    // when wp_enqueue_editor() used by some other plugin
    global $wp_actions;
    $wp_actions['wp_enqueue_editor'] = 1;

    // prevent editor loading when used wp_editor() used by some other plugin
    WPFunctions::get()->addFilter('wp_editor_settings', function () {
      ob_start();
      return [
        'tinymce' => false,
        'quicktags' => false,
      ];
    });

    WPFunctions::get()->addFilter('the_editor', function () {
      return '';
    });

    WPFunctions::get()->addFilter('the_editor_content', function () {
      ob_end_clean();
      return '';
    });
  }

  function resolveTinyMceConflict() {
    // WordPress TinyMCE scripts may not get enqueued as scripts when some plugins use wp_editor()
    // or wp_enqueue_editor(). Instead, they are printed inside the footer script print actions.
    // To unload TinyMCE we need to remove those actions.
    $tiny_mce_footer_script_hooks = [
      '_WP_Editors::enqueue_scripts',
      '_WP_Editors::editor_js',
      '_WP_Editors::force_uncompressed_tinymce',
      '_WP_Editors::print_default_editor_scripts',
    ];

    $disable_wp_tinymce = function() use ($tiny_mce_footer_script_hooks) {
      global $wp_filter;
      $action_name = 'admin_print_footer_scripts';
      if (!isset($wp_filter[$action_name])) {
        return;
      }
      foreach ($wp_filter[$action_name]->callbacks as $priority => $callbacks) {
        foreach ($tiny_mce_footer_script_hooks as $hook) {
          if (isset($callbacks[$hook])) {
            WPFunctions::get()->removeAction($action_name, $callbacks[$hook]['function'], $priority);
          }
        }
      }
    };

    WPFunctions::get()->addAction('admin_footer', $disable_wp_tinymce, PHP_INT_MAX);
  }
}
