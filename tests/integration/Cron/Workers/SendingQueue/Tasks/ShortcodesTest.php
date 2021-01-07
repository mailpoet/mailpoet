<?php

namespace MailPoet\Test\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Cron\Workers\SendingQueue\Tasks\Shortcodes;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoetVendor\Idiorm\ORM;

class ShortcodesTest extends \MailPoetTest {
  public $wPPost;

  public function _before() {
    parent::_before();
    $this->wPPost = wp_insert_post(
      [
        'post_title' => 'Sample Post',
        'post_content' => 'contents',
        'post_status' => 'publish',
      ]
    );
  }

  public function testItCanReplaceShortcodes() {
    $newsletter = (object)[
      'id' => 1,
    ];
    $queue = SendingQueue::createOrUpdate([
      'task_id' => 1,
      'newsletter_id' => 1,
    ]);
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'test@xample.com',
      'first_name' => 'John',
      'last_name' => 'Doe',
    ]);
    $renderedBody = '[subscriber:firstname] [subscriber:lastname]';
    $result = Shortcodes::process($renderedBody, null, $newsletter, $subscriber, $queue);
    expect($result)->equals('John Doe');
  }

  public function testItCanReplaceShortcodesInOneStringUsingContentsFromAnother() {
    $wpPost = get_post($this->wPPost);
    $content = 'Subject line with one shortcode: [newsletter:post_title]';
    $contentSource = '<a data-post-id="' . $this->wPPost . '" href="#">latest post</a>';

    // [newsletter:post_title] depends on the "data-post-id" tag and the shortcode will
    // get replaced with an empty string if that tag is not found
    expect(trim(Shortcodes::process($content)))->equals('Subject line with one shortcode:');

    // when tag is found, the shortcode will be processed and replaced
    expect(Shortcodes::process($content, $contentSource))->equals('Subject line with one shortcode: ' . $wpPost->post_title); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    wp_delete_post($this->wPPost, true);
  }
}
