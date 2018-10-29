<?php

namespace MailPoet\Test\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Cron\Workers\SendingQueue\Tasks\Shortcodes;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;

if(!defined('ABSPATH')) exit;

class ShortcodesTest extends \MailPoetTest {
  function _before() {
    $this->WP_post = wp_insert_post(
      array(
        'post_title' => 'Sample Post',
        'post_content' => 'contents',
        'post_status' => 'publish',
      )
    );
  }

  function testItCanReplaceShortcodes() {
    $queue = $newsletter = (object)array(
      'id' => 1
    );
    $subscriber = (object)array(
      'email' => 'test@xample. com',
      'first_name' => 'John',
      'last_name' => 'Doe'
    );
    $rendered_body = '[subscriber:firstname] [subscriber:lastname]';
    $result = Shortcodes::process($rendered_body, null, $newsletter, $subscriber, $queue);
    expect($result)->equals('John Doe');
  }

  function testItCanReplaceShortcodesInOneStringUsingContentsFromAnother() {
    $wp_post = get_post($this->WP_post);
    $content = 'Subject line with one shortcode: [newsletter:post_title]';
    $content_source = '<a data-post-id="' . $this->WP_post . '" href="#">latest post</a>';

    // [newsletter:post_title] depends on the "data-post-id" tag and the shortcode will
    // get replaced with an empty string if that tag is not found
    expect(trim(Shortcodes::process($content)))->equals('Subject line with one shortcode:');

    // when tag is found, the shortcode will be processed and replaced
    expect(Shortcodes::process($content, $content_source))->equals('Subject line with one shortcode: ' . $wp_post->post_title);
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    wp_delete_post($this->WP_post, true);
  }
}