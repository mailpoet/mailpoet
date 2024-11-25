<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\Subscriber;

class PersonalizationTagManager {
  private Subscriber $subscriber;

  public function __construct(
    Subscriber $subscriber
  ) {
    $this->subscriber = $subscriber;
  }

  public function initialize() {
    add_filter('mailpoet_email_editor_register_personalization_tags', function( Personalization_Tags_Registry $registry ): Personalization_Tags_Registry {
      $registry->register(
        __('First Name', 'mailpoet'),
        'mailpoet/subscriber-firstname',
        __('Subscriber', 'mailpoet'),
        [$this->subscriber, 'getFirstName'],
        ['default' => __('subscriber', 'mailpoet')],
      );
      $registry->register(
        __('Last Name', 'mailpoet'),
        'mailpoet/subscriber-lastname',
        __('Subscriber', 'mailpoet'),
        [$this->subscriber, 'getLastName'],
        ['default' => __('subscriber', 'mailpoet')],
      );
      $registry->register(
        __('Email', 'mailpoet'),
        'mailpoet/subscriber-email',
        __('Subscriber', 'mailpoet'),
        [$this->subscriber, 'getEmail'],
      );
      return $registry;
    });
  }
}
