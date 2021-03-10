<?php

namespace MailPoet\Test\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Cron\Workers\SendingQueue\Tasks\Posts as PostsTask;
use MailPoet\Entities\NewsletterPostEntity;
use MailPoet\Models\Newsletter;

class PostsTest extends \MailPoetTest {

  /** @var PostsTask */
  private $postsTask;

  public function _before() {
    parent::_before();
    $this->postsTask = new PostsTask;
  }

  public function testItFailsWhenNoPostsArePresent() {
    $newsletter = (object)[
      'id' => 1,
      'type' => Newsletter::TYPE_NOTIFICATION_HISTORY,
    ];
    $renderedNewsletter = [
      'html' => 'Sample newsletter',
    ];
    expect($this->postsTask->extractAndSave($renderedNewsletter, $newsletter))->equals(false);
  }

  public function testItCanExtractAndSavePosts() {
    $postId = 10;
    $newsletter = (object)[
      'id' => 2,
      'parentId' => 1,
      'type' => Newsletter::TYPE_NOTIFICATION_HISTORY,
    ];
    $renderedNewsletter = [
      'html' => '<a data-post-id="' . $postId . '" href="#">sample post</a>',
    ];
    expect($this->postsTask->extractAndSave($renderedNewsletter, $newsletter))->equals(true);
    $newsletterPost = NewsletterPost::where('newsletter_id', $newsletter->parentId)
      ->findOne();
    assert($newsletterPost instanceof NewsletterPost);
    expect($newsletterPost->postId)->equals($postId);
  }

  public function testItDoesNotSavePostsWhenNewsletterIsNotANotificationHistory() {
    $postId = 10;
    $newsletter = (object)[
      'id' => 2,
      'parentId' => 1,
      'type' => Newsletter::TYPE_WELCOME,
    ];
    $renderedNewsletter = [
      'html' => '<a data-post-id="' . $postId . '" href="#">sample post</a>',
    ];
    expect($this->postsTask->extractAndSave($renderedNewsletter, $newsletter))->equals(false);
    $newsletter->type = Newsletter::TYPE_STANDARD;
    expect($this->postsTask->extractAndSave($renderedNewsletter, $newsletter))->equals(false);
  }

  public function _after() {
    $this->truncateEntity(NewsletterPostEntity::class);
  }
}
