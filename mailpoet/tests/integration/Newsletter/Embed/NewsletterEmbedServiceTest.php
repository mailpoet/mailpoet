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
      'width' => 700,
      'showFallbackLink' => true,
      'fallbackLinkAlignment' => 'right',
      'iframeAlignment' => 'center',
      'showEmailBackground' => false,
      'align' => 'wide',
    ]);

    $this->assertStringContainsString('class="mailpoet-newsletter-embed alignwide"', $html);
    $this->assertStringContainsString('style="text-align:center;"', $html);
    $this->assertStringContainsString('height="600"', $html);
    $this->assertStringContainsString('width="700"', $html);
    $this->assertStringContainsString('sandbox="allow-same-origin allow-popups allow-popups-to-escape-sandbox"', $html);
    $this->assertStringContainsString('max-width:700px', $html);
    $this->assertStringContainsString('class="mailpoet-newsletter-embed-fallback" style="text-align:right;"', $html);
    $this->assertStringContainsString('MailPoet newsletter: Spring &lt;Sale&gt;', $html);
    $this->assertStringContainsString('View full newsletter', $html);

    $iframeUrl = $this->getIframeUrl($html);
    $fallbackUrl = $this->getFallbackUrl($html);
    $this->assertSame($iframeUrl, $fallbackUrl);
    $this->assertSame($latestCompletedQueue->getId(), $this->getQueueIdFromUrl($iframeUrl));
    $this->assertTrue($this->getUrlData($iframeUrl)['embed_hide_background']);
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
    $this->assertStringNotContainsString('View full newsletter', $html);
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
    $dateFormat = get_option('date_format');
    $timeFormat = get_option('time_format');
    $this->assertIsString($dateFormat);
    $this->assertIsString($timeFormat);
    $this->assertSame(
      'New campaign - ' . date_i18n($dateFormat . ' ' . $timeFormat, (new Carbon('2024-02-01 10:00:00'))->getTimestamp()),
      $items[$newIndex]['label']
    );

    foreach ($items as $item) {
      $this->assertArrayNotHasKey('url', $item);
      $this->assertArrayNotHasKey('body', $item);
    }

    $searched = $this->service->getSelectorItems('New', 10);
    $this->assertCount(1, $searched);
    $this->assertSame($new->getId(), $searched[0]['id']);
  }

  public function testItEscapesSelectorSearchWildcards(): void {
    $target = (new NewsletterFactory())
      ->withSubject('100% matched_campaign')
      ->withSentStatus()
      ->withSendingQueue(['processed_at' => new Carbon('2024-02-01 10:00:00')])
      ->create();
    (new NewsletterFactory())
      ->withSubject('100X matchedAcampaign')
      ->withSentStatus()
      ->withSendingQueue(['processed_at' => new Carbon('2024-02-02 10:00:00')])
      ->create();

    $items = $this->service->getSelectorItems('100% matched_', 10);

    $this->assertCount(1, $items);
    $this->assertSame($target->getId(), $items[0]['id']);
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
    return (int)$this->getUrlData($url)['queue_id'];
  }

  private function getUrlData(string $url): array {
    $parsedLink = parse_url($url, PHP_URL_QUERY);
    parse_str((string)$parsedLink, $data);
    $this->assertArrayHasKey('data', $data);
    return $this->newsletterUrl->transformUrlDataObject(
      Router::decodeRequestData($data['data'])
    );
  }
}
