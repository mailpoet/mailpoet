<?php

namespace MailPoet\AutomaticEmails;

use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;

class AutomaticEmails {
  const FILTER_PREFIX = 'mailpoet_automatic_email_';

  private $wp;

  /** @var array|null */
  private $automaticEmails;

  public $availableGroups = [
    'WooCommerce',
  ];

  public function __construct() {
    $this->wp = new WPFunctions;
  }

  public function init() {
    foreach ($this->availableGroups as $group) {
      $groupClass = sprintf(
        '%1$s\%2$s\%2$s',
        __NAMESPACE__,
        $group
      );

      if (!class_exists($groupClass)) {
        $this->displayGroupWarning($group);
        continue;
      }

      $groupInstance = new $groupClass();

      if (method_exists($groupInstance, 'init')) {
        $groupInstance->init();
      } else {
        $this->displayGroupWarning($group);
        continue;
      }
    }
  }

  public function getAutomaticEmails() {
    global $wp_filter; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps

    if ($this->automaticEmails) {
      return $this->automaticEmails;
    }

    // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    $registeredGroups = preg_grep('!^' . self::FILTER_PREFIX . '(.*?)$!', array_keys($wp_filter));

    if (empty($registeredGroups)) return null;

    $automaticEmails = [];
    foreach ($registeredGroups as $group) {
      $automaticEmail = $this->wp->applyFilters($group, []);

      if (!$this->validateAutomaticEmailDataFields($automaticEmail) ||
        !$this->validateAutomaticEmailEventsDataFields($automaticEmail['events'])
      ) {
        continue;
      }

      // keys associative events array by slug
      $automaticEmail['events'] = array_column($automaticEmail['events'], null, 'slug');
      // keys associative automatic email array by slug
      $automaticEmails[$automaticEmail['slug']] = $automaticEmail;
    }

    $this->automaticEmails = $automaticEmails;

    return $automaticEmails;
  }

  public function getAutomaticEmailBySlug($emailSlug) {
    $automaticEmails = $this->getAutomaticEmails();

    if (empty($automaticEmails)) return null;

    foreach ($automaticEmails as $email) {
      if (!empty($email['slug']) && $email['slug'] === $emailSlug) return $email;
    }

    return null;
  }

  public function getAutomaticEmailEventBySlug($emailSlug, $eventSlug) {
    $automaticEmail = $this->getAutomaticEmailBySlug($emailSlug);

    if (empty($automaticEmail)) return null;

    foreach ($automaticEmail['events'] as $event) {
      if (!empty($event['slug']) && $event['slug'] === $eventSlug) return $event;
    }

    return null;
  }

  public function validateAutomaticEmailDataFields(array $automaticEmail) {
    $requiredFields = [
      'slug',
      'title',
      'description',
      'events',
    ];

    foreach ($requiredFields as $field) {
      if (empty($automaticEmail[$field])) return false;
    }

    return true;
  }

  public function validateAutomaticEmailEventsDataFields(array $automaticEmailEvents) {
    $requiredFields = [
      'slug',
      'title',
      'description',
      'listingScheduleDisplayText',
    ];

    foreach ($automaticEmailEvents as $event) {
      $validEvent = array_diff($requiredFields, array_keys($event));
      if (!empty($validEvent)) return false;
    }

    return true;
  }

  public function unregisterAutomaticEmails() {
    global $wp_filter; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps

    // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    $registeredGroups = preg_grep('!^' . self::FILTER_PREFIX . '(.*?)$!', array_keys($wp_filter));

    if (empty($registeredGroups)) return null;

    $self = $this;
    array_map(function($group) use($self) {
      $self->wp->removeAllFilters($group);
    }, $registeredGroups);

    $this->automaticEmails = null;
  }

  private function displayGroupWarning($group) {
    $notice = sprintf('%s %s',
      sprintf(__('%s automatic email group is misconfigured.', 'mailpoet'), $group),
      WPFunctions::get()->__('Please contact our technical support for assistance.', 'mailpoet')
    );
    Notice::displayWarning($notice);
  }
}
