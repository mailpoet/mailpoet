<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Embed;

use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Router\Router;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoetVendor\Carbon\Carbon;

class NewsletterEmbedServiceTest extends \MailPoetTest {
  /** @var NewsletterEmbedService */
  private $service;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var NewsletterUrl */
  private $newsletterUrl;

  public function _before() {
    parent::_before();
    $this->service = $this->diContainer->get(NewsletterEmbedService::class);
    $this->sendingQueuesRepository = $this->diContainer->get(SendingQueuesRepository::class);
    $this->newsletterUrl = $this->diContainer->get(NewsletterUrl::class);
  }

  public function testItRendersIframeWithLatestCompletedQueueAndFallbackLink(): void {
    $newsletter = (new NewsletterFactory())
      ->withSubject('Spring <Sale>')
      ->withSentStatus()
      ->withSendingQueue(['processed_at' => new Carbon('2024-01-01 10:00:00')])
      ->withScheduledQueue(['processed_at' => new Carbon('2025-01-01 10:00:00')])
      ->withSendingQueue(['processed_at' => new Carbon('2024-03-01 10:00:00')])
      ->create();
    $latestCompletedQueue = $this->sendingQueuesRepository->findLatestCompletedByNewsletter($newsletter);
    $this->assertInstanceOf(SendingQueueEntity::class, $latestCompletedQueue);

    $html = $this->service->render([
      'newsletterId' => $newsletter->getId(),
      'height' => 600,
      'showFallbackLink' => true,
      'align' => 'wide',
    ]);

    $this->assertStringContainsString('class="mailpoet-newsletter-embed alignwide"', $html);
    $this->assertStringContainsString('height="600"', $html);
    $this->assertStringContainsString('MailPoet newsletter: Spring &lt;Sale&gt;', $html);
    $this->assertStringContainsString('View newsletter in browser', $html);

    $iframeUrl = $this->getIframeUrl($html);
    $fallbackUrl = $this->getFallbackUrl($html);
    $this->assertSame($iframeUrl, $fallbackUrl);
    $this->assertSame($latestCompletedQueue->getId(), $this->getQueueIdFromUrl($iframeUrl));
  }

  public function testItCanRenderWithoutFallbackLink(): void {
    $newsletter = (new NewsletterFactory())
      ->withSentStatus()
      ->withSendingQueue()
      ->create();

    $html = $this->service->render([
      'newsletterId' => $newsletter->getId(),
      'showFallbackLink' => false,
    ]);

    $this->assertStringContainsString('<iframe', $html);
    $this->assertStringNotContainsString('View newsletter in browser', $html);
  }

  public function testItReturnsEmptyForIneligibleNewsletters(): void {
    $eligibleNotificationHistory = (new NewsletterFactory())
      ->withPostNotificationHistoryType()
      ->withSentStatus()
      ->withSendingQueue()
      ->create();

    $draft = (new NewsletterFactory())
      ->withDraftStatus()
      ->withSendingQueue()
      ->create();
    $welcome = (new NewsletterFactory())
      ->withWelcomeTypeForSegment()
      ->withSentStatus()
      ->withSendingQueue()
      ->create();
    $deleted = (new NewsletterFactory())
      ->withDeleted()
      ->withSentStatus()
      ->withSendingQueue()
      ->create();
    $withoutCompletedQueue = (new NewsletterFactory())
      ->withSentStatus()
      ->withScheduledQueue()
      ->create();
    $withoutQueue = (new NewsletterFactory())
      ->withSentStatus()
      ->create();

    $this->assertStringContainsString('<iframe', $this->service->render(['newsletterId' => $eligibleNotificationHistory->getId()]));
    $this->assertSame('', $this->service->render(['newsletterId' => $draft->getId()]));
    $this->assertSame('', $this->service->render(['newsletterId' => $welcome->getId()]));
    $this->assertSame('', $this->service->render(['newsletterId' => $deleted->getId()]));
    $this->assertSame('', $this->service->render(['newsletterId' => $withoutCompletedQueue->getId()]));
    $this->assertSame('', $this->service->render(['newsletterId' => $withoutQueue->getId()]));
    $this->assertSame('', $this->service->render(['newsletterId' => 0]));
  }

  public function testItSelectsLatestCompletedQueueDeterministically(): void {
    $processedAt = new Carbon('2024-03-01 10:00:00');
    $newsletter = (new NewsletterFactory())
      ->withSentStatus()
      ->withSendingQueue(['processed_at' => $processedAt])
      ->withSendingQueue(['processed_at' => $processedAt])
      ->withSendingQueue(['processed_at' => $processedAt])
      ->create();

    $queueIds = array_map(function(SendingQueueEntity $queue): int {
      return (int)$queue->getId();
    }, $newsletter->getQueues()->toArray());
    $this->assertNotEmpty($queueIds);
    $expectedQueueId = max($queueIds);

    $latestCompletedQueue = $this->sendingQueuesRepository->findLatestCompletedByNewsletter($newsletter);
    $this->assertInstanceOf(SendingQueueEntity::class, $latestCompletedQueue);
    $this->assertSame($expectedQueueId, (int)$latestCompletedQueue->getId());
  }

  public function testItReturnsSelectorItemsSortedAndFiltered(): void {
    $old = (new NewsletterFactory())
      ->withSubject('Old campaign')
      ->withSentStatus()
      ->withSendingQueue(['processed_at' => new Carbon('2024-01-01 10:00:00')])
      ->create();
    $new = (new NewsletterFactory())
      ->withSubject('New campaign')
      ->withSentStatus()
      ->withWpPostId(123)
      ->withSendingQueue(['processed_at' => new Carbon('2024-02-01 10:00:00')])
      ->create();
    (new NewsletterFactory())
      ->withSubject('Draft campaign')
      ->withDraftStatus()
      ->withSendingQueue(['processed_at' => new Carbon('2024-03-01 10:00:00')])
      ->create();

    $items = $this->service->getSelectorItems('', 10);
    $this->assertGreaterThanOrEqual(2, count($items));
    $ids = array_column($items, 'id');
    $this->assertContains($new->getId(), $ids);
    $oldIndex = array_search($old->getId(), $ids, true);
    $newIndex = array_search($new->getId(), $ids, true);
    $this->assertIsInt($oldIndex);
    $this->assertIsInt($newIndex);
    $this->assertLessThan($oldIndex, $newIndex);
    $this->assertArrayHasKey('wpPostId', $items[$newIndex]);
    $this->assertSame(123, $items[$newIndex]['wpPostId']);

    foreach ($items as $item) {
      $this->assertArrayNotHasKey('url', $item);
      $this->assertArrayNotHasKey('body', $item);
    }

    $searched = $this->service->getSelectorItems('New', 10);
    $this->assertCount(1, $searched);
    $this->assertSame($new->getId(), $searched[0]['id']);

  }

  private function getIframeUrl(string $html): string {
    $matches = [];
    $this->assertSame(1, preg_match('/<iframe[^>]+src="([^"]+)"/', $html, $matches));
    return html_entity_decode($matches[1]);
  }

  private function getFallbackUrl(string $html): string {
    $matches = [];
    $this->assertSame(1, preg_match('/<a[^>]+href="([^"]+)"/', $html, $matches));
    return html_entity_decode($matches[1]);
  }

  private function getQueueIdFromUrl(string $url): int {
    $parsedLink = parse_url($url, PHP_URL_QUERY);
    parse_str((string)$parsedLink, $data);
    $this->assertArrayHasKey('data', $data);
    $requestData = $this->newsletterUrl->transformUrlDataObject(
      Router::decodeRequestData($data['data'])
    );
    return (int)$requestData['queue_id'];
  }
}
