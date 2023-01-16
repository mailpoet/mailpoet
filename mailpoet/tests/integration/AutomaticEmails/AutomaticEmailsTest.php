<?php declare(strict_types = 1);

namespace MailPoet\AutomaticEmails;

use MailPoet\AutomaticEmails\WooCommerce\WooCommerce;
use MailPoet\AutomaticEmails\WooCommerce\WooCommerceEventFactory;
use MailPoet\WooCommerce\Helper;
use MailPoet\WP\Functions as WPFunctions;

class AutomaticEmailsTest extends \MailPoetTest {
  public $wp;
  public $AM;

  /** @var WooCommerceEventFactory */
  private $wooCommerceEventFactory;

  /** @var AutomaticEmailFactory */
  private $automaticEmailFactory;

  public function _before() {
    $this->wp = new WPFunctions();
    $this->wooCommerceEventFactory = $this->diContainer->get(WooCommerceEventFactory::class);
    $this->automaticEmailFactory = $this->makeEmpty(AutomaticEmailFactory::class, [
      'createWooCommerceEmail' => new WooCommerce($this->wp, new Helper($this->wp), $this->wooCommerceEventFactory),
    ]);
    $this->AM = new AutomaticEmails($this->wp, $this->automaticEmailFactory);
  }

  public function testItCanUnregisterAutomaticEmails() {
    $this->wp->addFilter('mailpoet_automatic_email_test1', function() {
      return [
        'slug' => 'email1',
        'title' => 'email1_title',
        'description' => 'email1_description',
        'events' => [
          [
            'slug' => 'event_slug',
            'title' => 'event_title',
            'description' => 'event_description',
            'listingScheduleDisplayText' => 'sample_text',
          ],
        ],
      ];
    });

    $result = $this->AM->getAutomaticEmails();
    expect($result)->hasKey('email1');
    $this->AM->unregisterAutomaticEmails();
    $result = $this->AM->getAutomaticEmails();
    expect($result)->null();
  }

  public function testItGetsAutomaticEmails() {
    $this->wp->addFilter('mailpoet_automatic_email_test1', function() {
      return [
        'slug' => 'email1',
        'title' => 'email1_title',
        'description' => 'email1_description',
        'events' => [
          [
            'slug' => 'event_slug',
            'title' => 'event_title',
            'description' => 'event_description',
            'listingScheduleDisplayText' => 'sample_text',
          ],
        ],
      ];
    });

    // doees not use 'mailpoet_automatic_email_' prefix
    $this->wp->addFilter('mailpoet_automatic_test2', function() {
      return [
        'slug' => 'email2',
        'title' => 'email2_title',
        'description' => 'email2_description',
        'events' => [
          [
            'slug' => 'event_slug',
            'title' => 'event_title',
            'description' => 'event_description',
            'listingScheduleDisplayText' => 'sample_text',
          ],
        ],
      ];
    });

    $result = $this->AM->getAutomaticEmails();
    expect($result)->hasKey('email1');
    expect($result)->hasNotKey('email2');

    $this->wp->removeAllFilters('mailpoet_automatic_email_test1');
    $this->wp->removeAllFilters('mailpoet_automatic_email_test2');
  }

  public function testItReturnsNullWhenGettingEmailBySlugAndThereAreNoRegisteredEmails() {
    expect($this->AM->getAutomaticEmailBySlug('some_slug'))->null();

  }

  public function testItReturnsNullWhenItCannotGetEmailBySlug() {
    $this->wp->addFilter('mailpoet_automatic_email_test', function() {
      return [
        'slug' => 'email',
        'title' => 'email_title',
        'description' => 'email_description',
        'events' => [
          [
            'slug' => 'event_slug',
            'title' => 'event_title',
            'description' => 'event_description',
            'listingScheduleDisplayText' => 'sample_text',
          ],
        ],
      ];
    });

    expect($this->AM->getAutomaticEmailBySlug('some_slug'))->null();

    $this->wp->removeAllFilters('mailpoet_automatic_email_test');
  }

  public function testItGetsEmailBySlug() {
    $this->wp->addFilter('mailpoet_automatic_email_test1', function() {
      return [
        'slug' => 'email1',
        'title' => 'email1_title',
        'description' => 'email1_description',
        'events' => [
          [
            'slug' => 'event_slug',
            'title' => 'event_title',
            'description' => 'event_description',
            'listingScheduleDisplayText' => 'sample_text',
          ],
        ],
      ];
    });

    $this->wp->addFilter('mailpoet_automatic_email_test2', function() {
      return [
        'slug' => 'email2',
        'title' => 'email2_title',
        'description' => 'email2_description',
        'events' => [
          [
            'slug' => 'event_slug',
            'title' => 'event_title',
            'description' => 'event_description',
            'listingScheduleDisplayText' => 'sample_text',
          ],
        ],
      ];
    });

    $result = $this->AM->getAutomaticEmailBySlug('email1');
    expect($result['slug'])->equals('email1');

    $this->wp->removeAllFilters('mailpoet_automatic_email_test');
  }

  public function testItReturnsNullWhenGettingEmailEventBySlugAndThereAreNoRegisteredEmails() {
    expect($this->AM->getAutomaticEmailEventBySlug('some_email', 'some_slug'))->null();
  }

  public function testItReturnsNullWhenItCannotGetEmailEventBySlug() {
    $this->wp->addFilter('mailpoet_automatic_email_test', function() {
      return [
        'slug' => 'email',
        'title' => 'email_title',
        'description' => 'email_description',
        'events' => [
          [
            'slug' => 'event_slug',
            'title' => 'event_title',
            'description' => 'event_description',
            'listingScheduleDisplayText' => 'sample_text',
          ],
        ],
      ];
    });

    expect($this->AM->getAutomaticEmailEventBySlug('emai', 'some_slug'))->null();

    $this->wp->removeAllFilters('mailpoet_automatic_email_test');
  }

  public function testItGetsEmailEventBySlug() {
    $this->wp->addFilter('mailpoet_automatic_email_test', function() {
      return [
        'slug' => 'email',
        'title' => 'email_title',
        'description' => 'email_description',
        'events' => [
          [
            'slug' => 'event1_slug',
            'title' => 'event1_title',
            'description' => 'event1_description',
            'listingScheduleDisplayText' => 'sample_text',
          ],
          [
            'slug' => 'event2_slug',
            'title' => 'event2_title',
            'description' => 'event2_description',
            'listingScheduleDisplayText' => 'sample_text',
          ],
        ],
      ];
    });

    $result = $this->AM->getAutomaticEmailEventBySlug('email', 'event2_slug');
    expect($result['slug'])->equals('event2_slug');

    $this->wp->removeAllFilters('mailpoet_automatic_email_test');
  }

  public function testItValidatesEmailDataFields() {
    // slug is missing
    $data = [
      'title' => true,
      'description' => true,
      'events' => true,
    ];
    expect($this->AM->validateAutomaticEmailDataFields($data))->false();

    // title is missing
    $data = [
      'slug' => true,
      'description' => true,
      'events' => true,
    ];
    expect($this->AM->validateAutomaticEmailDataFields($data))->false();

    // description is missing
    $data = [
      'slug' => true,
      'title' => true,
      'events' => true,
    ];
    expect($this->AM->validateAutomaticEmailDataFields($data))->false();

    // events are missing
    $data = [
      'slug' => true,
      'title' => true,
      'description' => true,
    ];
    expect($this->AM->validateAutomaticEmailDataFields($data))->false();

    // valid object
    $data = [
      'slug' => true,
      'title' => true,
      'description' => true,
      'events' => true,
    ];
    expect($this->AM->validateAutomaticEmailDataFields($data))->true();
  }

  public function testItValidatesEmailEventsDataFields() {
    // slug is missing
    $data = [
      [
        'title' => true,
        'description' => true,
        'listingScheduleDisplayText' => true,
      ],
    ];
    expect($this->AM->validateAutomaticEmailEventsDataFields($data))->false();

    // title is missing
    $data = [
      [
        'slug' => true,
        'description' => true,
        'listingScheduleDisplayText' => true,
      ],
    ];
    expect($this->AM->validateAutomaticEmailEventsDataFields($data))->false();

    // description is missing
    $data = [
      [
        'slug' => true,
        'title' => true,
        'listingScheduleDisplayText' => true,
      ],
    ];
    expect($this->AM->validateAutomaticEmailEventsDataFields($data))->false();

    // listingScheduleDisplayText is missing
    $data = [
      [
        'slug' => true,
        'title' => true,
        'description' => true,
      ],
    ];
    expect($this->AM->validateAutomaticEmailEventsDataFields($data))->false();

    // valid object
    $data = [
      [
        'slug' => true,
        'title' => true,
        'description' => true,
        'listingScheduleDisplayText' => true,
      ],
    ];

    expect($this->AM->validateAutomaticEmailEventsDataFields($data))->true();
  }
}
