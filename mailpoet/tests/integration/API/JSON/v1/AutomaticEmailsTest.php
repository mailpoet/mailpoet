<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\v1;

use MailPoet\AutomaticEmails\AutomaticEmailFactory;
use MailPoet\AutomaticEmails\AutomaticEmails as AutomaticEmailsController;
use MailPoet\AutomaticEmails\WooCommerce\WooCommerce;
use MailPoet\AutomaticEmails\WooCommerce\WooCommerceEventFactory;
use MailPoet\WooCommerce\Helper;
use MailPoet\WP\Functions as WPFunctions;

class AutomaticEmailsTest extends \MailPoetTest {
  public $wp;

  /** @var AutomaticEmails */
  private $api;

  public function _before() {
    $this->wp = new WPFunctions;

    $wooCommerceEventFactory = $this->diContainer->get(WooCommerceEventFactory::class);
    $automaticEmailFactory = $this->makeEmpty(AutomaticEmailFactory::class, [
      'createWooCommerceEmail' => new WooCommerce($this->wp, new Helper(), $wooCommerceEventFactory),
    ]);
    $this->api = new AutomaticEmails(new AutomaticEmailsController($this->wp, $automaticEmailFactory), $this->wp);
  }

  public function testItRequiresProperlyFormattedRequestWhenGettingEventOptions() {
    $expectedErrorMessage = 'Improperly formatted request.';

    // query is invalid
    $data = [
      'query' => null,
      'filter' => 'filter',
      'email_slug' => 'email_slug',
      'event_slug' => 'event_slug',
    ];
    $result = $this->api->getEventOptions($data);
    expect($result->errors[0]['message'])->equals($expectedErrorMessage);

    // filter is invalid
    $data = [
      'query' => 'query',
      'filter' => null,
      'email_slug' => 'email_slug',
      'event_slug' => 'event_slug',
    ];
    $result = $this->api->getEventOptions($data);
    expect($result->errors[0]['message'])->equals($expectedErrorMessage);

    // email slug is invalid
    $data = [
      'query' => 'query',
      'filter' => 'filter',
      'email_slug' => null,
      'event_slug' => 'event_slug',
    ];
    $result = $this->api->getEventOptions($data);
    expect($result->errors[0]['message'])->equals($expectedErrorMessage);

    // event slug is invalid
    $data = [
      'query' => 'query',
      'filter' => 'filter',
      'email_slug' => 'email_slug',
      'event_slug' => null,
    ];
    $result = $this->api->getEventOptions($data);
    expect($result->errors[0]['message'])->equals($expectedErrorMessage);
  }

  public function testItRequiresValidEventFilterWhenGettingEventOptions() {
    $expectedErrorMessage = 'Automatic email event filter does not exist.';

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
            'options' => [
              'remoteQueryFilter' => 'test_filter',
            ],
            'listingScheduleDisplayText' => 'sample_text',
          ],
        ],
      ];
    });
    $this->wp->addFilter('test_filter', function($query) {
      expect($query)->equals('test');
      return 'pass';
    });

    $data = [
      'query' => 'test',
      'filter' => 'invalid_filter',
      // should be 'test_filter'
      'email_slug' => 'email',
      'event_slug' => 'event_slug',
    ];
    $result = $this->api->getEventOptions($data);
    expect($result->errors[0]['message'])->equals($expectedErrorMessage);

    $this->wp->removeAllFilters('mailpoet_automatic_email_test');
    $this->wp->removeAllFilters('test_filter');
  }

  public function testItGetsEventOptions() {
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
            'options' => [
              'remoteQueryFilter' => 'test_filter',
            ],
            'listingScheduleDisplayText' => 'sample_text',
          ],
        ],
      ];
    });
    $this->wp->addFilter('test_filter', function($query) {
      expect($query)->equals('test');
      return 'pass';
    });

    $data = [
      'query' => 'test',
      'filter' => 'test_filter',
      'email_slug' => 'email',
      'event_slug' => 'event_slug',
    ];
    $result = $this->api->getEventOptions($data);
    expect($result->data)->equals('pass');

    $this->wp->removeAllFilters('mailpoet_automatic_email_test');
    $this->wp->removeAllFilters('test_filter');
  }

  public function testItRequiresProperlyFormattedRequestWhenGettingEventShortcodes() {
    $expectedErrorMessage = 'Improperly formatted request.';

    // email slug is invalid
    $data = [
      'email_slug' => null,
      'event_slug' => 'event_slug',
    ];
    $result = $this->api->getEventOptions($data);
    expect($result->errors[0]['message'])->equals($expectedErrorMessage);

    // event slug is invalid
    $data = [
      'email_slug' => 'email_slug',
      'event_slug' => null,
    ];
    $result = $this->api->getEventOptions($data);
    expect($result->errors[0]['message'])->equals($expectedErrorMessage);
  }

  public function testItRequiresValidEventWhenGettingEventShortcodes() {
    $expectedErrorMessage = 'Automatic email event does not exist.';

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

    $data = [
      'email_slug' => 'email',
      'event_slug' => 'invalid_event',
      // should be 'event_slug'
    ];
    $result = $this->api->getEventShortcodes($data);
    expect($result->errors[0]['message'])->equals($expectedErrorMessage);

    $this->wp->removeAllFilters('mailpoet_automatic_email_test');
  }

  public function testItGetsEventShortcodes() {
    $shortcodes = [
      [
        'text' => 'shortcode_text',
        'shortcode' => '[shortcode]',
      ],
    ];
    $this->wp->addFilter('mailpoet_automatic_email_test', function() use ($shortcodes) {
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
            'shortcodes' => $shortcodes,
          ],
        ],
      ];
    });

    $data = [
      'email_slug' => 'email',
      'event_slug' => 'event_slug',
    ];
    $result = $this->api->getEventShortcodes($data);
    expect($result->data['email_title'])->equals($shortcodes);

    $this->wp->removeAllFilters('mailpoet_automatic_email_test');
  }
}
