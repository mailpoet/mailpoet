<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\v1;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;

class NewsletterLinksTest extends \MailPoetTest {
  /** @var NewsletterLinks */
  private $endpoint;

  public function _before() {
    parent::_before();
    $this->endpoint = $this->diContainer->get(NewsletterLinks::class);
  }

  public function testItReturnsStoredLinksForStandardNewsletters(): void {
    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD, NewsletterEntity::STATUS_SENT);
    $queue = $this->createQueue($newsletter);
    $link = $this->createLink($newsletter, $queue, 'https://example.com/standard');

    $response = $this->endpoint->get(['newsletterId' => $newsletter->getId()]);

    verify($response->data)->equals([
      [
        'id' => $link->getId(),
        'url' => 'https://example.com/standard',
      ],
    ]);
  }

  public function testItReturnsUniqueAutomationLinksFromStoredRunsAndBody(): void {
    $newsletter = $this->createNewsletter(
      NewsletterEntity::TYPE_AUTOMATION,
      NewsletterEntity::STATUS_ACTIVE,
      [
        'content' => [
          'blocks' => [
            [
              'type' => 'text',
              'text' => '<a href="https://example.com/body">Body link</a> <a href="[link:subscription_manage_url]">Manage</a>',
            ],
            [
              'type' => 'button',
              'url' => 'https://example.com/button',
            ],
            [
              'type' => 'button',
              'url' => '[postLink]',
            ],
            [
              'type' => 'image',
              'link' => 'https://example.com/image',
            ],
            [
              'type' => 'social',
              'icons' => [
                [
                  'link' => 'https://example.com/social',
                ],
              ],
            ],
          ],
        ],
      ]
    );
    $queue1 = $this->createQueue($newsletter);
    $queue2 = $this->createQueue($newsletter);
    $this->createLink($newsletter, $queue1, 'https://example.com/stored');
    $this->createLink($newsletter, $queue2, 'https://example.com/stored');

    $response = $this->endpoint->get(['newsletterId' => $newsletter->getId()]);
    $urls = array_column($response->data, 'url');
    $ids = array_column($response->data, 'id');

    $this->assertContains('https://example.com/stored', $urls);
    $this->assertContains('https://example.com/body', $urls);
    $this->assertContains('[link:subscription_manage_url]', $urls);
    $this->assertContains('https://example.com/button', $urls);
    $this->assertContains('https://example.com/image', $urls);
    $this->assertContains('https://example.com/social', $urls);
    $this->assertNotContains('[postLink]', $urls);
    $this->assertCount(count(array_unique($urls)), $urls);
    verify($ids)->equals($urls);
  }

  private function createNewsletter(string $type, string $status, array $body = []): NewsletterEntity {
    $newsletter = new NewsletterEntity();
    $newsletter->setType($type);
    $newsletter->setStatus($status);
    $newsletter->setSubject('Newsletter Links Test');
    $newsletter->setBody($body);
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();
    return $newsletter;
  }

  private function createQueue(NewsletterEntity $newsletter): SendingQueueEntity {
    $task = new ScheduledTaskEntity();
    $this->entityManager->persist($task);

    $queue = new SendingQueueEntity();
    $queue->setNewsletter($newsletter);
    $queue->setTask($task);
    $this->entityManager->persist($queue);
    $newsletter->getQueues()->add($queue);

    $this->entityManager->flush();
    return $queue;
  }

  private function createLink(NewsletterEntity $newsletter, SendingQueueEntity $queue, string $url): NewsletterLinkEntity {
    $link = new NewsletterLinkEntity($newsletter, $queue, $url, uniqid('', true));
    $this->entityManager->persist($link);
    $this->entityManager->flush();
    return $link;
  }
}
