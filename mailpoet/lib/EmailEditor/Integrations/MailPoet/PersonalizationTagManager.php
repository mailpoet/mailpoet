<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;
use MailPoet\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\Link;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\LinksToShortcodesConvertor;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\Site;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\Subscriber;
use MailPoet\WP\Functions as WPFunctions;

class PersonalizationTagManager {
  private Subscriber $subscriber;
  private Site $site;
  private Link $link;
  private WPFunctions $wp;
  private LinksToShortcodesConvertor $linksToShortcodesConvertor;

  public function __construct(
    Subscriber $subscriber,
    Site $site,
    Link $link,
    WPFunctions $wp,
    LinksToShortcodesConvertor $linksToShortcodesConvertor
  ) {
    $this->subscriber = $subscriber;
    $this->site = $site;
    $this->link = $link;
    $this->wp = $wp;
    $this->linksToShortcodesConvertor = $linksToShortcodesConvertor;
  }

  public function initialize() {
    $this->wp->addFilter('mailpoet_email_editor_register_personalization_tags', function( Personalization_Tags_Registry $registry ): Personalization_Tags_Registry {
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

      // Links registration
      $registry->register(new Personalization_Tag(
        __('Unsubscribe URL', 'mailpoet'),
        'mailpoet/subscription-unsubscribe-url',
        __('Link', 'mailpoet'),
        [$this->link, 'getSubscriptionUnsubscribeUrl'],
      ));
      $registry->register(new Personalization_Tag(
        __('Manage subscription URL', 'mailpoet'),
        'mailpoet/subscription-manage-url',
        __('Link', 'mailpoet'),
        [$this->link, 'getSubscriptionManageUrl'],
      ));
      $registry->register(new Personalization_Tag(
        __('View in browser URL', 'mailpoet'),
        'mailpoet/newsletter-view-in-browser-url',
        __('Link', 'mailpoet'),
        [$this->link, 'getNewsletterViewInBrowserUrl'],
      ));
      return $registry;
    });

    // Convert links to shortcodes before sending the email
    // This is a temporary solution so that we are able to integrate the new personalization tags
    // It is needed until we have a proper solution for the personalization tags in the MailPoet Link tracking system
    $this->wp->addFilter(
      'mailpoet_sending_newsletter_render_after_pre_process',
      [$this, 'convertLinksToShortcodes']
    );
  }

  public function convertLinksToShortcodes(array $emailContent): array {
    if (!isset($emailContent['html'])) {
      return $emailContent;
    }
    $emailContent['html'] = $this->linksToShortcodesConvertor->convertLinkTagsToShortcodes($emailContent['html']);
    return $emailContent;
  }
}
