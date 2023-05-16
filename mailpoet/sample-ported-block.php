<?php declare(strict_types = 1);

use MailPoet\AdminPages\PageRenderer;

/**
 * Registers the `email` post type.
 */
function email_init() {
  register_post_type(
    'email',
    [
      'labels' => [
        'name' => __('Emails', 'mailpoet'),
        'singular_name' => __('Email', 'mailpoet'),
        'all_items' => __('All Emails', 'mailpoet'),
        'archives' => __('Email Archives', 'mailpoet'),
        'attributes' => __('Email Attributes', 'mailpoet'),
        'insert_into_item' => __('Insert into email', 'mailpoet'),
        'uploaded_to_this_item' => __('Uploaded to this email', 'mailpoet'),
        'featured_image' => _x('Featured Image', 'email', 'mailpoet'),
        'set_featured_image' => _x('Set featured image', 'email', 'mailpoet'),
        'remove_featured_image' => _x('Remove featured image', 'email', 'mailpoet'),
        'use_featured_image' => _x('Use as featured image', 'email', 'mailpoet'),
        'filter_items_list' => __('Filter emails list', 'mailpoet'),
        'items_list_navigation' => __('Emails list navigation', 'mailpoet'),
        'items_list' => __('Emails list', 'mailpoet'),
        'new_item' => __('New Email', 'mailpoet'),
        'add_new' => __('Add New', 'mailpoet'),
        'add_new_item' => __('Add New Email', 'mailpoet'),
        'edit_item' => __('Edit Email', 'mailpoet'),
        'view_item' => __('View Email', 'mailpoet'),
        'view_items' => __('View Emails', 'mailpoet'),
        'search_items' => __('Search emails', 'mailpoet'),
        'not_found' => __('No emails found', 'mailpoet'),
        'not_found_in_trash' => __('No emails found in trash', 'mailpoet'),
        'parent_item_colon' => __('Parent Email:', 'mailpoet'),
        'menu_name' => __('Emails', 'mailpoet'),
      ],
      'public' => true,
      'hierarchical' => false,
      'show_ui' => true,
      'show_in_nav_menus' => true,
      'supports' => ['title', 'editor'],
      'has_archive' => true,
      'rewrite' => true,
      'query_var' => true,
      'menu_position' => null,
      'menu_icon' => 'dashicons-admin-post',
      'show_in_rest' => true,
      'rest_base' => 'email',
      'rest_controller_class' => 'WP_REST_Posts_Controller',
    ]
  );

}

add_action('init', 'email_init');

function create_block_sample_ported_block_block_init() {
  register_block_type('mailpoet/sample-ported-block', [
    'editor_script' => 'mailpoet/sample-ported-block',
  ]);
}

add_action('init', 'create_block_sample_ported_block_block_init');

function add_sample_ported_block_scripts($hook) {
  global $post_type;
  if ('email' != $post_type) {
    return;
  }

  wp_enqueue_script('mailpoet_newsletter_editor', plugin_dir_url(__FILE__) . 'assets/dist/js/newsletter_editor.js', []);
  wp_enqueue_script('mailpoet_hybrid_editor', plugin_dir_url(__FILE__) . 'assets/dist/js/hybrid_editor.js', ['lodash', 'wp-blocks', 'wp-components', 'wp-element', 'wp-i18n', 'wp-block-editor']);
}

add_action('admin_enqueue_scripts', 'add_sample_ported_block_scripts');

add_action('admin_footer', function (){
  global $post_type;
  if ('email' != $post_type) {
    return;
  }

  $pageRenderer = \MailPoet\DI\ContainerWrapper::getInstance()->get(PageRenderer::class);
  $pageRenderer->displayPage('newsletter/editor.html');
});

add_action('enqueue_block_assets', function () {
  wp_enqueue_style(
    'mailpoet_sample-ported-block-editor',
    plugin_dir_url(__FILE__) . 'assets/dist/css/mailpoet-editor.css',
    false
  );
});
