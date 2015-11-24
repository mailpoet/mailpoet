<?php
namespace MailPoet\Config;

class Hooks {
  function __construct() {
  }

  function init() {
    // WP Users synchronization
    add_action(
      'user_register',
      '\MailPoet\Filters\WP::synchronizeUser',
      1
    );
    add_action(
      'added_existing_user',
      '\MailPoet\Filters\WP::synchronizeUser',
      1
    );
    add_action(
      'profile_update',
      '\MailPoet\Filters\WP::synchronizeUser',
      1
    );
    add_action(
      'delete_user',
      '\MailPoet\Filters\WP::synchronizeUser',
      1
    );
    // multisite
    add_action(
      'deleted_user',
      '\MailPoet\Filters\WP::synchronizeUser',
      1
    );
    add_action(
      'remove_user_from_blog',
      '\MailPoet\Filters\WP::synchronizeUser',
      1
    );
  }
}