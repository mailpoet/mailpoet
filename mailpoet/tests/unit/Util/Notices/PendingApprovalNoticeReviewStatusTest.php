<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use MailPoet\Services\Bridge\ReviewStatus;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class PendingApprovalNoticeReviewStatusTest extends \MailPoetUnitTest {
  /** @var PendingApprovalNotice */
  private $notice;

  public function _before() {
    parent::_before();
    $settings = $this->createMock(SettingsController::class);
    $settings->method('get')->willReturnMap([
      ['mta.mailpoet_api_key_state.data.public_subscription_id', null, 'sub-123'],
    ]);
    $wp = $this->createMock(WPFunctions::class);
    $wp->method('getOption')->willReturnMap([['date_format', false, 'j. n. Y']]);
    $wp->method('wpDate')->willReturnCallback(function (string $format, int $timestamp): string {
      return gmdate($format, $timestamp);
    });
    $this->notice = new PendingApprovalNotice($settings, $wp);
  }

  /**
   * @dataProvider reviews
   */
  public function testItShowsWhereTheReviewStands(array $review, string $heading, string $text): void {
    $status = ReviewStatus::fromArray($review);
    $this->assertInstanceOf(ReviewStatus::class, $status);

    $message = $this->notice->getReviewStatusMessage($status);

    $this->assertStringContainsString($heading, $message);
    $this->assertStringContainsString($text, $message);
    $this->assertStringContainsString('https://kb.mailpoet.com/article/379-our-approval-process', $message);
    $this->assertStringContainsString('sending to your email list contacts is temporarily paused', $message);
    $this->assertStringContainsString('data-mailpoet-review-status="' . $review['status'] . '"', $message);
    $this->assertStringContainsString('data-public-subscription-id="sub-123"', $message);
    $this->assertStringContainsString('data-since="' . $review['since'] . '"', $message);
  }

  public static function reviews(): array {
    return [
      'fix: unfinished website' => [
        ['status' => 'fix', 'reason' => 'generic_content', 'since' => '2026-11-20T10:00:00.000Z'],
        'Action required',
        'Your website looks unfinished or shows placeholder content. Add your real content, or reply with details about your business, like a social media page or your address. We emailed you about this on 20. 11. 2026. Reply to that email when you’re done, and we’ll continue the review.',
      ],
      'fix: website offline' => [
        ['status' => 'fix', 'reason' => 'unreachable_website', 'since' => '2026-11-20T10:00:00.000Z'],
        'Action required',
        'We couldn’t open your website. Make sure it’s online and visible to visitors. We emailed you about this on 20. 11. 2026.',
      ],
      'question' => [
        ['status' => 'question', 'reason' => null, 'since' => '2026-11-21T10:00:00.000Z'],
        'Action required',
        'We emailed you a question about your plan on 21. 11. 2026. Reply to that email, and we’ll continue the review.',
      ],
      'replied' => [
        ['status' => 'replied', 'reason' => null, 'since' => '2026-11-22T10:00:00.000Z'],
        'We’re reviewing your plan',
        'We got your reply on 22. 11. 2026. We’ll email you when we’re done.',
      ],
      'reviewing' => [
        ['status' => 'reviewing', 'reason' => null, 'since' => '2026-11-23T10:00:00.000Z'],
        'We’re reviewing your plan',
        'We started on 23. 11. 2026. We’ll email you when we’re done.',
      ],
    ];
  }

  public function testItShowsOnlyTheHeadingWhenTheReviewHasNoDate(): void {
    $status = ReviewStatus::fromArray(['status' => 'reviewing', 'reason' => null, 'since' => null]);
    $this->assertInstanceOf(ReviewStatus::class, $status);

    $message = $this->notice->getReviewStatusMessage($status);

    $this->assertStringContainsString('We’re reviewing your plan', $message);
    $this->assertStringNotContainsString('We started on', $message);
  }
}
