<?php

use Codeception\Util\Fixtures;
use MailPoet\DynamicSegments\Filters\Filter;
use MailPoet\Models\Subscriber;
use MailPoetVendor\Idiorm\ORM;

$newsletterBodyText =

Fixtures::add(
  'newsletter_body_template',
  '{
     "content": {
       "type": "container",
       "orientation": "vertical",
       "blocks": [
         {
           "type": "container",
           "styles": { "block": {} },
           "orientation": "horizontal",
           "blocks": [
             {
               "type": "container",
               "orientation": "vertical",
               "styles": { "block": {} },
               "blocks": [
                 {
                   "type": "text",
                   "text": "<a href=\"[link:newsletter_view_in_browser_url]\">View in browser link</a> <a data-post-id=\"10\" href=\"http://example.com\">Post link</a> Hello [subscriber:firstname | default:test] <a href=\"[link:subscription_unsubscribe_url]\">Unsubscribe link</a> <a href=\"[link:subscription_manage_url]\">Manage subscription link</a> <img src=\"http://example.com/image with space.jpg\"> <a href=\"http://example.com/link with space.jpg\">Link with space</a>"
                 }
               ]
             }
           ]
         }
       ]
     }
   }'
);

Fixtures::add(
  'newsletter_subject_template',
  'Newsletter for [subscriber:firstname]'
);

Fixtures::add(
  'subscriber_template',
  [
    'first_name' => 'John',
    'last_name' => 'John',
    'email' => 'john.doe@example.com',
  ]
);

Fixtures::add(
  'form_body_template',
  [
    [
      'type' => 'text',
      'name' => 'First name',
      'id' => 'first_name',
      'unique' => '1',
      'static' => '0',
      'params' =>
      [
        'label' => 'First name',
      ],
      'position' => '1',
    ],
    [
      'type' => 'text',
      'name' => 'Nickname',
      'id' => '4',
      'unique' => '1',
      'static' => '0',
      'params' =>
      [
        'label' => 'Nickname',
      ],
      'position' => '2',
    ],
    [
      'type' => 'text',
      'name' => 'Age',
      'id' => '2',
      'unique' => '1',
      'static' => '0',
      'params' =>
      [
        'required' => '',
        'validate' => 'number',
        'label' => 'Age',
      ],
      'position' => '3',
    ],
     [
      'type' => 'divider',
      'name' => 'Divider',
      'id' => 'divider',
      'unique' => '0',
      'static' => '0',
      'params' => '',
      'position' => '4',
     ],
     [
      'type' => 'radio',
      'name' => '3-way choice',
      'id' => '3',
      'unique' => '1',
      'static' => '0',
      'params' =>
       [
        'values' =>
         [
          0 =>
           [
            'value' => '1',
           ],
          1 =>
           [
            'value' => '2',
           ],
          2 =>
           [
            'value' => '3',
           ],
         ],
        'required' => '',
        'label' => '3-way choice',
       ],
      'position' => '5',
     ],
     [
      'type' => 'html',
      'name' => 'Custom text or HTML',
      'id' => 'html',
      'unique' => '0',
      'static' => '0',
      'params' =>
       [
        'text' => 'Subscribe to our newsletter and join [mailpoet_subscribers_count] other subscribers.',
       ],
      'position' => '6',
     ],
    [
      'type' => 'text',
      'name' => 'Email',
      'id' => 'email',
      'unique' => '0',
      'static' => '1',
      'params' =>
      [
        'label' => 'Email',
        'required' => 'true',
      ],
      'position' => '7',
    ],
    [
      'type' => 'submit',
      'name' => 'Submit',
      'id' => 'submit',
      'unique' => '0',
      'static' => '1',
      'params' =>
      [
        'label' => 'Subscribe!',
      ],
      'position' => '8',
    ],
  ]
);

/**
 * Simple class mocking dynamic segment filter.
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration, Squiz.Classes.ClassFileName
class DynamicSegmentFilter implements Filter {
  protected $ids;

  public function __construct($ids) {
    $this->ids = $ids;
  }

  public function toSql(ORM $orm) {
    return $orm->whereIn(Subscriber::$_table . '.id', $this->ids);
  }

  public function toArray() {
    return [];
  }
}
