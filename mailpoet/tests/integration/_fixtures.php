<?php declare(strict_types = 1);

use Codeception\Util\Fixtures;

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
         },
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
                   "type": "button",
                   "text": "Action!",
                   "url": "",
                   "styles": {
                     "block": {"backgroundColor":"#2ea1cd","borderColor":"#0074a2","borderWidth":"1px","borderRadius":"5px","borderStyle":"solid","width":"180px","lineHeight":"40px","fontColor":"#ffffff","fontFamily":"Verdana","fontSize":"18px","fontWeight":"normal","textAlign":"center"}
                   }
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


Fixtures::add('gutenberg_email_body', '<!-- wp:mailpoet/text-ported-block {"legacyBlockData":{"type":"text","text":"\u003ch1 data-post-id=\u00221\u0022 style=\u0022text-align: left;\u0022\u003eHello world!\u003c/h1\u003e"}} /-->

<!-- wp:mailpoet/text-ported-block {"legacyBlockData":{"type":"text","text":"\u003cp class=\u0022mailpoet_wp_post\u0022\u003eWelcome to WordPress. \u003ca href=\u0022https://example.com\u0022\u003eThis is your first post\u003c/a\u003e. Edit or delete it, then start writing!\u003c/p\u003e"}} /-->

<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:mailpoet/text-ported-block {"legacyBlockData":{"type":"text","text":"\u003cp\u003eText in a column. Text in a column. Text in a column. Text in a column. Text in a column. Text in a column. Text in a column.\u003c/p\u003e"}} /--></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:mailpoet/image-ported-block {"legacyBlockData":{"type":"image","link":"","src":"http://mp3.localhost/wp-content/uploads/2023/05/t-shirt-with-logo-1.jpg","alt":"","fullWidth":false,"width":"246px","height":"800px","styles":{"block":{"textAlign":"center"}}}} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:mailpoet/button-ported-block {"legacyBlockData":{"type":"button","text":"Read more","url":"http://mp3.localhost/2023/04/11/hello-world/","styles":{"block":{"backgroundColor":"#707678","borderColor":"#0074a2","borderWidth":"1px","borderRadius":"5px","borderStyle":"solid","width":"180px","lineHeight":"40px","fontColor":"#ffffff","fontFamily":"Verdana","fontSize":"18px","fontWeight":"normal","textAlign":"center"}},"context":"posts.readMoreButton"}} /-->

<!-- wp:mailpoet/text-ported-block {"legacyBlockData":{"type":"text","text":"\u003cp style=\u0022text-align: center;\u0022\u003e\u003ca href=\u0022https://example.com\u0022\u003eUnsubscribe link\u003c/a\u003e\u003c/p\u003e"}} /-->');
