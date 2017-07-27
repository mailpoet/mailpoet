<?php
namespace MailPoet\Test\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Cron\Workers\SendingQueue\Tasks\Posts as PostsTask;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterPost;

if(!defined('ABSPATH')) exit;

class PostsTest extends \MailPoetTest {
  function testItFailsWhenNoPostsArePresent() {
    $newsletter = (object)array(
      'id' => 1,
    );
    $rendered_newsletter = array(
      'html' => 'Sample newsletter'
    );
    expect(PostsTask::extractAndSave($rendered_newsletter, $newsletter))->equals(false);
  }

  function testItCanExtractAndSavePosts() {
    $post_id = 10;
    $newsletter = (object)array(
      'id' => 1,
      'type' => Newsletter::TYPE_STANDARD
    );
    $rendered_newsletter = array(
      'html' => '<a data-post-id="' . $post_id . '" href="#">sample post</a>'
    );
    expect(PostsTask::extractAndSave($rendered_newsletter, $newsletter))->equals(true);
    $newsletter_post = NewsletterPost::where('newsletter_id', $newsletter->id)
      ->findOne();
    expect($newsletter_post->post_id)->equals($post_id);
  }

  function testItSetsNewsletterIdToParentIdWhenNewsletterIsANotificationHistory() {
    $post_id = 10;
    $newsletter = (object)array(
      'id' => 2,
      'parent_id' => 1,
      'type' => Newsletter::TYPE_NOTIFICATION_HISTORY
    );
    $rendered_newsletter = array(
      'html' => '<a data-post-id="' . $post_id . '" href="#">sample post</a>'
    );
    expect(PostsTask::extractAndSave($rendered_newsletter, $newsletter))->equals(true);
    $newsletter_post = NewsletterPost::where('newsletter_id', $newsletter->parent_id)
      ->findOne();
    expect($newsletter_post->post_id)->equals($post_id);
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . NewsletterPost::$_table);
  }
}