<?php
use Codeception\Util\Fixtures;
use MailPoet\Models\Subscriber;

$newsletter_body_text =

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
  array(
    'first_name' => 'John',
    'last_name' => 'John',
    'email' => 'john.doe@example.com'
  )
);

Fixtures::add(
  'form_body_template',
  array(
    array(
      'type' => 'text',
      'name' => 'First name',
      'id' => 'first_name',
      'unique' => '1',
      'static' => '0',
      'params' =>
      array(
        'label' => 'First name',
      ),
      'position' => '1',
    ),
    array(
      'type' => 'text',
      'name' => 'Nickname',
      'id' => '4',
      'unique' => '1',
      'static' => '0',
      'params' =>
      array(
        'label' => 'Nickname',
      ),
      'position' => '2',
    ),
    array(
      'type' => 'text',
      'name' => 'Age',
      'id' => '2',
      'unique' => '1',
      'static' => '0',
      'params' =>
      array(
        'required' => '',
        'validate' => 'number',
        'label' => 'Age',
      ),
      'position' => '3',
    ),
    array (
      'type' => 'divider',
      'name' => 'Divider',
      'id' => 'divider',
      'unique' => '0',
      'static' => '0',
      'params' => '',
      'position' => '4',
    ),
    array (
      'type' => 'radio',
      'name' => '3-way choice',
      'id' => '3',
      'unique' => '1',
      'static' => '0',
      'params' =>
      array (
        'values' =>
        array (
          0 =>
          array (
            'value' => '1',
          ),
          1 =>
          array (
            'value' => '2',
          ),
          2 =>
          array (
            'value' => '3',
          ),
        ),
        'required' => '',
        'label' => '3-way choice',
      ),
      'position' => '5',
    ),
    array (
      'type' => 'html',
      'name' => 'Random text or HTML',
      'id' => 'html',
      'unique' => '0',
      'static' => '0',
      'params' =>
      array (
        'text' => 'Subscribe to our newsletter and join [mailpoet_subscribers_count] other subscribers.',
      ),
      'position' => '6',
    ),
    array(
      'type' => 'text',
      'name' => 'Email',
      'id' => 'email',
      'unique' => '0',
      'static' => '1',
      'params' =>
      array(
        'label' => 'Email',
        'required' => 'true',
      ),
      'position' => '7',
    ),
    array(
      'type' => 'submit',
      'name' => 'Submit',
      'id' => 'submit',
      'unique' => '0',
      'static' => '1',
      'params' =>
      array(
        'label' => 'Subscribe!',
      ),
      'position' => '8',
    ),
  )
);

/**
 * Simple class mocking dynamic segment filter.
 */
class DynamicSegmentFilter {
  protected $ids;

  function __construct($ids) {
    $this->ids = $ids;
  }
  
  public function toSql($orm) {
    return $orm->whereIn(Subscriber::$_table . '.id', $this->ids);
  }
}