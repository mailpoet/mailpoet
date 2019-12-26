<?php

namespace MailPoet\Test\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Cron\Workers\SendingQueue\Tasks\Posts as PostsTask;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterPost;
use MailPoetVendor\Idiorm\ORM;

class PostsTest extends \MailPoetTest {

  /** @var PostsTask */
  private $posts_task;

  public function _before() {
    parent::_before();
    $this->posts_task = new PostsTask;
  }

  public function testItFailsWhenNoPostsArePresent() {
    $newsletter = (object)[
      'id' => 1,
      'type' => Newsletter::TYPE_NOTIFICATION_HISTORY,
    ];
    $rendered_newsletter = [
      'html' => 'Sample newsletter',
    ];
    expect($this->posts_task->extractAndSave($rendered_newsletter, $newsletter))->equals(false);
  }

  public function testItCanExtractAndSavePosts() {
    $post_id = 10;
    $newsletter = (object)[
      'id' => 2,
      'parent_id' => 1,
      'type' => Newsletter::TYPE_NOTIFICATION_HISTORY,
    ];
    $rendered_newsletter = [
      'html' => '<a data-post-id="' . $post_id . '" href="#">sample post</a>',
    ];
    expect($this->posts_task->extractAndSave($rendered_newsletter, $newsletter))->equals(true);
    $newsletter_post = NewsletterPost::where('newsletter_id', $newsletter->parent_id)
      ->findOne();
    expect($newsletter_post->post_id)->equals($post_id);
  }

  public function testItDoesNotSavePostsWhenNewsletterIsNotANotificationHistory() {
    $post_id = 10;
    $newsletter = (object)[
      'id' => 2,
      'parent_id' => 1,
      'type' => Newsletter::TYPE_WELCOME,
    ];
    $rendered_newsletter = [
      'html' => '<a data-post-id="' . $post_id . '" href="#">sample post</a>',
    ];
    expect($this->posts_task->extractAndSave($rendered_newsletter, $newsletter))->equals(false);
    $newsletter->type = Newsletter::TYPE_STANDARD;
    expect($this->posts_task->extractAndSave($rendered_newsletter, $newsletter))->equals(false);
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . NewsletterPost::$_table);
  }
}
