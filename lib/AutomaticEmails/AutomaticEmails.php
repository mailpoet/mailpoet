<?php

namespace MailPoet\AutomaticEmails;

use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;

use function MailPoetVendor\array_column;

class AutomaticEmails {
  const FILTER_PREFIX = 'mailpoet_automatic_email_';

  private $wp;

  public $available_groups = [
    'WooCommerce',
  ];

  function __construct() {
    $this->wp = new WPFunctions;
  }

  function init() {
    foreach ($this->available_groups as $group) {
      $group_class = sprintf(
        '%1$s\%2$s\%2$s',
        __NAMESPACE__,
        $group
      );

      if (!class_exists($group_class)) {
        $this->displayGroupWarning($group);
        continue;
      }

      $group_instance = new $group_class();

      if (method_exists($group_instance, 'init')) {
        $group_instance->init();
      } else {
        $this->displayGroupWarning($group);
        continue;
      }
    }
  }

  function getAutomaticEmails() {
    global $wp_filter;

    $registered_groups = preg_grep('!^' . self::FILTER_PREFIX . '(.*?)$!', array_keys($wp_filter));

    if (empty($registered_groups)) return null;

    $automatic_emails = [];
    foreach ($registered_groups as $group) {
      $automatic_email = $this->wp->applyFilters($group, []);

      if (!$this->validateAutomaticEmailDataFields($automatic_email) ||
        !$this->validateAutomaticEmailEventsDataFields($automatic_email['events'])
      ) {
        continue;
      }

      // keys associative events array by slug
      $automatic_email['events'] = array_column($automatic_email['events'], null, 'slug');
      // keys associative automatic email array by slug
      $automatic_emails[$automatic_email['slug']] = $automatic_email;
    }

    return $automatic_emails;
  }

  function getAutomaticEmailBySlug($email_slug) {
    $automatic_emails = $this->getAutomaticEmails();

    if (empty($automatic_emails)) return null;

    foreach ($automatic_emails as $email) {
      if (!empty($email['slug']) && $email['slug'] === $email_slug) return $email;
    }

    return null;
  }

  function getAutomaticEmailEventBySlug($email_slug, $event_slug) {
    $automatic_email = $this->getAutomaticEmailBySlug($email_slug);

    if (empty($automatic_email)) return null;

    foreach ($automatic_email['events'] as $event) {
      if (!empty($event['slug']) && $event['slug'] === $event_slug) return $event;
    }

    return null;
  }

  function validateAutomaticEmailDataFields(array $automatic_email) {
    $required_fields = [
      'slug',
      'title',
      'description',
      'events',
    ];

    foreach ($required_fields as $field) {
      if (empty($automatic_email[$field])) return false;
    }

    return true;
  }

  function validateAutomaticEmailEventsDataFields(array $automatic_email_events) {
    $required_fields = [
      'slug',
      'title',
      'description',
      'listingScheduleDisplayText',
    ];

    foreach ($automatic_email_events as $event) {
      $valid_event = array_diff($required_fields, array_keys($event));
      if (!empty($valid_event)) return false;
    }

    return true;
  }

  function unregisterAutomaticEmails() {
    global $wp_filter;

    $registered_groups = preg_grep('!^' . self::FILTER_PREFIX . '(.*?)$!', array_keys($wp_filter));

    if (empty($registered_groups)) return null;

    $self = $this;
    array_map(function($group) use($self) {
      $self->wp->removeAllFilters($group);
    }, $registered_groups);
  }

  private function displayGroupWarning($group) {
    $notice = sprintf('%s %s',
      sprintf(__('%s automatic email group is misconfigured.', 'mailpoet'), $group),
      WPFunctions::get()->__('Please contact our technical support for assistance.', 'mailpoet')
    );
    Notice::displayWarning($notice);
  }
}
