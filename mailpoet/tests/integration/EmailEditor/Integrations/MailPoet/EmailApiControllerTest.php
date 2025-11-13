<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\NotFoundException;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\NewsletterOptionField;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\UnexpectedValueException;

class EmailApiControllerTest extends \MailPoetTest {
  /** @var EmailApiController */
  private $emailApiController;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  public function _before() {
    $this->emailApiController = $this->diContainer->get(EmailApiController::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
  }

  public function testItGetsEmailDataFromNewsletterEntity(): void {
    $wpPostId = 6;
    $newsletter = (new NewsletterFactory())
      ->withSubject('New subject')
      ->withPreheader('New preheader')
      ->withWpPostId($wpPostId)
      ->create();

    $emailData = $this->emailApiController->getEmailData(['id' => $wpPostId]);
    verify($emailData['subject'])->equals('New subject');
    verify($emailData['preheader'])->equals('New preheader');
    verify($emailData['id'])->equals($newsletter->getId());
  }

  public function testItSaveEmailDataToNewsletterEntity(): void {
    $wpPostId = 5;
    $newsletter = (new NewsletterFactory())
      ->withWpPostId($wpPostId)
      ->create();

    $this->emailApiController->saveEmailData([
      'id' => $newsletter->getId(),
      'subject' => 'New subject',
      'preheader' => 'New preheader',
      'theme' => ['styles' => ['spacing' => ['padding' => ['bottom' => '10px', 'left' => '10px', 'right' => '10px', 'top' => '10px']]]],
    ], new \WP_Post((object)['ID' => $wpPostId]));

    $this->entityManager->clear();
    $newsletter = $this->newslettersRepository->findOneById($newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    verify($newsletter->getSubject())->equals('New subject');
    verify($newsletter->getPreheader())->equals('New preheader');
  }

  public function testItThrowsErrorWhenNewsletterDoesNotExist(): void {
    try {
      $this->emailApiController->saveEmailData([
        'id' => 999,
        'subject' => 'New subject',
        'preheader' => 'New preheader',
      ], new \WP_Post((object)['ID' => 5]));

      $this->fail('Incorrect state exception should have been thrown.');
    } catch (NotFoundException $exception) {
      verify($exception->getHttpStatusCode())->equals(APIResponse::STATUS_NOT_FOUND);
      verify($exception->getMessage())->stringContainsString('Newsletter was not found');
    }
  }

  public function testItThrowsErrorWhenNewsletterWpPostIdDoesNotMatchWpPostId(): void {
    $newsletter = (new NewsletterFactory())
      ->withWpPostId(1)
      ->create();

    try {
      $this->emailApiController->saveEmailData([
        'id' => $newsletter->getId(),
        'subject' => 'New subject',
        'preheader' => 'New preheader',
      ], new \WP_Post((object)['ID' => 2]));

      $this->fail('Incorrect state exception should have been thrown.');
    } catch (UnexpectedValueException $exception) {
      verify($exception->getHttpStatusCode())->equals(APIResponse::STATUS_BAD_REQUEST);
      verify($exception->getMessage())->stringContainsString('Newsletter ID does not match the post ID');
    }
  }

  public function testItUpdatesScheduledAtAndSetsIsScheduledTo1(): void {
    $wpPostId = 7;
    $newsletter = (new NewsletterFactory())
      ->withWpPostId($wpPostId)
      ->create();

    (new NewsletterOptionField())->findOrCreate(
      NewsletterOptionFieldEntity::NAME_SCHEDULED_AT,
      NewsletterEntity::TYPE_STANDARD
    );
    (new NewsletterOptionField())->findOrCreate(
      NewsletterOptionFieldEntity::NAME_IS_SCHEDULED,
      NewsletterEntity::TYPE_STANDARD
    );
    $this->entityManager->flush();

    $scheduledAt = '2024-12-25 14:30:00';
    $this->emailApiController->saveEmailData([
      'id' => $newsletter->getId(),
      'subject' => 'Test Subject',
      'preheader' => 'Test Preheader',
      'scheduled_at' => $scheduledAt,
    ], new \WP_Post((object)['ID' => $wpPostId]));

    $this->entityManager->clear();
    $newsletter = $this->newslettersRepository->findOneById($newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    verify($newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_SCHEDULED_AT))->equals($scheduledAt);
    verify($newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_IS_SCHEDULED))->equals('1');
  }

  public function testItUpdatesScheduledAtToNullAndSetsIsScheduledTo0(): void {
    $wpPostId = 8;
    $newsletter = (new NewsletterFactory())
      ->withWpPostId($wpPostId)
      ->create();

    (new NewsletterOptionField())->findOrCreate(
      NewsletterOptionFieldEntity::NAME_SCHEDULED_AT,
      NewsletterEntity::TYPE_STANDARD
    );
    (new NewsletterOptionField())->findOrCreate(
      NewsletterOptionFieldEntity::NAME_IS_SCHEDULED,
      NewsletterEntity::TYPE_STANDARD
    );
    $this->entityManager->flush();

    // First set a scheduled time.
    $scheduledAt = '2024-12-25 14:30:00';
    $this->emailApiController->saveEmailData([
      'id' => $newsletter->getId(),
      'subject' => 'Test Subject',
      'preheader' => 'Test Preheader',
      'scheduled_at' => $scheduledAt,
    ], new \WP_Post((object)['ID' => $wpPostId]));

    // Then clear it by setting to null.
    $this->emailApiController->saveEmailData([
      'id' => $newsletter->getId(),
      'subject' => 'Test Subject',
      'preheader' => 'Test Preheader',
      'scheduled_at' => null,
    ], new \WP_Post((object)['ID' => $wpPostId]));

    $this->entityManager->clear();
    $newsletter = $this->newslettersRepository->findOneById($newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    verify($newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_SCHEDULED_AT))->null();
    verify($newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_IS_SCHEDULED))->equals('0');
  }

  public function testItUpdatesSegmentIds(): void {
    $wpPostId = 11;
    $newsletter = (new NewsletterFactory())
      ->withWpPostId($wpPostId)
      ->create();

    $segment1 = (new SegmentFactory())->create();
    $segment2 = (new SegmentFactory())->create();

    $this->emailApiController->saveEmailData([
      'id' => $newsletter->getId(),
      'subject' => 'Test Subject',
      'preheader' => 'Test Preheader',
      'segment_ids' => [$segment1->getId(), $segment2->getId()],
    ], new \WP_Post((object)['ID' => $wpPostId]));

    $this->entityManager->clear();
    $newsletter = $this->newslettersRepository->findOneById($newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $segmentIds = $newsletter->getSegmentIds();
    verify($segmentIds)->arrayCount(2);
    $this->assertContains($segment1->getId(), $segmentIds);
    $this->assertContains($segment2->getId(), $segmentIds);
  }

  public function testItRemovesSegmentsNotInNewList(): void {
    $wpPostId = 12;
    $segment1 = (new SegmentFactory())->create();
    $segment2 = (new SegmentFactory())->create();
    $segment3 = (new SegmentFactory())->create();

    $newsletter = (new NewsletterFactory())
      ->withWpPostId($wpPostId)
      ->withSegments([$segment1, $segment2])
      ->create();

    // Update to only include segment3, removing segment1 and segment2.
    $this->emailApiController->saveEmailData([
      'id' => $newsletter->getId(),
      'subject' => 'Test Subject',
      'preheader' => 'Test Preheader',
      'segment_ids' => [$segment3->getId()],
    ], new \WP_Post((object)['ID' => $wpPostId]));

    $this->entityManager->clear();
    $newsletter = $this->newslettersRepository->findOneById($newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $segmentIds = $newsletter->getSegmentIds();
    verify($segmentIds)->arrayCount(1);
    $this->assertContains($segment3->getId(), $segmentIds);
    $this->assertNotContains($segment1->getId(), $segmentIds);
    $this->assertNotContains($segment2->getId(), $segmentIds);
  }

  public function testItNormalizesSegmentIdsToIntegers(): void {
    $wpPostId = 13;
    $newsletter = (new NewsletterFactory())
      ->withWpPostId($wpPostId)
      ->create();

    $segment1 = (new SegmentFactory())->create();
    $segment2 = (new SegmentFactory())->create();

    // Pass segment IDs as strings to test normalization.
    $this->emailApiController->saveEmailData([
      'id' => $newsletter->getId(),
      'subject' => 'Test Subject',
      'preheader' => 'Test Preheader',
      'segment_ids' => [(string)$segment1->getId(), (string)$segment2->getId()],
    ], new \WP_Post((object)['ID' => $wpPostId]));

    $this->entityManager->clear();
    $newsletter = $this->newslettersRepository->findOneById($newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $segmentIds = $newsletter->getSegmentIds();
    verify($segmentIds)->arrayCount(2);
    $this->assertContains($segment1->getId(), $segmentIds);
    $this->assertContains($segment2->getId(), $segmentIds);

    foreach ($segmentIds as $segmentId) {
      verify($segmentId)->isInt();
    }
  }

  public function testItHandlesEmptySegmentIdsArray(): void {
    $wpPostId = 14;
    $segment1 = (new SegmentFactory())->create();
    $segment2 = (new SegmentFactory())->create();

    $newsletter = (new NewsletterFactory())
      ->withWpPostId($wpPostId)
      ->withSegments([$segment1, $segment2])
      ->create();

    // Clear all segments by passing empty array.
    $this->emailApiController->saveEmailData([
      'id' => $newsletter->getId(),
      'subject' => 'Test Subject',
      'preheader' => 'Test Preheader',
      'segment_ids' => [],
    ], new \WP_Post((object)['ID' => $wpPostId]));

    $this->entityManager->clear();
    $newsletter = $this->newslettersRepository->findOneById($newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $segmentIds = $newsletter->getSegmentIds();
    verify($segmentIds)->arrayCount(0);
  }

  public function _after() {
    parent::_after();
    $this->truncateEntity(NewsletterEntity::class);
  }
}
