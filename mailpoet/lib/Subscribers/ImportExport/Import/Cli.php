<?php declare(strict_types = 1);

namespace MailPoet\Subscribers\ImportExport\Import;

use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Options\NewsletterOptionsRepository;
use MailPoet\Segments\SegmentSaveController;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Segments\WP as SegmentsWP;
use MailPoet\Services\Validator;
use MailPoet\Subscribers\ImportExport\ImportExportRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tags\TagRepository;
use WP_CLI;

class Cli {
  /** Subscriber fields that can appear as CSV columns, matched by their canonical name. */
  private const BASE_FIELDS = [
    'email',
    'first_name',
    'last_name',
    'subscribed_ip',
    'created_at',
    'confirmed_at',
    'confirmed_ip',
  ];

  private const NEW_SUBSCRIBER_STATUSES = [
    SubscriberEntity::STATUS_SUBSCRIBED,
    SubscriberEntity::STATUS_UNCONFIRMED,
    SubscriberEntity::STATUS_UNSUBSCRIBED,
    SubscriberEntity::STATUS_INACTIVE,
  ];

  private const EXISTING_SUBSCRIBER_STATUSES = [
    Import::STATUS_DONT_UPDATE,
    SubscriberEntity::STATUS_SUBSCRIBED,
    SubscriberEntity::STATUS_UNSUBSCRIBED,
    SubscriberEntity::STATUS_INACTIVE,
  ];

  private const DEFAULT_BATCH_SIZE = 2000;

  /** @var SegmentsWP */
  private $wpSegment;

  /** @var CustomFieldsRepository */
  private $customFieldsRepository;

  /** @var ImportExportRepository */
  private $importExportRepository;

  /** @var NewsletterOptionsRepository */
  private $newsletterOptionsRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var TagRepository */
  private $tagRepository;

  /** @var Validator */
  private $validator;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SegmentSaveController */
  private $segmentSaveController;

  public function __construct(
    SegmentsWP $wpSegment,
    CustomFieldsRepository $customFieldsRepository,
    ImportExportRepository $importExportRepository,
    NewsletterOptionsRepository $newsletterOptionsRepository,
    SubscribersRepository $subscribersRepository,
    TagRepository $tagRepository,
    Validator $validator,
    SegmentsRepository $segmentsRepository,
    SegmentSaveController $segmentSaveController
  ) {
    $this->wpSegment = $wpSegment;
    $this->customFieldsRepository = $customFieldsRepository;
    $this->importExportRepository = $importExportRepository;
    $this->newsletterOptionsRepository = $newsletterOptionsRepository;
    $this->subscribersRepository = $subscribersRepository;
    $this->tagRepository = $tagRepository;
    $this->validator = $validator;
    $this->segmentsRepository = $segmentsRepository;
    $this->segmentSaveController = $segmentSaveController;
  }

  public function initialize(): void {
    if (!class_exists(WP_CLI::class)) {
      return;
    }

    WP_CLI::add_command('mailpoet import', [$this, 'import'], [
      'shortdesc' => 'Imports subscribers into MailPoet from a CSV file',
      'synopsis' => [
        [
          'type' => 'positional',
          'name' => 'file',
          'description' => 'Path to the CSV file. The header row must use MailPoet field names (email, first_name, last_name, subscribed_ip, created_at, confirmed_at, confirmed_ip) or existing custom field names. An "email" column is required.',
          'optional' => false,
        ],
        [
          'type' => 'assoc',
          'name' => 'segments',
          'description' => 'Comma-separated segment IDs or names to add subscribers to. Names that do not exist are created.',
          'optional' => true,
        ],
        [
          'type' => 'assoc',
          'name' => 'status',
          'description' => 'Status for newly created subscribers.',
          'optional' => true,
          'default' => SubscriberEntity::STATUS_SUBSCRIBED,
          'options' => self::NEW_SUBSCRIBER_STATUSES,
        ],
        [
          'type' => 'flag',
          'name' => 'update-existing',
          'description' => 'Update the details of subscribers that already exist.',
          'optional' => true,
        ],
        [
          'type' => 'assoc',
          'name' => 'existing-status',
          'description' => 'Status to set on existing subscribers.',
          'optional' => true,
          'default' => Import::STATUS_DONT_UPDATE,
          'options' => self::EXISTING_SUBSCRIBER_STATUSES,
        ],
        [
          'type' => 'assoc',
          'name' => 'tags',
          'description' => 'Comma-separated tag names to assign to imported subscribers. Tags are created if they do not exist.',
          'optional' => true,
        ],
        [
          'type' => 'assoc',
          'name' => 'batch-size',
          'description' => 'Number of subscribers to process per batch.',
          'optional' => true,
          'default' => self::DEFAULT_BATCH_SIZE,
        ],
        [
          'type' => 'flag',
          'name' => 'dry-run',
          'description' => 'Parse and validate the file and report what would be imported without writing anything.',
          'optional' => true,
        ],
      ],
    ]);
  }

  /**
   * WP-CLI entry point. Translates CLI input/output; the work happens in run().
   *
   * @param string[] $args
   * @param array<string, string> $assocArgs
   */
  public function import(array $args, array $assocArgs): void {
    $options = [
      'segments' => $this->parseList((string)($assocArgs['segments'] ?? '')),
      'status' => (string)($assocArgs['status'] ?? SubscriberEntity::STATUS_SUBSCRIBED),
      'existing_status' => (string)($assocArgs['existing-status'] ?? Import::STATUS_DONT_UPDATE),
      'update_existing' => !empty($assocArgs['update-existing']),
      'tags' => $this->parseList((string)($assocArgs['tags'] ?? '')),
      'batch_size' => (int)($assocArgs['batch-size'] ?? self::DEFAULT_BATCH_SIZE),
      'dry_run' => !empty($assocArgs['dry-run']),
    ];

    try {
      $totals = $this->run((string)($args[0] ?? ''), $options, function (string $message): void {
        WP_CLI::log($message);
      });
    } catch (\Exception $e) {
      WP_CLI::error($e->getMessage());
      return;
    }

    if ($options['dry_run']) {
      WP_CLI::success(sprintf(
        'Dry run: %d rows read, %d subscribers with a valid email. Nothing was written.',
        $totals['rows'],
        $totals['valid']
      ));
      return;
    }

    WP_CLI::success(sprintf(
      'Import finished: %d created, %d updated (out of %d rows).',
      $totals['created'],
      $totals['updated'],
      $totals['rows']
    ));
  }

  /**
   * Parses the CSV file and imports the subscribers. Throws on invalid input.
   * Free of any WP-CLI dependency so it can be unit/integration tested.
   *
   * @param array{segments: string[], status: string, existing_status: string, update_existing: bool, tags: string[], batch_size: int, dry_run: bool} $options
   * @param callable(string): void|null $logger
   * @return array{created: int, updated: int, valid: int, rows: int}
   * @throws \RuntimeException
   */
  public function run(string $file, array $options, ?callable $logger = null): array {
    $log = $logger ?? function (string $message): void {
    };

    if (!is_readable($file)) {
      throw new \RuntimeException(sprintf('File "%s" does not exist or is not readable.', $file));
    }
    if (!in_array($options['status'], self::NEW_SUBSCRIBER_STATUSES, true)) {
      throw new \RuntimeException(sprintf('Invalid status "%s". Allowed: %s.', $options['status'], implode(', ', self::NEW_SUBSCRIBER_STATUSES)));
    }
    if (!in_array($options['existing_status'], self::EXISTING_SUBSCRIBER_STATUSES, true)) {
      throw new \RuntimeException(sprintf('Invalid existing status "%s". Allowed: %s.', $options['existing_status'], implode(', ', self::EXISTING_SUBSCRIBER_STATUSES)));
    }
    if ($options['batch_size'] < 1) {
      throw new \RuntimeException('Batch size must be a positive integer.');
    }

    $segmentIds = $this->resolveSegments($options['segments'], $options['dry_run'], $log);

    $handle = fopen($file, 'r');
    if ($handle === false) {
      throw new \RuntimeException(sprintf('Unable to open file "%s".', $file));
    }

    try {
      $header = fgetcsv($handle, 0, ',', '"', '\\');
      if (!is_array($header)) {
        throw new \RuntimeException('The CSV file is empty or has no header row.');
      }
      $columns = $this->buildColumns($header);

      $totals = ['created' => 0, 'updated' => 0, 'valid' => 0, 'rows' => 0];
      $batch = [];
      while (is_array($row = fgetcsv($handle, 0, ',', '"', '\\'))) {
        if ($row === [null]) {
          continue; // skip blank lines
        }
        $totals['rows']++;
        $batch[] = $row;
        if (count($batch) >= $options['batch_size']) {
          $this->processBatch($batch, $columns, $segmentIds, $options, $totals, $log);
          $batch = [];
        }
      }
      if ($batch) {
        $this->processBatch($batch, $columns, $segmentIds, $options, $totals, $log);
      }
    } finally {
      fclose($handle);
    }

    return $totals;
  }

  /**
   * @param array<int, array<int, string|null>> $batch
   * @param array<string|int, array{index: int}> $columns
   * @param int[] $segmentIds
   * @param array{segments: string[], status: string, existing_status: string, update_existing: bool, tags: string[], batch_size: int, dry_run: bool} $options
   * @param array{created: int, updated: int, valid: int, rows: int} $totals
   * @param callable(string): void $log
   */
  private function processBatch(
    array $batch,
    array $columns,
    array $segmentIds,
    array $options,
    array &$totals,
    callable $log
  ): void {
    $data = [
      'subscribers' => $batch,
      'columns' => $columns,
      'segments' => $segmentIds,
      'tags' => $options['tags'],
      'timestamp' => time(),
      'newSubscribersStatus' => $options['status'],
      'existingSubscribersStatus' => $options['existing_status'],
      'updateSubscribers' => $options['update_existing'],
    ];

    $import = new Import(
      $this->wpSegment,
      $this->customFieldsRepository,
      $this->importExportRepository,
      $this->newsletterOptionsRepository,
      $this->subscribersRepository,
      $this->tagRepository,
      $this->validator,
      $data
    );

    if ($options['dry_run']) {
      $valid = $import->validateSubscribersData($import->subscribersData);
      $emails = is_array($valid) && isset($valid['email']) ? $valid['email'] : [];
      $totals['valid'] += count($emails);
      return;
    }

    $result = $import->process();
    $totals['created'] += (int)$result['created'];
    $totals['updated'] += (int)$result['updated'];
    $log(sprintf('  Batch of %d rows: %d created, %d updated.', count($batch), $result['created'], $result['updated']));
  }

  /**
   * Maps each CSV header to a subscriber field or custom field id.
   *
   * @param array<int, string|null> $header
   * @return array<string|int, array{index: int}>
   * @throws \RuntimeException
   */
  private function buildColumns(array $header): array {
    $columns = [];
    $unknown = [];
    foreach ($header as $index => $name) {
      $name = trim((string)$name);
      if ($name === '') {
        continue;
      }
      $field = $this->resolveField($name);
      if ($field === null) {
        $unknown[] = $name;
        continue;
      }
      $columns[$field] = ['index' => $index];
    }

    if ($unknown) {
      throw new \RuntimeException(sprintf(
        'Unrecognized CSV column(s): %s. Use MailPoet field names (%s) or an existing custom field name.',
        implode(', ', $unknown),
        implode(', ', self::BASE_FIELDS)
      ));
    }

    if (!isset($columns['email'])) {
      throw new \RuntimeException('The CSV file must contain an "email" column.');
    }

    return $columns;
  }

  /**
   * @return string|int|null Field name, custom field id, or null when unrecognized.
   */
  private function resolveField(string $header) {
    if (in_array(strtolower($header), self::BASE_FIELDS, true)) {
      return strtolower($header);
    }
    $customField = $this->customFieldsRepository->findOneBy(['name' => $header]);
    if ($customField instanceof CustomFieldEntity) {
      return $customField->getId();
    }
    return null;
  }

  /**
   * Resolves segment IDs/names to IDs. Unknown names are created unless this is a dry run.
   *
   * @param string[] $segments
   * @param bool $dryRun
   * @param callable(string): void $log
   * @return int[]
   * @throws \RuntimeException
   */
  private function resolveSegments(array $segments, bool $dryRun, callable $log): array {
    $ids = [];
    foreach ($segments as $segment) {
      if (ctype_digit($segment)) {
        $entity = $this->segmentsRepository->findOneById((int)$segment);
        if (!$entity instanceof SegmentEntity) {
          throw new \RuntimeException(sprintf('Segment with ID "%s" does not exist.', $segment));
        }
        $ids[] = (int)$segment;
        continue;
      }
      $entity = $this->segmentsRepository->findOneBy(['name' => $segment, 'type' => SegmentEntity::TYPE_DEFAULT]);
      if ($entity instanceof SegmentEntity) {
        $ids[] = (int)$entity->getId();
        continue;
      }
      if ($dryRun) {
        $log(sprintf('Segment "%s" would be created.', $segment));
        continue;
      }
      $entity = $this->segmentSaveController->save(['name' => $segment]);
      $log(sprintf('Created segment "%s" (ID %d).', $segment, (int)$entity->getId()));
      $ids[] = (int)$entity->getId();
    }
    return array_values(array_unique($ids));
  }

  /**
   * @return string[]
   */
  private function parseList(string $value): array {
    if (trim($value) === '') {
      return [];
    }
    return array_values(array_filter(array_map('trim', explode(',', $value)), function (string $item): bool {
      return $item !== '';
    }));
  }
}
