<?php declare(strict_types = 1);

namespace MailPoet\Test\Doctrine\EventListeners;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Doctrine\EventListeners\NewsletterListener;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\WpPostEntity;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Doctrine\ORM\Events;

require_once __DIR__ . '/EventListenersBaseTest.php';

class NewsletterListenerTest extends EventListenersBaseTest {
  private WPFunctions $wp;

  public function _before() {
    parent::_before();

    $this->wp = $this->diContainer->get(WPFunctions::class);
    $newsletterListener = new NewsletterListener($this->wp);
    $originalListener = $this->diContainer->get(NewsletterListener::class);
    $this->replaceListeners($originalListener, $newsletterListener, [Events::preUpdate]);
  }

  public function testItUpdatesPost() {
    $postId = $this->wp->wpInsertPost(['post_title' => 'Post 1', 'post_status' => 'draft']);
    $entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);

    $newsletter = new NewsletterEntity();
    $this->entityManager->persist($newsletter);
    $newsletter->setId(2);
    $newsletter->setWpPost($entityManager->getReference(WpPostEntity::class, $postId));
    $newsletter->setStatus(NewsletterEntity::STATUS_DRAFT);
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setSubject('Test');
    $this->entityManager->flush();

    $newsletter->setStatus(NewsletterEntity::STATUS_SENT);
    $this->entityManager->flush();

    $post = $this->wp->getPost($postId);
    verify($post)->instanceOf(\WP_Post::class);
    verify($post->post_status)->equals(NewsletterEntity::STATUS_SENT);// @phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }
}
