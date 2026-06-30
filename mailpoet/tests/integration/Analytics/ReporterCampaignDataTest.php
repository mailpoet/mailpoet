<?php declare(strict_types = 1);

namespace MailPoet\Analytics;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\Sending\NewsletterReplayMetadata;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoetVendor\Carbon\Carbon;

class ReporterCampaignDataTest extends \MailPoetTest {
  private ReporterCampaignData $reporter;

  public function _before() {
    parent::_before();
    $this->reporter = $this->diContainer->get(ReporterCampaignData::class);
  }

  public function testItReportsReplayQueuesAsAutomationCampaigns(): void {
    $now = Carbon::now();
    $replayMeta = [
      NewsletterReplayMetadata::LATEST_NEWSLETTER_REPLAY => true,
      NewsletterReplayMetadata::AUTOMATION => ['id' => 42],
      // A replay carries the source newsletter's content-hash campaignId; the report must override it.
      'campaignId' => 'standardcampaign1',
    ];
    (new Newsletter())
      ->withSentStatus()
      ->withSendingQueue([
        'processed_at' => $now,
        'meta' => ['campaignId' => 'standardcampaign1'],
      ])
      ->withSendingQueue(['processed_at' => $now, 'meta' => $replayMeta])
      ->withSendingQueue(['processed_at' => $now, 'meta' => $replayMeta])
      ->create();

    $processed = $this->reporter->getProcessedCampaignAnalytics();

    // Original standard send is still counted as a standard campaign.
    $this->assertArrayHasKey('standardcampaign1', $processed);
    $this->assertSame(NewsletterEntity::TYPE_STANDARD, $processed['standardcampaign1']['newsletterType']);

    // Both replays from automation 42 collapse into a single automation campaign.
    $automationKeys = array_filter(array_keys($processed), function ($key) {
      return strpos($key, 'automation_') === 0;
    });
    $this->assertCount(1, $automationKeys);
    $this->assertArrayHasKey('automation_42', $processed);
    $this->assertSame(NewsletterEntity::TYPE_AUTOMATION, $processed['automation_42']['newsletterType']);
    $this->assertTrue($processed['automation_42']['sentLast7Days']);
  }
}
