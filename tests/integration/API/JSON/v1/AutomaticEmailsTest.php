<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\v1\AutomaticEmails;
use MailPoet\WP\Functions as WPFunctions;

class AutomaticEmailsTest extends \MailPoetTest {
  function _before() {
    $this->wp = new WPFunctions;
  }
  function testItRequiresProperlyFormattedRequestWhenGettingEventOptions() {
    $API = new AutomaticEmails();
    $expected_error_message = 'Improperly formatted request.';

    // query is invalid
    $data = [
      'query' => null,
      'filter' => 'filter',
      'email_slug' => 'email_slug',
      'event_slug' => 'event_slug',
    ];
    $result = $API->getEventOptions($data);
    expect($result->errors[0]['message'])->equals($expected_error_message);

    // filter is invalid
    $data = [
      'query' => 'query',
      'filter' => null,
      'email_slug' => 'email_slug',
      'event_slug' => 'event_slug',
    ];
    $result = $API->getEventOptions($data);
    expect($result->errors[0]['message'])->equals($expected_error_message);

    // email slug is invalid
    $data = [
      'query' => 'query',
      'filter' => 'filter',
      'email_slug' => null,
      'event_slug' => 'event_slug',
    ];
    $result = $API->getEventOptions($data);
    expect($result->errors[0]['message'])->equals($expected_error_message);

    // event slug is invalid
    $data = [
      'query' => 'query',
      'filter' => 'filter',
      'email_slug' => 'email_slug',
      'event_slug' => null,
    ];
    $result = $API->getEventOptions($data);
    expect($result->errors[0]['message'])->equals($expected_error_message);
  }

  function testItRequiresValidEventFilterWhenGettingEventOptions() {
    $API = new AutomaticEmails();
    $expected_error_message = 'Automatic email event filter does not exist.';

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
    $result = $API->getEventOptions($data);
    expect($result->errors[0]['message'])->equals($expected_error_message);

    $this->wp->removeAllFilters('mailpoet_automatic_email_test');
    $this->wp->removeAllFilters('test_filter');
  }

  function testItGetsEventOptions() {
    $API = new AutomaticEmails();

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
    $result = $API->getEventOptions($data);
    expect($result->data)->equals('pass');

    $this->wp->removeAllFilters('mailpoet_automatic_email_test');
    $this->wp->removeAllFilters('test_filter');
  }

  function testItRequiresProperlyFormattedRequestWhenGettingEventShortcodes() {
    $API = new AutomaticEmails();
    $expected_error_message = 'Improperly formatted request.';

    // email slug is invalid
    $data = [
      'email_slug' => null,
      'event_slug' => 'event_slug',
    ];
    $result = $API->getEventOptions($data);
    expect($result->errors[0]['message'])->equals($expected_error_message);

    // event slug is invalid
    $data = [
      'email_slug' => 'email_slug',
      'event_slug' => null,
    ];
    $result = $API->getEventOptions($data);
    expect($result->errors[0]['message'])->equals($expected_error_message);
  }

  function testItRequiresValidEventWhenGettingEventShortcodes() {
    $API = new AutomaticEmails();
    $expected_error_message = 'Automatic email event does not exist.';

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
    $result = $API->getEventShortcodes($data);
    expect($result->errors[0]['message'])->equals($expected_error_message);

    $this->wp->removeAllFilters('mailpoet_automatic_email_test');
  }

  function testItGetsEventShortcodes() {
    $API = new AutomaticEmails();
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
    $result = $API->getEventShortcodes($data);
    expect($result->data['email_title'])->equals($shortcodes);

    $this->wp->removeAllFilters('mailpoet_automatic_email_test');
  }
}
