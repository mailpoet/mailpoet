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