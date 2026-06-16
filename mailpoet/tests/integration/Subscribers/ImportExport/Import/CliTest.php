<?php declare(strict_types = 1);

namespace MailPoet\Subscribers\ImportExport\Import;

use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Subscribers\SubscriberCustomFieldRepository;
use MailPoet\Subscribers\SubscribersRepository;

class CliTest extends \MailPoetTest {
  /** @var Cli */
  private $cli;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var CustomFieldsRepository */
  private $customFieldsRepository;

  /** @var SubscriberCustomFieldRepository */
  private $subscriberCustomFieldRepository;

  /** @var string[] */
  private $tempFiles = [];

  private const DEFAULT_OPTIONS = [
    'segments' => [],
    'status' => SubscriberEntity::STATUS_SUBSCRIBED,
    'existing_status' => Import::STATUS_DONT_UPDATE,
    'update_existing' => false,
    'tags' => [],
    'batch_size' => 2000,
    'dry_run' => false,
  ];

  public function _before(): void {
    parent::_before();
    $this->cli = $this->diContainer->get(Cli::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->customFieldsRepository = $this->diContainer->get(CustomFieldsRepository::class);
    $this->subscriberCustomFieldRepository = $this->diContainer->get(SubscriberCustomFieldRepository::class);
  }

  public function testItImportsNewSubscribersAndCreatesSegment(): void {
    $file = $this->writeCsv([
      ['email', 'first_name', 'last_name'],
      ['Adam@Example.com', 'Adam', 'Smith'],
      ['mary@example.com', 'Mary', 'Jane'],
    ]);

    $totals = $this->cli->run($file, ['segments' => ['CLI Import List']] + self::DEFAULT_OPTIONS);

    $this->assertSame(2, $totals['created']);
    $this->assertSame(0, $totals['updated']);

    $adam = $this->subscribersRepository->findOneBy(['email' => 'adam@example.com']);
    $this->assertInstanceOf(SubscriberEntity::class, $adam);
    $this->assertSame('Adam', $adam->getFirstName());
    $this->assertSame(SubscriberEntity::STATUS_SUBSCRIBED, $adam->getStatus());

    $segment = $this->segmentsRepository->findOneBy(['name' => 'CLI Import List', 'type' => SegmentEntity::TYPE_DEFAULT]);
    $this->assertInstanceOf(SegmentEntity::class, $segment);
    $this->assertCount(1, array_filter($adam->getSegments()->toArray(), function (SegmentEntity $s) use ($segment): bool {
      return $s->getId() === $segment->getId();
    }));
  }

  public function testItImportsIntoExistingSegmentById(): void {
    $segment = $this->segmentsRepository->createOrUpdate('Existing CLI List');
    $file = $this->writeCsv([
      ['email'],
      ['solo@example.com'],
    ]);

    $totals = $this->cli->run($file, ['segments' => [(string)$segment->getId()]] + self::DEFAULT_OPTIONS);

    $this->assertSame(1, $totals['created']);
    $subscriber = $this->subscribersRepository->findOneBy(['email' => 'solo@example.com']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
  }

  public function testItMapsCustomFieldByName(): void {
    $customField = $this->customFieldsRepository->createOrUpdate([
      'name' => 'Country',
      'type' => CustomFieldEntity::TYPE_TEXT,
    ]);
    $this->assertInstanceOf(CustomFieldEntity::class, $customField);

    $file = $this->writeCsv([
      ['email', 'Country'],
      ['traveler@example.com', 'France'],
    ]);

    $this->cli->run($file, self::DEFAULT_OPTIONS);

    $subscriber = $this->subscribersRepository->findOneBy(['email' => 'traveler@example.com']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $value = $this->subscriberCustomFieldRepository->findOneBy([
      'subscriber' => $subscriber,
      'customField' => $customField,
    ]);
    $this->assertNotNull($value);
    $this->assertSame('France', $value->getValue());
  }

  public function testItUpdatesExistingSubscribersWhenFlagIsSet(): void {
    $this->cli->run($this->writeCsv([
      ['email', 'first_name'],
      ['repeat@example.com', 'Original'],
    ]), self::DEFAULT_OPTIONS);

    $totals = $this->cli->run($this->writeCsv([
      ['email', 'first_name'],
      ['repeat@example.com', 'Updated'],
    ]), ['update_existing' => true] + self::DEFAULT_OPTIONS);

    $this->assertSame(0, $totals['created']);
    $this->assertSame(1, $totals['updated']);
    $this->subscribersRepository->refreshAll();
    $subscriber = $this->subscribersRepository->findOneBy(['email' => 'repeat@example.com']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $this->assertSame('Updated', $subscriber->getFirstName());
  }

  public function testDryRunDoesNotWriteAnything(): void {
    $file = $this->writeCsv([
      ['email'],
      ['ghost@example.com'],
      ['invalid-email'],
    ]);

    $totals = $this->cli->run($file, ['dry_run' => true, 'segments' => ['Phantom List']] + self::DEFAULT_OPTIONS);

    $this->assertSame(2, $totals['rows']);
    $this->assertSame(1, $totals['valid']);
    $this->assertSame(0, $totals['created']);
    $this->assertNull($this->subscribersRepository->findOneBy(['email' => 'ghost@example.com']));
    $this->assertNull($this->segmentsRepository->findOneBy(['name' => 'Phantom List', 'type' => SegmentEntity::TYPE_DEFAULT]));
  }

  public function testItThrowsWhenEmailColumnMissing(): void {
    $file = $this->writeCsv([
      ['first_name', 'last_name'],
      ['No', 'Email'],
    ]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('must contain an "email" column');
    $this->cli->run($file, self::DEFAULT_OPTIONS);
  }

  public function testItThrowsOnUnrecognizedColumn(): void {
    $file = $this->writeCsv([
      ['email', 'favourite_colour'],
      ['picky@example.com', 'blue'],
    ]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Unrecognized CSV column(s): favourite_colour');
    $this->cli->run($file, self::DEFAULT_OPTIONS);
  }

  public function testItThrowsOnCaseInsensitiveDuplicateColumn(): void {
    $file = $this->writeCsv([
      ['Email', 'email'],
      ['first@example.com', 'second@example.com'],
    ]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Duplicate CSV column(s) mapping to the same field: Email, email');
    $this->cli->run($file, self::DEFAULT_OPTIONS);
  }

  public function testItThrowsOnDuplicateCustomFieldColumn(): void {
    $customField = $this->customFieldsRepository->createOrUpdate([
      'name' => 'Country',
      'type' => CustomFieldEntity::TYPE_TEXT,
    ]);
    $this->assertInstanceOf(CustomFieldEntity::class, $customField);

    $file = $this->writeCsv([
      ['email', 'Country', 'Country'],
      ['traveler@example.com', 'France', 'Spain'],
    ]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Duplicate CSV column(s) mapping to the same field');
    $this->cli->run($file, self::DEFAULT_OPTIONS);
  }

  public function testItThrowsForMissingFile(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('does not exist or is not readable');
    $this->cli->run('/tmp/does-not-exist-' . bin2hex(random_bytes(6)) . '.csv', self::DEFAULT_OPTIONS); // phpcs:ignore
  }

  /**
   * @param array<int, array<int, string>> $rows
   */
  private function writeCsv(array $rows): string {
    $path = tempnam(sys_get_temp_dir(), 'mailpoet-import-');
    $this->assertIsString($path);
    $this->tempFiles[] = $path;
    $handle = fopen($path, 'w');
    $this->assertNotFalse($handle);
    foreach ($rows as $row) {
      fputcsv($handle, $row, ',', '"', '\\');
    }
    fclose($handle);
    return $path;
  }

  public function _after(): void {
    parent::_after();
    foreach ($this->tempFiles as $file) {
      if (is_file($file)) {
        unlink($file);
      }
    }
    $this->tempFiles = [];
  }
}
