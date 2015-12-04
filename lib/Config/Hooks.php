<?php
namespace MailPoet\Config;

class Hooks {
  function __construct() {
  }

  function init() {
    // WP Users synchronization
    add_action(
      'user_register',
      '\MailPoet\Segments\WP::synchronizeUser',
      1
    );
    add_action(
      'added_existing_user',
      '\MailPoet\Segments\WP::synchronizeUser',
      1
    );
    add_action(
      'profile_update',
      '\MailPoet\Segments\WP::synchronizeUser',
      1
    );
    add_action(
      'delete_user',
      '\MailPoet\Segments\WP::synchronizeUser',
      1
    );
    // multisite
    add_action(
      'deleted_user',
      '\MailPoet\Segments\WP::synchronizeUser',
      1
    );
    add_action(
      'remove_user_from_blog',
      '\MailPoet\Segments\WP::synchronizeUser',
      1
    );

    add_filter(
      'image_size_names_choose',
      array(
        $this,
        'appendImageSizes'
      )
    );
  }

  function appendImageSizes($sizes) {
    return array_merge($sizes, array(
      'mailpoet_newsletter_max' => __('MailPoet Newsletter'),
    ));
  }
}
