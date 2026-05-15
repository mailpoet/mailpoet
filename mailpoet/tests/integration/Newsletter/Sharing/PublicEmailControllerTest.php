<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter\Sharing;

use InvalidArgumentException;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Newsletter\Links\Links;
use MailPoet\Newsletter\Sharing\PublicEmailController;
use MailPoet\Newsletter\Sharing\ShareVisibility;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Router\Router;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\NewsletterLink;

class PublicEmailControllerTest extends \MailPoetTest {
  public function testItRendersPublicEmailByNewsletterId() {
    $newsletter = (new Newsletter())
      ->withSentStatus()
      ->withSendingQueue()
      ->withOptions([
        NewsletterOptionFieldEntity::NAME_SHARE_VISIBILITY => ShareVisibility::VISIBILITY_PUBLIC,
      ])
      ->create();
    $queue = $newsletter->getLatestQueue();
    $this->assertNotNull($queue);
    $queue->setNewsletterRenderedBody([
      'html' => '<html><head></head><body>Hello, [subscriber:firstname | default:reader]. '
        . '<a href="' . Links::DATA_TAG_CLICK . '-abcde">Google</a>'
        . '<img src="' . Links::DATA_TAG_OPEN . '" /></body></html>',
      'text' => 'test',
    ]);
    (new NewsletterLink($newsletter))
      ->withUrl('https://example.com')
      ->withHash('abcde')
      ->create();
    $this->entityManager->flush();

    $controller = $this->diContainer->get(PublicEmailController::class);
    $identifier = $this->diContainer->get(NewsletterUrl::class)->getPublicShareIdentifier($newsletter);
    $result = $controller->render($controller->getNewsletter($identifier));

    verify($result)->stringContainsString('Hello, reader');
    verify($result)->stringContainsString('<meta property="og:title"');
    verify($result)->stringContainsString('<a href="https://example.com">');
    verify($result)->stringNotContainsString(Router::NAME . '&endpoint=track');
    verify($result)->stringNotContainsString('[mailpoet_open_data]');
  }

  public function testItRendersFromCurrentEntityWhenNoCompletedQueueIsStored() {
    $newsletter = (new Newsletter())
      ->withSubject('Resilient Share')
      ->withSentStatus()
      ->withOptions([
        NewsletterOptionFieldEntity::NAME_SHARE_VISIBILITY => ShareVisibility::VISIBILITY_PUBLIC,
      ])
      ->create();

    $controller = $this->diContainer->get(PublicEmailController::class);
    $result = $controller->render($newsletter);

    verify($result)->stringContainsString('<meta property="og:title" content="Resilient Share" />');
  }

  public function testItRejectsPrivatePublicEmails() {
    $newsletter = (new Newsletter())
      ->withSentStatus()
      ->withOptions([
        NewsletterOptionFieldEntity::NAME_SHARE_VISIBILITY => ShareVisibility::VISIBILITY_PRIVATE,
      ])
      ->create();

    $this->expectException(InvalidArgumentException::class);

    $this->diContainer->get(PublicEmailController::class)
      ->getNewsletter($this->diContainer->get(NewsletterUrl::class)->getPublicShareIdentifier($newsletter));
  }
}
