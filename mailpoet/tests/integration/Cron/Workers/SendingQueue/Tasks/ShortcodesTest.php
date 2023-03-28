<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Cron\Workers\SendingQueue\Tasks\Shortcodes;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use WP_Post;

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
    $newsletter = (new NewsletterFactory())->withSendingQueue()->create();
    $queue = $newsletter->getLatestQueue();
    $subscriber = (new SubscriberFactory())->withFirstName('John')->withLastName('Doe')->create();
    $renderedBody = '[subscriber:firstname] [subscriber:lastname]';
    $result = Shortcodes::process($renderedBody, null, $newsletter, $subscriber, $queue);
    expect($result)->equals('John Doe');
  }

  public function testItCanReplaceShortcodesInOneStringUsingContentsFromAnother() {
    $wpPost = get_post($this->wPPost);
    $this->assertInstanceOf(WP_Post::class, $wpPost);
    $content = 'Subject line with one shortcode: [newsletter:post_title]';
    $contentSource = '<a data-post-id="' . $this->wPPost . '" href="#">latest post</a>';

    // [newsletter:post_title] depends on the "data-post-id" tag and the shortcode will
    // get replaced with an empty string if that tag is not found
    expect(trim(Shortcodes::process($content)))->equals('Subject line with one shortcode:');

    // when tag is found, the shortcode will be processed and replaced
    expect(Shortcodes::process($content, $contentSource))->equals('Subject line with one shortcode: ' . $wpPost->post_title); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function _after() {
    wp_delete_post($this->wPPost, true);
  }
}
