<?php
use Codeception\Util\Fixtures;

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
                   "text": "<a data-post-id=\"10\" href=\"http://example.com\">Link</a>Hello [subscriber:firstname | default:test]"
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
