<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Listing;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterSegmentEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Listing\Handler;
use MailPoet\Newsletter\Sending\NewsletterReplayMetadata;
use MailPoet\Newsletter\Sending\TimeZoneCampaignScheduler;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\NewsletterOptionField;

class NewsletterListingRepositoryTest extends \MailPoetTest {
  public function testItAppliesGroup() {
    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setSubject('Newsletter subject');
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();

    $listingHandler = new Handler();
    $newsletterListingRepository = $this->diContainer->get(NewsletterListingRepository::class);

    // all/trash groups
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition(['group' => 'all']));
    verify($newsletters)->arrayCount(1);

    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition(['group' => 'trash']));
    verify($newsletters)->arrayCount(0);

    // mark the newsletter sent
    $newsletter->setStatus(NewsletterEntity::STATUS_SENT);
    $this->entityManager->flush();

    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition(['group' => 'sent']));
    verify($newsletters)->arrayCount(1);

    // delete the newsletter
    $newsletter->setDeletedAt(new \DateTime());
    $this->entityManager->flush();

    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition(['group' => 'all']));
    verify($newsletters)->arrayCount(0);

    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition(['group' => 'trash']));
    verify($newsletters)->arrayCount(1);
  }

  public function testItAppliesSearch() {
    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setSubject('Search for "pineapple" here');
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();

    $listingHandler = new Handler();
    $newsletterListingRepository = $this->diContainer->get(NewsletterListingRepository::class);

    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition(['search' => 'pineapple']));
    verify($newsletters)->arrayCount(1);

    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition(['search' => 'tomato']));
    verify($newsletters)->arrayCount(0);
  }

  public function testItAppliesSegmentFilter() {
    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setSubject('Newsletter without segment');
    $this->entityManager->persist($newsletter);

    $segment = new SegmentEntity('Segment', SegmentEntity::TYPE_DEFAULT, 'Segment description');
    $this->entityManager->persist($segment);

    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setSubject('Newsletter with segment');
    $this->entityManager->persist($newsletter);

    $newsletterSegment = new NewsletterSegmentEntity($newsletter, $segment);
    $this->entityManager->persist($newsletterSegment);

    $this->entityManager->flush();

    $listingHandler = new Handler();
    $newsletterListingRepository = $this->diContainer->get(NewsletterListingRepository::class);

    // without filter
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([]));
    verify($newsletters)->arrayCount(2);

    // with filter
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'filter' => [
        'segment' => $segment->getId(),
      ],
    ]));
    verify($newsletters)->arrayCount(1);
  }

  public function testItAppliesTypeParameter() {
    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setSubject('Newsletter subject');
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();

    $listingHandler = new Handler();
    $newsletterListingRepository = $this->diContainer->get(NewsletterListingRepository::class);

    // without type
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([]));
    verify($newsletters)->arrayCount(1);

    // with 'standard' type
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'params' => [
        'type' => 'standard',
      ],
    ]));
    verify($newsletters)->arrayCount(1);

    // with 'welcome' type
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'params' => [
        'type' => 'welcome',
      ],
    ]));
    verify($newsletters)->arrayCount(0);
  }

  public function testItAppliesAutomaticEmailsGroupParameter() {
    $newsletterOptionField = (new NewsletterOptionField())->findOrCreate('group', NewsletterEntity::TYPE_AUTOMATIC);

    $newsletter1 = new NewsletterEntity();
    $newsletter1->setType(NewsletterEntity::TYPE_AUTOMATIC);
    $newsletter1->setSubject('Automatic email 1');
    $this->entityManager->persist($newsletter1);

    $newsletter1Option = new NewsletterOptionEntity($newsletter1, $newsletterOptionField);
    $newsletter1Option->setValue('woocommerce');
    $this->entityManager->persist($newsletter1Option);

    $newsletter2 = new NewsletterEntity();
    $newsletter2->setType(NewsletterEntity::TYPE_AUTOMATIC);
    $newsletter2->setSubject('Automatic email 2');
    $this->entityManager->persist($newsletter2);

    $newsletter2Option = new NewsletterOptionEntity($newsletter2, $newsletterOptionField);
    $newsletter2Option->setValue('unicorns');
    $this->entityManager->persist($newsletter2Option);

    $this->entityManager->flush();

    $listingHandler = new Handler();
    $newsletterListingRepository = $this->diContainer->get(NewsletterListingRepository::class);

    // get 'woocommerce' group
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'params' => [
        'type' => NewsletterEntity::TYPE_AUTOMATIC,
        'group' => 'woocommerce',
      ],
    ]));
    verify($newsletters)->arrayCount(1);

    // get 'unicorns' group
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'params' => [
        'type' => NewsletterEntity::TYPE_AUTOMATIC,
        'group' => 'unicorns',
      ],
    ]));
    verify($newsletters)->arrayCount(1);

    // get all emails group
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition(['type' => NewsletterEntity::TYPE_AUTOMATIC]));
    verify($newsletters)->arrayCount(2);
  }

  public function testItAppliesParentIdParameter() {
    $parent = new NewsletterEntity();
    $parent->setType(NewsletterEntity::TYPE_NOTIFICATION);
    $parent->setSubject('Newsletter subject');
    $this->entityManager->persist($parent);

    $newsletter = new NewsletterEntity();
    $newsletter->setParent($parent);
    $newsletter->setType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY);
    $newsletter->setSubject('Newsletter subject');
    $this->entityManager->persist($newsletter);

    $this->entityManager->flush();

    $listingHandler = new Handler();
    $newsletterListingRepository = $this->diContainer->get(NewsletterListingRepository::class);

    // existing parent ID
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'params' => [
        'parentId' => (string)$parent->getId(),
      ],
    ]));
    verify($newsletters)->arrayCount(1);

    // non-existent parent ID
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'params' => [
        'parentId' => (string)$newsletter->getId(),
      ],
    ]));
    verify($newsletters)->arrayCount(0);
  }

  public function testItSearchesInStaticAndRenderedSubjectsForPostNotification() {
    $originalSubject = 'Notification history [newsletter:post_title]';
    $renderedSubject = 'Notification history Hello World Post';

    $parent = new NewsletterEntity();
    $parent->setType(NewsletterEntity::TYPE_NOTIFICATION);
    $parent->setSubject($originalSubject);
    $this->entityManager->persist($parent);
    $this->entityManager->flush();

    $notificationHistoryNotSent = (new Newsletter())
    ->withParent($parent)
    ->withType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY)
    ->withSubject($originalSubject)
    ->create();

    $notificationHistorySent = (new Newsletter())
      ->withParent($parent)
      ->withType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY)
      ->withSubject($originalSubject)
      ->withSendingQueue([
        'status' => 'sent',
        'subject' => $renderedSubject,
      ])
      ->create();

    $listingHandler = new Handler();
    $newsletterListingRepository = $this->diContainer->get(NewsletterListingRepository::class);

    // Search by original subject with placeholder
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'params' => [
        'parentId' => (string)$parent->getId(),
        'type' => NewsletterEntity::TYPE_NOTIFICATION_HISTORY,
      ],
      'search' => $originalSubject,
    ]));
    verify($newsletters)->arrayCount(2);

    // Search by common part of subject
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'params' => [
        'parentId' => (string)$parent->getId(),
        'type' => NewsletterEntity::TYPE_NOTIFICATION_HISTORY,
      ],
      'search' => 'history',
    ]));
    verify($newsletters)->arrayCount(2);

    // Search by rendered subject
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'params' => [
        'parentId' => (string)$parent->getId(),
        'type' => NewsletterEntity::TYPE_NOTIFICATION_HISTORY,
      ],
      'search' => $renderedSubject,
    ]));
    verify($newsletters)->arrayCount(1);
    verify($newsletters[0]->getId())->equals($notificationHistorySent->getId());

    // Search by part of rendered subject
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'params' => [
        'parentId' => (string)$parent->getId(),
        'type' => NewsletterEntity::TYPE_NOTIFICATION_HISTORY,
      ],
      'search' => 'Hello World',
    ]));
    verify($newsletters)->arrayCount(1);
    verify($newsletters[0]->getId())->equals($notificationHistorySent->getId());
  }

  public function testItAppliesSort() {
    $newsletter1 = new NewsletterEntity();
    $newsletter1->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter1->setSubject('Newsletter A');
    $this->entityManager->persist($newsletter1);

    $newsletter2 = new NewsletterEntity();
    $newsletter2->setType(NewsletterEntity::TYPE_WELCOME);
    $newsletter2->setSubject('Newsletter B');
    $this->entityManager->persist($newsletter2);

    $this->entityManager->flush();

    $listingHandler = new Handler();
    $newsletterListingRepository = $this->diContainer->get(NewsletterListingRepository::class);

    // ASC
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'sort_by' => 'subject',
      'sort_order' => 'asc',
    ]));
    verify($newsletters)->arrayCount(2);
    verify($newsletters[0]->getSubject())->same('Newsletter A');
    verify($newsletters[1]->getSubject())->same('Newsletter B');

    // DESC
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'sort_by' => 'subject',
      'sort_order' => 'desc',
    ]));
    verify($newsletters)->arrayCount(2);
    verify($newsletters[0]->getSubject())->same('Newsletter B');
    verify($newsletters[1]->getSubject())->same('Newsletter A');
  }

  public function testItAppliesLimitAndOffset() {
    $newsletter1 = new NewsletterEntity();
    $newsletter1->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter1->setSubject('Newsletter A');
    $this->entityManager->persist($newsletter1);

    $newsletter2 = new NewsletterEntity();
    $newsletter2->setType(NewsletterEntity::TYPE_WELCOME);
    $newsletter2->setSubject('Newsletter B');
    $this->entityManager->persist($newsletter2);

    $this->entityManager->flush();

    $listingHandler = new Handler();
    $newsletterListingRepository = $this->diContainer->get(NewsletterListingRepository::class);

    // first page
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'limit' => 1,
      'offset' => 0,
    ]));
    verify($newsletters)->arrayCount(1);
    verify($newsletters[0]->getSubject())->same('Newsletter A');

    // second page
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'limit' => 1,
      'offset' => 1,
    ]));
    verify($newsletters)->arrayCount(1);
    verify($newsletters[0]->getSubject())->same('Newsletter B');

    // third page
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'limit' => 1,
      'offset' => 2,
    ]));
    verify($newsletters)->arrayCount(0);
  }

  public function testItReturnsCorrectSegmentFilterData() {
    $newsletter1 = new NewsletterEntity();
    $newsletter1->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter1->setSubject('Newsletter with segment 1');
    $this->entityManager->persist($newsletter1);

    $segment1 = new SegmentEntity('Segment 1', SegmentEntity::TYPE_DEFAULT, 'Segment description');
    $this->entityManager->persist($segment1);

    $newsletterSegment1 = new NewsletterSegmentEntity($newsletter1, $segment1);
    $this->entityManager->persist($newsletterSegment1);

    $segment2 = new SegmentEntity('Segment 2', SegmentEntity::TYPE_DEFAULT, 'Segment without any newsletter');
    $this->entityManager->persist($segment2);
    $this->entityManager->flush();

    $listingHandler = new Handler();
    $newsletterListingRepository = $this->diContainer->get(NewsletterListingRepository::class);
    $filters = $newsletterListingRepository->getFilters($listingHandler->getListingDefinition([
      'limit' => 1,
      'offset' => 0,
    ]));

    verify($filters['segment'])->arrayCount(2); // All list + 1 segments
    verify($filters['segment'][0]['label'])->equals('All Lists');
    verify($filters['segment'][1]['label'])->equals('Segment 1');
    verify($filters['segment'][0]['value'])->equals('');
    verify($filters['segment'][1]['value'])->equals($segment1->getId());
  }

  public function testItFiltersBySentAtFrom() {
    $this->createSent('Newsletter 1', '2025-01-01 10:00:00');
    $this->createSent('Newsletter 2', '2025-02-01 10:00:00');
    $this->createSent('Newsletter 3', '2025-03-01 10:00:00');

    $this->assertSame(['Newsletter 2', 'Newsletter 3'], $this->listSubjects(['sent_at_from' => '2025-02-01']));
  }

  public function testItFiltersBySentAtToAsWholeDay() {
    $this->createSent('Newsletter 1', '2025-01-01 10:00:00');
    $this->createSent('Newsletter 2', '2025-02-01 23:59:59');
    $this->createSent('Newsletter 3', '2025-02-02 00:00:00');

    $this->assertSame(['Newsletter 1', 'Newsletter 2'], $this->listSubjects(['sent_at_to' => '2025-02-01']));
  }

  public function testItFiltersBySentAtRange() {
    $this->createSent('Newsletter 1', '2025-01-01 10:00:00');
    $this->createSent('Newsletter 2', '2025-02-01 10:00:00');
    $this->createSent('Newsletter 3', '2025-03-01 10:00:00');

    $this->assertSame(
      ['Newsletter 2'],
      $this->listSubjects(['sent_at_from' => '2025-01-15', 'sent_at_to' => '2025-02-15'])
    );
  }

  public function testUnparsableSentAtMatchesNothing() {
    $this->createSent('Newsletter 1', '2025-01-01 10:00:00');

    $this->assertSame([], $this->listSubjects(['sent_at_from' => 'not a date']));
    $this->assertSame([], $this->listSubjects(['sent_at_to' => 'not a date']));
    $this->assertSame([], $this->listSubjects(['sent_at_from' => ['2025-01-01']]));
    $this->assertSame([], $this->listSubjects(['sent_at_from' => '2025-01-01 00:00:00']));
  }

  public function testSentAtFilterExcludesScheduledNewsletters() {
    $this->createSent('Sent Newsletter', '2025-02-01 10:00:00');
    $this->createScheduled('Scheduled Newsletter', '2025-02-15 10:00:00');
    $this->createSent('Old Newsletter', '2025-01-01 10:00:00');
    $this->createScheduled('Future Newsletter', '2025-03-01 10:00:00');

    $this->assertSame(
      ['Sent Newsletter'],
      $this->listSubjects(['sent_at_from' => '2025-02-01', 'sent_at_to' => '2025-02-20'])
    );
    $this->assertSame([], $this->listSubjects(['sent_at_from' => '2025-02-10', 'sent_at_to' => '2025-02-20']));
    $this->assertSame(['Sent Newsletter'], $this->listSubjects(['sent_at_from' => '2025-02-01']));
  }

  public function testSentAtRangeIgnoresScheduledTimeWhenSentAtIsOutside() {
    $newsletter = $this->createSent('Rescheduled', '2025-01-01 10:00:00');
    $this->addScheduledTask($newsletter, '2025-02-10 10:00:00');

    $this->assertSame([], $this->listSubjects(['sent_at_from' => '2025-02-01', 'sent_at_to' => '2025-02-20']));
    $this->assertSame([], $this->listSubjects(['sent_at_from' => '2025-02-01']));
    $this->assertSame(['Rescheduled'], $this->listSubjects(['sent_at_to' => '2025-01-01']));
  }

  /** @dataProvider siteDayCases */
  public function testSentAtFiltersBySiteLocalDay(string $timezone, $gmtOffset, string $day, array $inside, array $outside) {
    $this->withSiteTimezone($timezone, $gmtOffset, function () use ($day, $inside, $outside) {
      foreach (array_merge($inside, $outside) as $sentAt) {
        $this->createSent($sentAt, $sentAt);
      }
      foreach ([['sent_at_from' => $day, 'sent_at_to' => $day], ['sent_at_from' => $day], ['sent_at_to' => $day]] as $filter) {
        $subjects = $this->listSubjects($filter);
        if (isset($filter['sent_at_from'])) {
          $this->assertContains($inside[0], $subjects);
          $this->assertNotContains($outside[0], $subjects);
        }
        if (isset($filter['sent_at_to'])) {
          $this->assertContains($inside[1], $subjects);
          $this->assertNotContains($outside[1], $subjects);
        }
        if (isset($filter['sent_at_from'], $filter['sent_at_to'])) {
          $this->assertSame($inside, $subjects);
        }
      }
    });
  }

  /**
   * Site timezone, options to set, filter day, and UTC sent-at values grouped by whether the
   * row belongs to that site-local day. Rows sit one second either side of the day boundaries.
   *
   * @return array<string, array{string, string|float|int, string, string[], string[]}>
   */
  public function siteDayCases(): array {
    return [
      'utc site' => ['', 0, '2020-03-10', ['2020-03-10 00:00:00', '2020-03-10 23:59:59'], ['2020-03-09 23:59:59', '2020-03-11 00:00:00']],
      'dst zone, standard time' => ['Europe/Prague', 0, '2020-03-10', ['2020-03-09 23:00:00', '2020-03-10 22:59:59'], ['2020-03-09 22:59:59', '2020-03-10 23:00:00']],
      'fixed gmt offset' => ['', 5.5, '2020-03-10', ['2020-03-09 18:30:00', '2020-03-10 18:29:59'], ['2020-03-09 18:29:59', '2020-03-10 18:30:00']],
    ];
  }

  public function testItFiltersBySegmentIdsWithIsAnyOperator() {
    $segment1 = new SegmentEntity('Segment 1', SegmentEntity::TYPE_DEFAULT, 'Description 1');
    $this->entityManager->persist($segment1);

    $segment2 = new SegmentEntity('Segment 2', SegmentEntity::TYPE_DEFAULT, 'Description 2');
    $this->entityManager->persist($segment2);

    $segment3 = new SegmentEntity('Segment 3', SegmentEntity::TYPE_DEFAULT, 'Description 3');
    $this->entityManager->persist($segment3);

    $newsletter1 = new NewsletterEntity();
    $newsletter1->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter1->setSubject('Newsletter 1');
    $this->entityManager->persist($newsletter1);

    $newsletterSegment1 = new NewsletterSegmentEntity($newsletter1, $segment1);
    $this->entityManager->persist($newsletterSegment1);

    $newsletter2 = new NewsletterEntity();
    $newsletter2->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter2->setSubject('Newsletter 2');
    $this->entityManager->persist($newsletter2);

    $newsletterSegment2 = new NewsletterSegmentEntity($newsletter2, $segment2);
    $this->entityManager->persist($newsletterSegment2);

    $newsletter3 = new NewsletterEntity();
    $newsletter3->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter3->setSubject('Newsletter 3');
    $this->entityManager->persist($newsletter3);

    $newsletterSegment3 = new NewsletterSegmentEntity($newsletter3, $segment3);
    $this->entityManager->persist($newsletterSegment3);

    $this->entityManager->flush();

    $listingHandler = new Handler();
    $newsletterListingRepository = $this->diContainer->get(NewsletterListingRepository::class);

    // Filter by segment 1 and 2 with isAny operator
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'filter' => [
        'segment_ids' => [$segment1->getId(), $segment2->getId()],
        'segment_operator' => 'isAny',
      ],
    ]));
    verify($newsletters)->arrayCount(2);
    verify($newsletters[0]->getSubject())->equals('Newsletter 1');
    verify($newsletters[1]->getSubject())->equals('Newsletter 2');
  }

  public function testItFiltersBySegmentIdsWithIsNoneOperator() {
    $segment1 = new SegmentEntity('Segment 1', SegmentEntity::TYPE_DEFAULT, 'Description 1');
    $this->entityManager->persist($segment1);

    $segment2 = new SegmentEntity('Segment 2', SegmentEntity::TYPE_DEFAULT, 'Description 2');
    $this->entityManager->persist($segment2);

    $segment3 = new SegmentEntity('Segment 3', SegmentEntity::TYPE_DEFAULT, 'Description 3');
    $this->entityManager->persist($segment3);

    $newsletter1 = new NewsletterEntity();
    $newsletter1->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter1->setSubject('Newsletter 1');
    $this->entityManager->persist($newsletter1);

    $newsletterSegment1 = new NewsletterSegmentEntity($newsletter1, $segment1);
    $this->entityManager->persist($newsletterSegment1);

    $newsletter2 = new NewsletterEntity();
    $newsletter2->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter2->setSubject('Newsletter 2');
    $this->entityManager->persist($newsletter2);

    $newsletterSegment2 = new NewsletterSegmentEntity($newsletter2, $segment2);
    $this->entityManager->persist($newsletterSegment2);

    $newsletter3 = new NewsletterEntity();
    $newsletter3->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter3->setSubject('Newsletter 3');
    $this->entityManager->persist($newsletter3);

    $newsletterSegment3 = new NewsletterSegmentEntity($newsletter3, $segment3);
    $this->entityManager->persist($newsletterSegment3);

    $this->entityManager->flush();

    $listingHandler = new Handler();
    $newsletterListingRepository = $this->diContainer->get(NewsletterListingRepository::class);

    // Filter by segment 1 and 2 with isNone operator - should return only newsletter 3
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'filter' => [
        'segment_ids' => [$segment1->getId(), $segment2->getId()],
        'segment_operator' => 'isNone',
      ],
    ]));
    verify($newsletters)->arrayCount(1);
    verify($newsletters[0]->getSubject())->equals('Newsletter 3');
  }

  public function testItFiltersBySegmentIdsWithMultipleSegmentsAssigned() {
    $segment1 = new SegmentEntity('Segment 1', SegmentEntity::TYPE_DEFAULT, 'Description 1');
    $this->entityManager->persist($segment1);

    $segment2 = new SegmentEntity('Segment 2', SegmentEntity::TYPE_DEFAULT, 'Description 2');
    $this->entityManager->persist($segment2);

    $newsletter1 = new NewsletterEntity();
    $newsletter1->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter1->setSubject('Newsletter with both segments');
    $this->entityManager->persist($newsletter1);

    $newsletterSegment1 = new NewsletterSegmentEntity($newsletter1, $segment1);
    $this->entityManager->persist($newsletterSegment1);

    $newsletterSegment2 = new NewsletterSegmentEntity($newsletter1, $segment2);
    $this->entityManager->persist($newsletterSegment2);

    $newsletter2 = new NewsletterEntity();
    $newsletter2->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter2->setSubject('Newsletter with no segments');
    $this->entityManager->persist($newsletter2);

    $this->entityManager->flush();

    $listingHandler = new Handler();
    $newsletterListingRepository = $this->diContainer->get(NewsletterListingRepository::class);

    // Filter by segment 1 with isAny - should return newsletter 1
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'filter' => [
        'segment_ids' => [$segment1->getId()],
        'segment_operator' => 'isAny',
      ],
    ]));
    verify($newsletters)->arrayCount(1);
    verify($newsletters[0]->getSubject())->equals('Newsletter with both segments');

    // Filter by segment 1 with isNone - should return newsletter 2
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'filter' => [
        'segment_ids' => [$segment1->getId()],
        'segment_operator' => 'isNone',
      ],
    ]));
    verify($newsletters)->arrayCount(1);
    verify($newsletters[0]->getSubject())->equals('Newsletter with no segments');
  }

  public function testItCombinesSentAtAndSegmentFilters() {
    $segment1 = new SegmentEntity('Segment 1', SegmentEntity::TYPE_DEFAULT, 'Description 1');
    $this->entityManager->persist($segment1);

    $segment2 = new SegmentEntity('Segment 2', SegmentEntity::TYPE_DEFAULT, 'Description 2');
    $this->entityManager->persist($segment2);

    $newsletter1 = new NewsletterEntity();
    $newsletter1->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter1->setSubject('Newsletter 1');
    $newsletter1->setSentAt(new \DateTime('2025-01-01 10:00:00'));
    $this->entityManager->persist($newsletter1);

    $newsletterSegment1 = new NewsletterSegmentEntity($newsletter1, $segment1);
    $this->entityManager->persist($newsletterSegment1);

    $newsletter2 = new NewsletterEntity();
    $newsletter2->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter2->setSubject('Newsletter 2');
    $newsletter2->setSentAt(new \DateTime('2025-02-01 10:00:00'));
    $this->entityManager->persist($newsletter2);

    $newsletterSegment2 = new NewsletterSegmentEntity($newsletter2, $segment1);
    $this->entityManager->persist($newsletterSegment2);

    $newsletter3 = new NewsletterEntity();
    $newsletter3->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter3->setSubject('Newsletter 3');
    $newsletter3->setSentAt(new \DateTime('2025-03-01 10:00:00'));
    $this->entityManager->persist($newsletter3);

    $newsletterSegment3 = new NewsletterSegmentEntity($newsletter3, $segment2);
    $this->entityManager->persist($newsletterSegment3);

    $this->entityManager->flush();

    $listingHandler = new Handler();
    $newsletterListingRepository = $this->diContainer->get(NewsletterListingRepository::class);

    // Combine sent_at_from and segment filter
    $newsletters = $newsletterListingRepository->getData($listingHandler->getListingDefinition([
      'filter' => [
        'sent_at_from' => '2025-01-15',
        'segment_ids' => [$segment1->getId()],
        'segment_operator' => 'isAny',
      ],
    ]));
    verify($newsletters)->arrayCount(1);
    verify($newsletters[0]->getSubject())->equals('Newsletter 2');
  }

  public function testItFiltersBySingleStatus() {
    $draft = $this->createStandard('Draft', NewsletterEntity::STATUS_DRAFT);
    $this->createStandard('Sent', NewsletterEntity::STATUS_SENT);

    $this->assertSame([$draft->getId()], $this->listIds(['status' => 'draft'], 'standard'));
    $this->assertSame([$draft->getId()], $this->listIds(['status' => ['draft']], 'standard'));
  }

  public function testItFiltersByMultipleStatuses() {
    $draft = $this->createStandard('Draft', NewsletterEntity::STATUS_DRAFT);
    $sent = $this->createStandard('Sent', NewsletterEntity::STATUS_SENT);
    $this->createStandard('Scheduled', NewsletterEntity::STATUS_SCHEDULED);

    $this->assertEqualsCanonicalizing(
      [$draft->getId(), $sent->getId()],
      $this->listIds(['status' => ['draft', 'sent']], 'standard')
    );
  }

  public function testItSeparatesPausedFromSendingOnStandardEmails() {
    $running = $this->createSendingWithTask(ScheduledTaskEntity::STATUS_COMPLETED, 'Running');
    $paused = $this->createSendingWithTask(ScheduledTaskEntity::STATUS_PAUSED, 'Paused');

    $this->assertSame([$paused->getId()], $this->listIds(['status' => 'paused'], 'standard'));
    $this->assertSame([$running->getId()], $this->listIds(['status' => 'sending'], 'standard'));
    $this->assertEqualsCanonicalizing(
      [$running->getId(), $paused->getId()],
      $this->listIds(['status' => ['sending', 'paused']], 'standard')
    );
  }

  public function testItCombinesPausedWithPlainStatuses() {
    $draft = $this->createStandard('Draft', NewsletterEntity::STATUS_DRAFT);
    $paused = $this->createSendingWithTask(ScheduledTaskEntity::STATUS_PAUSED, 'Paused');
    $this->createSendingWithTask(ScheduledTaskEntity::STATUS_COMPLETED, 'Running');

    $this->assertEqualsCanonicalizing(
      [$draft->getId(), $paused->getId()],
      $this->listIds(['status' => ['draft', 'paused']], 'standard')
    );
  }

  public function testReplayOnlyPausedQueueIsNotPaused() {
    $newsletter = (new Newsletter())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withStatus(NewsletterEntity::STATUS_SENDING)
      ->withSubject('Replay paused')
      ->withSendingQueue(['status' => ScheduledTaskEntity::STATUS_COMPLETED])
      ->withSendingQueue([
        'status' => ScheduledTaskEntity::STATUS_PAUSED,
        'meta' => [NewsletterReplayMetadata::LATEST_NEWSLETTER_REPLAY => true],
      ])
      ->create();

    $this->assertSame([], $this->listIds(['status' => 'paused'], 'standard'));
    $this->assertSame([$newsletter->getId()], $this->listIds(['status' => 'sending'], 'standard'));
  }

  public function testItUsesLatestNonReplayQueueForPausedStatus() {
    $newsletter = (new Newsletter())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withStatus(NewsletterEntity::STATUS_SENDING)
      ->withSubject('Older paused')
      ->withSendingQueue(['status' => ScheduledTaskEntity::STATUS_PAUSED])
      ->withSendingQueue(['status' => ScheduledTaskEntity::STATUS_COMPLETED])
      ->create();

    $this->assertSame([], $this->listIds(['status' => 'paused'], 'standard'));
    $this->assertSame([$newsletter->getId()], $this->listIds(['status' => 'sending'], 'standard'));
  }

  public function testTimeZoneCampaignWithPausedBatchIsPaused() {
    $meta = [
      TimeZoneCampaignScheduler::META_SEND_BY_TIMEZONE => true,
      TimeZoneCampaignScheduler::META_TIMEZONE_CAMPAIGN_ID => 'campaign-1',
    ];
    // The latest batch is completed, an earlier batch is paused: the label shows “paused”.
    $newsletter = (new Newsletter())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withStatus(NewsletterEntity::STATUS_SENDING)
      ->withSubject('Time zone')
      ->withSendingQueue(['status' => ScheduledTaskEntity::STATUS_PAUSED, 'meta' => $meta])
      ->withSendingQueue(['status' => ScheduledTaskEntity::STATUS_COMPLETED, 'meta' => $meta])
      ->create();

    $this->assertSame([$newsletter->getId()], $this->listIds(['status' => 'paused'], 'standard'));
    $this->assertSame([], $this->listIds(['status' => 'sending'], 'standard'));
  }

  public function testItFiltersPausedNotificationHistory() {
    $paused = $this->createSendingWithTask(ScheduledTaskEntity::STATUS_PAUSED, 'Paused history', NewsletterEntity::TYPE_NOTIFICATION_HISTORY);
    $this->createSendingWithTask(ScheduledTaskEntity::STATUS_COMPLETED, 'Running history', NewsletterEntity::TYPE_NOTIFICATION_HISTORY);

    $this->assertSame([$paused->getId()], $this->listIds(['status' => 'paused'], NewsletterEntity::TYPE_NOTIFICATION_HISTORY));
  }

  public function testItReturnsSupportedStatusesPerType() {
    $this->assertSame(['draft', 'scheduled', 'sending', 'paused', 'sent'], NewsletterListingRepository::getSupportedStatusesForType('standard'));
    $this->assertSame(['sending', 'paused', 'sent'], NewsletterListingRepository::getSupportedStatusesForType('notification_history'));
    foreach (['notification', 're_engagement', 'welcome', 'automatic'] as $type) {
      $this->assertSame(['active', 'draft'], NewsletterListingRepository::getSupportedStatusesForType($type));
    }
    $this->assertSame([], NewsletterListingRepository::getSupportedStatusesForType(null));
    $this->assertSame([], NewsletterListingRepository::getSupportedStatusesForType('unknown'));
  }

  public function testUnsupportedStatusMatchesNothing() {
    $this->createStandard('Draft', NewsletterEntity::STATUS_DRAFT);
    $this->createStandard('Sent', NewsletterEntity::STATUS_SENT);

    $this->assertSame([], $this->listIds(['status' => 'active'], 'standard'));
    $this->assertSame([], $this->listIds(['status' => ['active', 'bogus']], 'standard'));
    $this->assertSame([], $this->listIds(['status' => 'draft'], null));
    $this->assertSame([], $this->listIds(['status' => 'draft'], 'unknown'));
  }

  public function testStatusFilterCombinesWithGroupAndType() {
    $this->createStandard('Draft', NewsletterEntity::STATUS_DRAFT);
    $sent = $this->createStandard('Sent', NewsletterEntity::STATUS_SENT);
    (new Newsletter())->withType(NewsletterEntity::TYPE_NOTIFICATION)->withStatus(NewsletterEntity::STATUS_ACTIVE)->withSubject('Notif')->create();

    $this->assertSame([$sent->getId()], $this->listIds(['status' => ['draft', 'sent']], 'standard', 'sent'));
    $this->assertSame([], $this->listIds(['status' => 'draft'], 'standard', 'sent'));
    $this->assertSame([], $this->listIds(['status' => 'draft'], 'standard', 'trash'));
  }

  public function testInvalidSegmentFiltersMatchNothing() {
    $segment = new SegmentEntity('Segment', SegmentEntity::TYPE_DEFAULT, 'Segment description');
    $this->entityManager->persist($segment);
    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setSubject('With segment');
    $this->entityManager->persist($newsletter);
    $this->entityManager->persist(new NewsletterSegmentEntity($newsletter, $segment));
    $this->entityManager->flush();
    $id = $segment->getId();

    $this->assertCount(1, $this->listIds(['segment_ids' => [$id], 'segment_operator' => 'isAny'], null));
    $this->assertSame([], $this->listIds(['segment_ids' => [$id], 'segment_operator' => 'bogus'], null));
    $this->assertSame([], $this->listIds(['segment_ids' => [$id], 'segment_operator' => null], null));
    $this->assertSame([], $this->listIds(['segment_ids' => ['abc'], 'segment_operator' => 'isAny'], null));
    $this->assertSame([], $this->listIds(['segment_ids' => [$id, 'abc'], 'segment_operator' => 'isNone'], null));
    $this->assertCount(1, $this->listIds(['segment' => ''], null));
  }

  public function testSortingIsStableWithTies() {
    $first = $this->createStandard('Same', NewsletterEntity::STATUS_DRAFT);
    $second = $this->createStandard('Same', NewsletterEntity::STATUS_DRAFT);
    $third = $this->createStandard('Same', NewsletterEntity::STATUS_DRAFT);
    $ids = [$first->getId(), $second->getId(), $third->getId()];

    foreach (['status', 'name', 'subject', 'updated_at'] as $sortBy) {
      $this->assertSame($ids, $this->listIds([], null, null, $sortBy, 'asc'), "asc by $sortBy");
      $this->assertSame(array_reverse($ids), $this->listIds([], null, null, $sortBy, 'desc'), "desc by $sortBy");
    }
  }

  public function testNewsletterInTwoSelectedListsAppearsOnce() {
    [$listA, $listB, $listC] = $this->createLists(['A', 'B', 'C']);
    $both = $this->createWithLists('Both', [$listA, $listB]);
    $onlyA = $this->createWithLists('Only A', [$listA]);
    $onlyC = $this->createWithLists('Only C', [$listC]);
    $filter = ['segment_ids' => [$listA->getId(), $listB->getId()], 'segment_operator' => 'isAny'];

    $this->assertSame([$both->getId(), $onlyA->getId()], $this->listIds($filter, null));
    $repository = $this->diContainer->get(NewsletterListingRepository::class);
    $definition = (new Handler())->getListingDefinition(['filter' => $filter, 'limit' => 1, 'offset' => 0]);
    $this->assertCount(1, $repository->getData($definition));
    $this->assertSame(2, $repository->getCount($definition));
    $page2 = (new Handler())->getListingDefinition(['filter' => $filter, 'limit' => 1, 'offset' => 1]);
    $this->assertSame([$onlyA->getId()], array_map(function(NewsletterEntity $n) {
      return $n->getId();
    }, $repository->getData($page2)));
    $this->assertNotContains($onlyC->getId(), $this->listIds($filter, null));
  }

  public function testBulkActionableIdsAreUniqueForMultipleLists() {
    [$listA, $listB] = $this->createLists(['A', 'B']);
    $both = $this->createWithLists('Both', [$listA, $listB]);
    $other = $this->createWithLists('Other', [$listB]);
    $repository = $this->diContainer->get(NewsletterListingRepository::class);
    $definition = (new Handler())->getListingDefinition([
      'filter' => ['segment_ids' => [$listA->getId(), $listB->getId()], 'segment_operator' => 'isAny'],
    ]);

    $ids = array_map('intval', $repository->getActionableIds($definition));
    sort($ids);
    $this->assertSame([$both->getId(), $other->getId()], $ids);
  }

  public function testIsNoneExcludesEmailsInAnySelectedList() {
    [$listA, $listB, $listC] = $this->createLists(['A', 'B', 'C']);
    $this->createWithLists('Both', [$listA, $listB]);
    $this->createWithLists('Only A', [$listA]);
    $onlyC = $this->createWithLists('Only C', [$listC]);
    $none = $this->createStandard('No list', NewsletterEntity::STATUS_DRAFT);
    $filter = ['segment_ids' => [$listA->getId(), $listB->getId()], 'segment_operator' => 'isNone'];

    $this->assertSame([$onlyC->getId(), $none->getId()], $this->listIds($filter, null));
    $repository = $this->diContainer->get(NewsletterListingRepository::class);
    $this->assertSame(2, $repository->getCount((new Handler())->getListingDefinition(['filter' => $filter])));
  }

  public function testLegacySegmentFilterDoesNotDuplicateRows() {
    [$listA, $listB] = $this->createLists(['A', 'B']);
    $both = $this->createWithLists('Both', [$listA, $listB]);
    $this->assertSame([$both->getId()], $this->listIds(['segment' => $listA->getId()], null));
    $this->assertSame([$both->getId()], $this->listIds(['segment' => (string)$listB->getId()], null));
  }

  public function testListOptionsHaveNoCountsAndAreSortedWithAllListsFirst() {
    [$listB, $listA] = $this->createLists(['B list', 'A list']);
    $this->createWithLists('One', [$listB, $listA]);
    $this->createWithLists('Two', [$listB]);
    $this->createLists(['Unused']);

    $options = $this->getListOptions([]);

    $this->assertSame(
      [
        ['label' => 'All Lists', 'value' => ''],
        ['label' => 'A list', 'value' => $listA->getId()],
        ['label' => 'B list', 'value' => $listB->getId()],
      ],
      $options
    );
  }

  public function testListOptionsIgnoreGroupAndOtherTypes() {
    [$listA, $listB, $listC, $listD] = $this->createLists(['A', 'B', 'C', 'D']);
    $this->createWithLists('Draft', [$listA], NewsletterEntity::STATUS_DRAFT);
    $this->createWithLists('Sent', [$listB], NewsletterEntity::STATUS_SENT);
    $trashed = $this->createWithLists('Trashed', [$listC]);
    $trashed->setDeletedAt(new \DateTime());
    $this->entityManager->flush();
    $welcome = $this->createWithLists('Welcome', [$listD], NewsletterEntity::STATUS_ACTIVE);
    $welcome->setType(NewsletterEntity::TYPE_WELCOME);
    $this->entityManager->flush();

    $expected = ['', $listA->getId(), $listB->getId(), $listC->getId()];
    $values = function(array $options): array {
      return array_column($options, 'value');
    };
    foreach ([null, 'all', 'draft', 'sent', 'trash'] as $group) {
      $this->assertSame($expected, $values($this->getListOptions(['group' => $group, 'params' => ['type' => 'standard']])), (string)$group);
    }
  }

  public function testListOptionsForNotificationHistoryAreScopedToParent() {
    [$listA, $listB] = $this->createLists(['A', 'B']);
    $parent = $this->createStandard('Parent', NewsletterEntity::STATUS_ACTIVE);
    $parent->setType(NewsletterEntity::TYPE_NOTIFICATION);
    $otherParent = $this->createStandard('Other parent', NewsletterEntity::STATUS_ACTIVE);
    $otherParent->setType(NewsletterEntity::TYPE_NOTIFICATION);
    $child = $this->createWithLists('Child', [$listA], NewsletterEntity::STATUS_SENT);
    $child->setType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY);
    $child->setParent($parent);
    $otherChild = $this->createWithLists('Other child', [$listB], NewsletterEntity::STATUS_SENT);
    $otherChild->setType(NewsletterEntity::TYPE_NOTIFICATION_HISTORY);
    $otherChild->setParent($otherParent);
    $this->entityManager->flush();

    $options = $this->getListOptions(['params' => ['type' => 'notification_history', 'parentId' => $parent->getId()]]);

    $this->assertSame(['', $listA->getId()], array_column($options, 'value'));
  }

  private function getListOptions(array $listing): array {
    $repository = $this->diContainer->get(NewsletterListingRepository::class);
    return $repository->getFilters((new Handler())->getListingDefinition($listing))['segment'];
  }

  /**
   * @param string[] $names
   * @return SegmentEntity[]
   */
  private function createLists(array $names): array {
    $lists = [];
    foreach ($names as $name) {
      $list = new SegmentEntity($name, SegmentEntity::TYPE_DEFAULT, 'Description');
      $this->entityManager->persist($list);
      $lists[] = $list;
    }
    $this->entityManager->flush();
    return $lists;
  }

  /** @param SegmentEntity[] $lists */
  private function createWithLists(string $subject, array $lists, string $status = NewsletterEntity::STATUS_DRAFT): NewsletterEntity {
    $newsletter = $this->createStandard($subject, $status);
    foreach ($lists as $list) {
      $this->entityManager->persist(new NewsletterSegmentEntity($newsletter, $list));
    }
    $this->entityManager->flush();
    return $newsletter;
  }

  private function createSent(string $subject, string $sentAtUtc): NewsletterEntity {
    $newsletter = $this->createStandard($subject, NewsletterEntity::STATUS_SENT);
    $newsletter->setSentAt(new \DateTime($sentAtUtc, new \DateTimeZone('UTC')));
    $this->entityManager->flush();
    return $newsletter;
  }

  private function createScheduled(string $subject, string $scheduledAtUtc): NewsletterEntity {
    $newsletter = $this->createStandard($subject, NewsletterEntity::STATUS_SCHEDULED);
    $this->addScheduledTask($newsletter, $scheduledAtUtc);
    return $newsletter;
  }

  private function addScheduledTask(NewsletterEntity $newsletter, string $scheduledAtUtc): void {
    $task = new ScheduledTaskEntity();
    $task->setScheduledAt(new \DateTime($scheduledAtUtc, new \DateTimeZone('UTC')));
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->entityManager->persist($task);
    $queue = new SendingQueueEntity();
    $queue->setTask($task);
    $queue->setNewsletter($newsletter);
    $this->entityManager->persist($queue);
    $this->entityManager->flush();
  }

  /** @return string[] Subjects ordered by id */
  private function listSubjects(array $filter): array {
    $repository = $this->diContainer->get(NewsletterListingRepository::class);
    $rows = $repository->getData((new Handler())->getListingDefinition(['filter' => $filter, 'limit' => 100]));
    return array_map(function(NewsletterEntity $newsletter): string {
      return (string)$newsletter->getSubject();
    }, $rows);
  }

  private function withSiteTimezone(string $timezoneString, $gmtOffset, callable $callback): void {
    $previous = [
      'timezone_string' => get_option('timezone_string'),
      'gmt_offset' => get_option('gmt_offset'),
    ];
    update_option('timezone_string', $timezoneString);
    update_option('gmt_offset', $gmtOffset);
    try {
      $callback();
    } finally {
      foreach ($previous as $option => $value) {
        if ($value === false) {
          delete_option($option);
        } else {
          update_option($option, $value);
        }
      }
    }
  }

  private function createStandard(string $subject, string $status): NewsletterEntity {
    return (new Newsletter())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withStatus($status)
      ->withSubject($subject)
      ->create();
  }

  private function createSendingWithTask(string $taskStatus, string $subject, string $type = NewsletterEntity::TYPE_STANDARD): NewsletterEntity {
    return (new Newsletter())
      ->withType($type)
      ->withStatus(NewsletterEntity::STATUS_SENDING)
      ->withSubject($subject)
      ->withSendingQueue(['status' => $taskStatus])
      ->create();
  }

  private function listIds(array $filter, ?string $type, ?string $group = null, string $sortBy = 'id', string $sortOrder = 'asc'): array {
    $repository = $this->diContainer->get(NewsletterListingRepository::class);
    $rows = $repository->getData((new Handler())->getListingDefinition([
      'filter' => $filter,
      'group' => $group,
      'params' => $type ? ['type' => $type] : [],
      'sort_by' => $sortBy,
      'sort_order' => $sortOrder,
      'limit' => 100,
    ]));
    return array_map(function(NewsletterEntity $newsletter): int {
      return (int)$newsletter->getId();
    }, $rows);
  }
}
