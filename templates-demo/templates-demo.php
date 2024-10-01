<?php

/**
 * Plugin Name:       Templates Demo
 * Description:       Example code for registering plugin block templates with WordPress 6.7+.
 * Version:           1.0.0
 * Requires at least: 6.7
 * Requires PHP:      7.4
 * Text Domain:       devblog-plugin-templates
 */

add_action('init', 'register_plugin_templates');

function register_plugin_templates() {
  // SAMPLE TEMPLATE FOR POST
  wp_register_block_template( 'templates-demo//demo1',
    [
      'title' => 'Demo 1 Plugin Template Title',
      'description' => 'This is a demo plugin template',
      'content' => file_get_contents( __DIR__ . '/demo1.html' ),
      'post_types' => ['post'],
    ]
  );
  // TEMPLATE FOR MAILPOET EMAILS
  wp_register_block_template( 'templates-demo//mailpoet-email',
    [
      'title' => 'MailPoet Email Template',
      'description' => 'This is a MailPoet email template',
      'content' => file_get_contents( __DIR__ . '/mailpoetemail.html' ),
      'post_types' => ['mailpoet_email'],
    ]
  );
}
