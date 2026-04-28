<?php declare(strict_types = 1);

namespace MailPoet\Test\Unit\Subscribers;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Subscribers\ConfirmationEmailResolver;

class ConfirmationEmailResolverTest extends \MailPoetUnitTest {
  /** @var ConfirmationEmailResolver */
  private $resolver;

  public function _before() {
    parent::_before();
    $this->resolver = new ConfirmationEmailResolver();
  }

  public function testItReturnsNullsWhenNoSegmentsHaveCustomSettings(): void {
    $segment1 = $this->createSegment(null, null);
    $segment2 = $this->createSegment(null, null);

    [$emailId, $pageId] = $this->resolver->resolveFromSegments([$segment1, $segment2]);

    verify($emailId)->null();
    verify($pageId)->null();
  }

  public function testItReturnsCustomEmailWhenOneSegmentHasIt(): void {
    $segment1 = $this->createSegment(42, null);
    $segment2 = $this->createSegment(null, null);

    [$emailId, $pageId] = $this->resolver->resolveFromSegments([$segment1, $segment2]);

    verify($emailId)->equals(42);
    verify($pageId)->null();
  }

  public function testItReturnsCustomPageWhenOneSegmentHasIt(): void {
    $segment1 = $this->createSegment(null, null);
    $segment2 = $this->createSegment(null, 99);

    [$emailId, $pageId] = $this->resolver->resolveFromSegments([$segment1, $segment2]);

    verify($emailId)->null();
    verify($pageId)->equals(99);
  }

  public function testItReturnsCustomSettingsWhenAllSegmentsAgree(): void {
    $segment1 = $this->createSegment(42, 99);
    $segment2 = $this->createSegment(42, 99);

    [$emailId, $pageId] = $this->resolver->resolveFromSegments([$segment1, $segment2]);

    verify($emailId)->equals(42);
    verify($pageId)->equals(99);
  }

  public function testItFallsBackToGlobalWhenSegmentsConflictOnEmail(): void {
    $segment1 = $this->createSegment(42, null);
    $segment2 = $this->createSegment(43, null);

    [$emailId, $pageId] = $this->resolver->resolveFromSegments([$segment1, $segment2]);

    verify($emailId)->null();
    verify($pageId)->null();
  }

  public function testItFallsBackToGlobalWhenSegmentsConflictOnPage(): void {
    $segment1 = $this->createSegment(null, 99);
    $segment2 = $this->createSegment(null, 100);

    [$emailId, $pageId] = $this->resolver->resolveFromSegments([$segment1, $segment2]);

    verify($emailId)->null();
    verify($pageId)->null();
  }

  public function testItHandlesEmptySegmentArray(): void {
    [$emailId, $pageId] = $this->resolver->resolveFromSegments([]);

    verify($emailId)->null();
    verify($pageId)->null();
  }

  public function testItHandlesSingleSegmentWithBothSettings(): void {
    $segment = $this->createSegment(42, 99);

    [$emailId, $pageId] = $this->resolver->resolveFromSegments([$segment]);

    verify($emailId)->equals(42);
    verify($pageId)->equals(99);
  }

  public function testEmailAndPageResolveIndependently(): void {
    $segment1 = $this->createSegment(42, 99);
    $segment2 = $this->createSegment(42, 100);

    [$emailId, $pageId] = $this->resolver->resolveFromSegments([$segment1, $segment2]);

    verify($emailId)->equals(42);
    verify($pageId)->null();
  }

  private function createSegment(?int $confirmationEmailId, ?int $confirmationPageId): SegmentEntity {
    $segment = new SegmentEntity('Test', SegmentEntity::TYPE_DEFAULT, '');
    $segment->setConfirmationEmailId($confirmationEmailId);
    $segment->setConfirmationPageId($confirmationPageId);
    return $segment;
  }
}
