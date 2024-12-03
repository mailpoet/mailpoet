<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;
use MailPoet\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\Site;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\Subscriber;

class PersonalizationTagManager {
  private Subscriber $subscriber;
  private Site $site;

  public function __construct(
    Subscriber $subscriber,
    Site $site
  ) {
    $this->subscriber = $subscriber;
    $this->site = $site;
  }

  public function initialize() {
    add_filter('mailpoet_email_editor_register_personalization_tags', function( Personalization_Tags_Registry $registry ): Personalization_Tags_Registry {
      // Subscriber Personalization Tags
      $registry->register(new Personalization_Tag(
        __('First Name', 'mailpoet'),
        'mailpoet/subscriber-firstname',
        __('Subscriber', 'mailpoet'),
        [$this->subscriber, 'getFirstName'],
        ['default' => __('subscriber', 'mailpoet')],
      ));
      $registry->register(new Personalization_Tag(
        __('Last Name', 'mailpoet'),
        'mailpoet/subscriber-lastname',
        __('Subscriber', 'mailpoet'),
        [$this->subscriber, 'getLastName'],
        ['default' => __('subscriber', 'mailpoet')],
      ));
      $registry->register(new Personalization_Tag(
        __('Email', 'mailpoet'),
        'mailpoet/subscriber-email',
        __('Subscriber', 'mailpoet'),
        [$this->subscriber, 'getEmail'],
      ));
      // Site Personalization Tags
      $registry->register(new Personalization_Tag(
        __('Site Title', 'mailpoet'),
        'mailpoet/site-title',
        __('Site', 'mailpoet'),
        [$this->site, 'getTitle'],
      ));
      $registry->register(new Personalization_Tag(
        __('Homepage URL', 'mailpoet'),
        'mailpoet/site-homepage-url',
        __('Site', 'mailpoet'),
        [$this->site, 'getHomepageURL'],
      ));
      return $registry;
    });
  }
}
