<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Cron\Workers\SendingQueue\Tasks\Posts as PostsTask;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterPostEntity;
use MailPoet\Newsletter\NewsletterPostsRepository;

class PostsTest extends \MailPoetTest {

  /** @var PostsTask */
  private $postsTask;

  public function _before() {
    parent::_before();
    $this->postsTask = new PostsTask;
  }

  public function testItFailsWhenNoPostsArePresent() {
    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY);
    $newsletter->setId(1);
    $renderedNewsletter = [
      'html' => 'Sample newsletter',
    ];
    expect($this->postsTask->extractAndSave($renderedNewsletter, $newsletter))->equals(false);
  }

  public function testItCanExtractAndSavePosts() {
    $parent = new NewsletterEntity();
    $parent->setType(NewsletterEntity::TYPE_NOTIFICATION);
    $parent->setSubject('xx');
    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY);
    $newsletter->setSubject('xx');
    $newsletter->setId(2);
    $newsletter->setParent($parent);
    $this->entityManager->persist($parent);
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();
    $postId = 10;
    $renderedNewsletter = [
      'html' => '<a data-post-id="' . $postId . '" href="#">sample post</a>',
    ];
    expect($this->postsTask->extractAndSave($renderedNewsletter, $newsletter))->equals(true);
    $newsletterPostRepository = ContainerWrapper::getInstance()->get(NewsletterPostsRepository::class);
    $newsletterPost = $newsletterPostRepository->findOneBy(['newsletter' => $parent]);
    expect($newsletterPost)->isInstanceOf(NewsletterPostEntity::class);
    expect($newsletterPost->getPostId())->equals($postId);
  }

  public function testItDoesNotSavePostsWhenNewsletterIsNotANotificationHistory() {
    $postId = 10;

    $parent = new NewsletterEntity();
    $parent->setType(NewsletterEntity::TYPE_WELCOME);
    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_WELCOME);
    $newsletter->setId(2);
    $newsletter->setParent($parent);
    $renderedNewsletter = [
      'html' => '<a data-post-id="' . $postId . '" href="#">sample post</a>',
    ];
    expect($this->postsTask->extractAndSave($renderedNewsletter, $newsletter))->equals(false);
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    expect($this->postsTask->extractAndSave($renderedNewsletter, $newsletter))->equals(false);
  }
}
