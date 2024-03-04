<?php declare(strict_types = 1);

namespace MailPoet\Analytics;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\Segment;
use MailPoetVendor\Carbon\Carbon;

class ReporterTest extends \MailPoetTest {
  private Reporter $reporter;

  public function _before() {
    parent::_before();
    $this->reporter = $this->diContainer->get(Reporter::class);
  }

  public function testItWorksWithStandardNewslettersAndStandardSegments(): void {
    $defaultSegment = (new Segment())->withType(SegmentEntity::TYPE_DEFAULT)->create();
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subDays(2), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subDays(8), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subDays(89), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3']]]);
    $processed = $this->reporter->getCampaignAnalyticsProperties();

    $this->assertEquals(1, $processed['Number of standard newsletters sent in last 7 days']);
    $this->assertEquals(2, $processed['Number of standard newsletters sent in last 30 days']);
    $this->assertEquals(3, $processed['Number of standard newsletters sent in last 3 months']);
  }

  public function testItWorksWithStandardNewslettersAndDynamicSegments(): void {
    $dynamicSegment = (new Segment())->withType(SegmentEntity::TYPE_DYNAMIC)->create();
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subDays(2), [$dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subDays(8), [$dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subDays(89), [$dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3']]]);
    $processed = $this->reporter->getCampaignAnalyticsProperties();

    $this->assertEquals(1, $processed['Number of standard newsletters sent in last 7 days']);
    $this->assertEquals(2, $processed['Number of standard newsletters sent in last 30 days']);
    $this->assertEquals(3, $processed['Number of standard newsletters sent in last 3 months']);
    $this->assertEquals(1, $processed['Number of standard newsletters sent to segment in last 7 days']);
    $this->assertEquals(2, $processed['Number of standard newsletters sent to segment in last 30 days']);
    $this->assertEquals(3, $processed['Number of standard newsletters sent to segment in last 3 months']);
  }

  public function testItWorksWithStandardNewslettersAndFilterSegments(): void {
    $defaultSegment = (new Segment())->withType(SegmentEntity::TYPE_DEFAULT)->create();
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subDays(2), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1', 'filterSegment' => ['theDataDoesNot' => 'matter']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subDays(8), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2', 'filterSegment' => ['theDataDoesNot' => 'matter']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subDays(89), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3', 'filterSegment' => ['theDataDoesNot' => 'matter']]]]);
    $processed = $this->reporter->getCampaignAnalyticsProperties();

    $this->assertEquals(1, $processed['Number of standard newsletters sent in last 7 days']);
    $this->assertEquals(2, $processed['Number of standard newsletters sent in last 30 days']);
    $this->assertEquals(3, $processed['Number of standard newsletters sent in last 3 months']);
    $this->assertEquals(1, $processed['Number of standard newsletters filtered by segment in last 7 days']);
    $this->assertEquals(2, $processed['Number of standard newsletters filtered by segment in last 30 days']);
    $this->assertEquals(3, $processed['Number of standard newsletters filtered by segment in last 3 months']);
    $this->assertEquals(0, $processed['Number of standard newsletters sent to segment in last 7 days']);
    $this->assertEquals(0, $processed['Number of standard newsletters sent to segment in last 30 days']);
    $this->assertEquals(0, $processed['Number of standard newsletters sent to segment in last 3 months']);
  }

  public function testItWorksWithNotificationHistoryNewsletters(): void {
    $defaultSegment = (new Segment())->withType(SegmentEntity::TYPE_DEFAULT)->create();
    $this->createSentNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, Carbon::now()->subDays(2), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, Carbon::now()->subDays(8), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, Carbon::now()->subDays(89), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3']]]);
    $processed = $this->reporter->getCampaignAnalyticsProperties();

    $this->assertEquals(1, $processed['Number of post notification campaigns sent in the last 7 days']);
    $this->assertEquals(2, $processed['Number of post notification campaigns sent in the last 30 days']);
    $this->assertEquals(3, $processed['Number of post notification campaigns sent in the last 3 months']);
  }

  public function testItWorksWithNotificationHistoryNewslettersSentToSegments(): void {
    $dynamicSegment = (new Segment())->withType(SegmentEntity::TYPE_DYNAMIC)->create();
    $this->createSentNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, Carbon::now()->subDays(2), [$dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, Carbon::now()->subDays(8), [$dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, Carbon::now()->subDays(89), [$dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3']]]);
    $processed = $this->reporter->getCampaignAnalyticsProperties();

    $this->assertEquals(1, $processed['Number of post notification campaigns sent in the last 7 days']);
    $this->assertEquals(2, $processed['Number of post notification campaigns sent in the last 30 days']);
    $this->assertEquals(3, $processed['Number of post notification campaigns sent in the last 3 months']);
    $this->assertEquals(1, $processed['Number of post notification campaigns sent to segment in the last 7 days']);
    $this->assertEquals(2, $processed['Number of post notification campaigns sent to segment in the last 30 days']);
    $this->assertEquals(3, $processed['Number of post notification campaigns sent to segment in the last 3 months']);
  }

  public function testItWorksWithNotificationHistoryNewslettersFilteredBySegment(): void {
    $defaultSegment = (new Segment())->withType(SegmentEntity::TYPE_DEFAULT)->create();
    $this->createSentNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, Carbon::now()->subDays(2), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1', 'filterSegment' => ['not' => 'relevant']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, Carbon::now()->subDays(8), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2', 'filterSegment' => ['not' => 'relevant']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, Carbon::now()->subDays(89), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3', 'filterSegment' => ['not' => 'relevant']]]]);
    $processed = $this->reporter->getCampaignAnalyticsProperties();

    $this->assertEquals(1, $processed['Number of post notification campaigns sent in the last 7 days']);
    $this->assertEquals(2, $processed['Number of post notification campaigns sent in the last 30 days']);
    $this->assertEquals(3, $processed['Number of post notification campaigns sent in the last 3 months']);
    $this->assertEquals(1, $processed['Number of post notification campaigns filtered by segment in the last 7 days']);
    $this->assertEquals(2, $processed['Number of post notification campaigns filtered by segment in the last 30 days']);
    $this->assertEquals(3, $processed['Number of post notification campaigns filtered by segment in the last 3 months']);
    $this->assertEquals(0, $processed['Number of post notification campaigns sent to segment in the last 7 days']);
    $this->assertEquals(0, $processed['Number of post notification campaigns sent to segment in the last 30 days']);
    $this->assertEquals(0, $processed['Number of post notification campaigns sent to segment in the last 3 months']);
  }

  public function testItWorksWithReEngagementEmails(): void {
    $defaultSegment = (new Segment())->withType(SegmentEntity::TYPE_DEFAULT)->create();
    $this->createSentNewsletter(NewsletterEntity::TYPE_RE_ENGAGEMENT, Carbon::now()->subDays(2), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_RE_ENGAGEMENT, Carbon::now()->subDays(8), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_RE_ENGAGEMENT, Carbon::now()->subDays(89), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3']]]);
    $processed = $this->reporter->getCampaignAnalyticsProperties();

    $this->assertEquals(1, $processed['Number of re-engagement campaigns sent in the last 7 days']);
    $this->assertEquals(2, $processed['Number of re-engagement campaigns sent in the last 30 days']);
    $this->assertEquals(3, $processed['Number of re-engagement campaigns sent in the last 3 months']);
  }

  public function testItWorksWithReEngagementEmailsSentToSegment(): void {
    $dynamicSegment = (new Segment())->withType(SegmentEntity::TYPE_DYNAMIC)->create();
    $this->createSentNewsletter(NewsletterEntity::TYPE_RE_ENGAGEMENT, Carbon::now()->subDays(2), [$dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_RE_ENGAGEMENT, Carbon::now()->subDays(8), [$dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_RE_ENGAGEMENT, Carbon::now()->subDays(89), [$dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3']]]);
    $processed = $this->reporter->getCampaignAnalyticsProperties();

    $this->assertEquals(1, $processed['Number of re-engagement campaigns sent in the last 7 days']);
    $this->assertEquals(2, $processed['Number of re-engagement campaigns sent in the last 30 days']);
    $this->assertEquals(3, $processed['Number of re-engagement campaigns sent in the last 3 months']);
    $this->assertEquals(1, $processed['Number of re-engagement campaigns sent to segment in the last 7 days']);
    $this->assertEquals(2, $processed['Number of re-engagement campaigns sent to segment in the last 30 days']);
    $this->assertEquals(3, $processed['Number of re-engagement campaigns sent to segment in the last 3 months']);
  }

  public function testItWorksWithReEngagementEmailsFilteredBySegment(): void {
    $dynamicSegment = (new Segment())->withType(SegmentEntity::TYPE_DEFAULT)->create();
    $this->createSentNewsletter(NewsletterEntity::TYPE_RE_ENGAGEMENT, Carbon::now()->subDays(2), [$dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1', 'filterSegment' => ['theDataDoesNot' => 'matter']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_RE_ENGAGEMENT, Carbon::now()->subDays(8), [$dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2', 'filterSegment' => ['theDataDoesNot' => 'matter']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_RE_ENGAGEMENT, Carbon::now()->subDays(89), [$dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3', 'filterSegment' => ['theDataDoesNot' => 'matter']]]]);
    $processed = $this->reporter->getCampaignAnalyticsProperties();

    $this->assertEquals(1, $processed['Number of re-engagement campaigns sent in the last 7 days']);
    $this->assertEquals(2, $processed['Number of re-engagement campaigns sent in the last 30 days']);
    $this->assertEquals(3, $processed['Number of re-engagement campaigns sent in the last 3 months']);
    $this->assertEquals(1, $processed['Number of re-engagement campaigns filtered by segment in the last 7 days']);
    $this->assertEquals(2, $processed['Number of re-engagement campaigns filtered by segment in the last 30 days']);
    $this->assertEquals(3, $processed['Number of re-engagement campaigns filtered by segment in the last 3 months']);
    $this->assertEquals(0, $processed['Number of re-engagement campaigns sent to segment in the last 7 days']);
    $this->assertEquals(0, $processed['Number of re-engagement campaigns sent to segment in the last 30 days']);
    $this->assertEquals(0, $processed['Number of re-engagement campaigns sent to segment in the last 3 months']);
  }

  public function testItWorksWithLegacyWelcomeEmails(): void {
    $this->createSentNewsletter(NewsletterEntity::TYPE_WELCOME, Carbon::now()->subDays(2), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_WELCOME, Carbon::now()->subDays(8), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_WELCOME, Carbon::now()->subDays(89), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3']]]);
    $processed = $this->reporter->getCampaignAnalyticsProperties();
    $this->assertSame(1, $processed['Number of legacy welcome email campaigns sent in the last 7 days']);
    $this->assertSame(2, $processed['Number of legacy welcome email campaigns sent in the last 30 days']);
    $this->assertSame(3, $processed['Number of legacy welcome email campaigns sent in the last 3 months']);
  }

  public function testItWorksWithLegacyAbandonedCartEmails(): void {
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATIC, Carbon::now()->subDays(2), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1', 'cart_product_ids' => ['123']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATIC, Carbon::now()->subDays(8), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2', 'cart_product_ids' => ['1234']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATIC, Carbon::now()->subDays(89), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3', 'cart_product_ids' => ['1235']]]]);
    $processed = $this->reporter->getCampaignAnalyticsProperties();
    $this->assertSame(1, $processed['Number of legacy abandoned cart campaigns sent in the last 7 days']);
    $this->assertSame(2, $processed['Number of legacy abandoned cart campaigns sent in the last 30 days']);
    $this->assertSame(3, $processed['Number of legacy abandoned cart campaigns sent in the last 3 months']);
  }

  public function testItWorksWithLegacyPurchasedProductEmails(): void {
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATIC, Carbon::now()->subDays(2), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1', 'orderedProducts' => ['123']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATIC, Carbon::now()->subDays(8), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2', 'orderedProducts' => ['1234']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATIC, Carbon::now()->subDays(89), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3', 'orderedProducts' => ['1235']]]]);
    $processed = $this->reporter->getCampaignAnalyticsProperties();
    $this->assertSame(1, $processed['Number of legacy purchased product campaigns sent in the last 7 days']);
    $this->assertSame(2, $processed['Number of legacy purchased product campaigns sent in the last 30 days']);
    $this->assertSame(3, $processed['Number of legacy purchased product campaigns sent in the last 3 months']);
  }

  public function testItWorksWithLegacyPurchasedInCategoryEmails(): void {
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATIC, Carbon::now()->subDays(2), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1', 'orderedProductCategories' => ['123']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATIC, Carbon::now()->subDays(8), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2', 'orderedProductCategories' => ['1234']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATIC, Carbon::now()->subDays(89), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3', 'orderedProductCategories' => ['1235']]]]);
    $processed = $this->reporter->getCampaignAnalyticsProperties();
    $this->assertSame(1, $processed['Number of legacy purchased in category campaigns sent in the last 7 days']);
    $this->assertSame(2, $processed['Number of legacy purchased in category campaigns sent in the last 30 days']);
    $this->assertSame(3, $processed['Number of legacy purchased in category campaigns sent in the last 3 months']);
  }

  public function testItWorksWithLegacyFirstPurchaseEmails(): void {
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATIC, Carbon::now()->subDays(2), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1', 'order_amount' => 123, 'order_date' => '2024-03-01', 'order_id' => '1']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATIC, Carbon::now()->subDays(8), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2', 'order_amount' => 123, 'order_date' => '2024-03-01', 'order_id' => '2']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATIC, Carbon::now()->subDays(89), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3', 'order_amount' => 123, 'order_date' => '2024-03-01', 'order_id' => '3']]]);
    $processed = $this->reporter->getCampaignAnalyticsProperties();
    $this->assertSame(1, $processed['Number of legacy first purchase campaigns sent in the last 7 days']);
    $this->assertSame(2, $processed['Number of legacy first purchase campaigns sent in the last 30 days']);
    $this->assertSame(3, $processed['Number of legacy first purchase campaigns sent in the last 3 months']);
  }

  public function testItWorksForAutomationEmails(): void {
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATION, Carbon::now()->subDays(2), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1', 'orderedProductCategories' => ['123']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATION, Carbon::now()->subDays(8), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2', 'orderedProductCategories' => ['1234']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_AUTOMATION, Carbon::now()->subDays(89), [], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3', 'orderedProductCategories' => ['1235']]]]);

    $processed = $this->reporter->getCampaignAnalyticsProperties();

    $this->assertSame(1, $processed['Number of automations campaigns sent in the last 7 days']);
    $this->assertSame(2, $processed['Number of automations campaigns sent in the last 30 days']);
    $this->assertSame(3, $processed['Number of automations campaigns sent in the last 3 months']);
  }

  public function testItReportsSentCampaignTotals(): void {
    $defaultSegment = (new Segment())->withType(SegmentEntity::TYPE_DEFAULT)->create();
    $dynamicSegment = (new Segment())->withType(SegmentEntity::TYPE_DYNAMIC)->create();
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subDays(2), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subDays(8), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '2']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subDays(89), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '3']]]);

    $this->createSentNewsletter(NewsletterEntity::TYPE_RE_ENGAGEMENT, Carbon::now()->subDays(2), [$defaultSegment, $dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '4']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_RE_ENGAGEMENT, Carbon::now()->subDays(8), [$defaultSegment, $dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '5']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_RE_ENGAGEMENT, Carbon::now()->subDays(89), [$defaultSegment, $dynamicSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '6']]]);

    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subDays(2), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '7', 'filterSegment' => ['theDataDoesNot' => 'matter']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subDays(8), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '8', 'filterSegment' => ['theDataDoesNot' => 'matter']]]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_STANDARD, Carbon::now()->subDays(89), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '9', 'filterSegment' => ['theDataDoesNot' => 'matter']]]]);

    $processed = $this->reporter->getCampaignAnalyticsProperties();
    $this->assertEquals(3, $processed['Number of campaigns sent in the last 7 days']);
    $this->assertEquals(6, $processed['Number of campaigns sent in the last 30 days']);
    $this->assertEquals(9, $processed['Number of campaigns sent in the last 3 months']);

    $this->assertEquals(1, $processed['Number of campaigns sent to segment in the last 7 days']);
    $this->assertEquals(2, $processed['Number of campaigns sent to segment in the last 30 days']);
    $this->assertEquals(3, $processed['Number of campaigns sent to segment in the last 3 months']);

    $this->assertEquals(1, $processed['Number of campaigns filtered by segment in the last 7 days']);
    $this->assertEquals(2, $processed['Number of campaigns filtered by segment in the last 30 days']);
    $this->assertEquals(3, $processed['Number of campaigns filtered by segment in the last 3 months']);
  }

  public function testItDoesNotDoubleCountDuplicateCampaignIds(): void {
    $defaultSegment = (new Segment())->withType(SegmentEntity::TYPE_DEFAULT)->create();
    $this->createSentNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, Carbon::now()->subDays(89), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, Carbon::now()->subDays(8), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1']]]);
    $this->createSentNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, Carbon::now()->subDays(2), [$defaultSegment], ['sendingQueueOptions' => ['meta' => ['campaignId' => '1']]]);
    $processed = $this->reporter->getCampaignAnalyticsProperties();

    $this->assertEquals(1, $processed['Number of post notification campaigns sent in the last 7 days']);
    $this->assertEquals(1, $processed['Number of post notification campaigns sent in the last 30 days']);
    $this->assertEquals(1, $processed['Number of post notification campaigns sent in the last 3 months']);
  }

  private function createSentNewsletter(string $type, Carbon $sentAt, array $segments, array $otherOptions = []): void {
    $sendingQueueOptions = ['processed_at' => $sentAt];

    $extraSendingQueueOptions = $otherOptions['sendingQueueOptions'] ?? null;

    if (is_array($extraSendingQueueOptions)) {
      $sendingQueueOptions = array_merge($sendingQueueOptions, $extraSendingQueueOptions);
    }

    (new NewsletterFactory())
      ->withType($type)
      ->withSegments($segments)
      ->withSendingQueue($sendingQueueOptions)
      ->withStatus(NewsletterEntity::STATUS_SENT)
      ->create();
  }
}
