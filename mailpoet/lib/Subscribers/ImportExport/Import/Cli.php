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
use MailPoet\Subscribers\ImportExport\ImportExportFactory;
use MailPoet\Subscribers\ImportExport\ImportExportRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tags\TagRepository;
use MailPoet\Util\SpreadsheetCellFormatter;
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
    'tracking_consent',
    'tracking_consent_method',
    'tracking_consent_copy',
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

  private const UTF8_BOM = "\xEF\xBB\xBF";

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

  /** @var array<string, string>|null Lowercased exported column label => canonical field name. */
  private $exportedLabelMap = null;

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
          'description' => 'Path to the CSV file. The header row must use MailPoet field names (email, first_name, last_name, subscribed_ip, created_at, confirmed_at, confirmed_ip, tracking_consent, tracking_consent_method, tracking_consent_copy), existing custom field names, or the column labels MailPoet\'s own export writes. An "email" column is required. tracking_consent accepts granted, denied or unknown; a blank cell leaves the stored value alone.',
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

    $rowsRead = $totals['rows'] + $totals['skipped'];
    $skippedNotice = '';
    if ($totals['skipped'] > 0) {
      $skippedTemplate = $totals['skipped'] === 1
        ? ' %d row was skipped because its column count did not match the header.'
        : ' %d rows were skipped because their column count did not match the header.';
      $skippedNotice = sprintf($skippedTemplate, $totals['skipped']);
    }

    if ($options['dry_run']) {
      WP_CLI::success(sprintf(
        'Dry run: %d rows read, %d subscribers with a valid email. Nothing was written.%s',
        $rowsRead,
        $totals['valid'],
        $skippedNotice
      ));
      return;
    }

    WP_CLI::success(sprintf(
      'Import finished: %d created, %d updated (out of %d rows).%s',
      $totals['created'],
      $totals['updated'],
      $rowsRead,
      $skippedNotice
    ));
  }

  /**
   * Parses the CSV file and imports the subscribers. Throws on invalid input.
   * Free of any WP-CLI dependency so it can be unit/integration tested.
   *
   * @param array{segments: string[], status: string, existing_status: string, update_existing: bool, tags: string[], batch_size: int, dry_run: bool} $options
   * @param callable(string): void|null $logger
   * @return array{created: int, updated: int, valid: int, rows: int, skipped: int}
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

    // generateCSV starts the file with a UTF-8 BOM so Excel detects the encoding. Skip it
    // before parsing rather than trimming it off the first column afterwards: a BOM sitting
    // in front of a quoted first column stops fgetcsv reading that field as enclosed, so
    // the quotes would survive into the column name.
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread -- Reading the local CSV the user pointed the command at, through the handle opened above.
    if (fread($handle, 3) !== self::UTF8_BOM) {
      rewind($handle);
    }

    try {
      // Escaping is disabled so quotes are read as RFC 4180 doubled quotes, the same
      // convention Export::writeCSVRow writes with. PHP's proprietary backslash escape
      // would misread a backslash sitting directly before a quote.
      $header = fgetcsv($handle, 0, ',', '"', '');
      if (!is_array($header)) {
        throw new \RuntimeException('The CSV file is empty or has no header row.');
      }
      $header = array_map([$this, 'unformatCell'], $header);
      $columns = $this->buildColumns($header, $log);

      $headerColumnCount = count($header);
      $totals = ['created' => 0, 'updated' => 0, 'valid' => 0, 'rows' => 0, 'skipped' => 0];
      $batch = [];
      $lineNumber = 1; // header is line 1
      while (is_array($row = fgetcsv($handle, 0, ',', '"', ''))) {
        $lineNumber++;
        if ($row === [null]) {
          continue; // skip blank lines
        }
        // fgetcsv does not pad short rows or trim long ones. A row whose column
        // count differs from the header would misalign the per-column arrays built
        // in Import (a value landing on the wrong subscriber), so skip it and warn
        // rather than guessing which columns are missing.
        if (count($row) !== $headerColumnCount) {
          $totals['skipped']++;
          $log(sprintf('  Skipped line %d: expected %d column(s) but found %d.', $lineNumber, $headerColumnCount, count($row)));
          continue;
        }
        $row = array_map([$this, 'unformatCell'], $row);
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
   * @param array{created: int, updated: int, valid: int, rows: int, skipped: int} $totals
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
   * MailPoet's own export prefixes a value a spreadsheet would read as a formula with
   * an apostrophe. Take it back off so exporting and re-importing returns the original
   * value, and so a column heading still matches its custom field.
   */
  private function unformatCell(?string $value): ?string {
    $unformatted = SpreadsheetCellFormatter::unformat($value);
    return is_string($unformatted) ? $unformatted : null;
  }

  /**
   * Maps each CSV header to a subscriber field or custom field id.
   *
   * @param array<int, string|null> $header
   * @param callable(string):void|null $log
   * @return array<string|int, array{index: int}>
   * @throws \RuntimeException
   */
  private function buildColumns(array $header, ?callable $log = null): array {
    $columns = [];
    $unknown = [];
    $ignored = [];
    $duplicates = [];
    $namesByField = [];
    foreach ($header as $index => $name) {
      $name = trim((string)$name);
      if ($name === '') {
        continue;
      }
      $field = $this->resolveField($name);
      if ($field === null) {
        if ($this->isExportOnlyColumn($name)) {
          $ignored[] = $name;
          continue;
        }
        $unknown[] = $name;
        continue;
      }
      if (isset($columns[$field])) {
        $duplicates[$field] = array_merge($namesByField[$field], [$name]);
        continue;
      }
      $namesByField[$field] = [$name];
      $columns[$field] = ['index' => $index];
    }

    if ($unknown) {
      throw new \RuntimeException(sprintf(
        'Unrecognized CSV column(s): %s. Use MailPoet field names (%s) or an existing custom field name.',
        implode(', ', $unknown),
        implode(', ', self::BASE_FIELDS)
      ));
    }

    if ($duplicates) {
      $details = array_map(function (array $names): string {
        return implode(', ', $names);
      }, $duplicates);
      throw new \RuntimeException(sprintf(
        'Duplicate CSV column(s) mapping to the same field: %s. Each field may only appear once in the header.',
        implode('; ', $details)
      ));
    }

    if (!isset($columns['email'])) {
      throw new \RuntimeException('The CSV file must contain an "email" column.');
    }

    if ($ignored && $log) {
      $log(sprintf(
        'Ignored column(s) MailPoet exports but cannot import: %s. Use --segments to choose the lists, and --status / --existing-status to choose the subscription status.',
        implode(', ', $ignored)
      ));
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
    // A custom field of the same name wins above, so this only catches the labels
    // MailPoet's own export writes, such as "First name" for first_name.
    $field = $this->getExportedLabelMap()[strtolower($header)] ?? null;
    return in_array($field, self::BASE_FIELDS, true) ? $field : null;
  }

  /**
   * Columns MailPoet's export writes that hold no importable field: the export-only
   * fields, and the "List" column, whose lists are chosen with --segments instead.
   */
  private function isExportOnlyColumn(string $header): bool {
    $normalized = strtolower($header);
    if ($normalized === strtolower(__('List', 'mailpoet'))) {
      return true;
    }
    $field = $this->getExportedLabelMap()[$normalized] ?? null;
    return $field !== null && !in_array($field, self::BASE_FIELDS, true);
  }

  /**
   * MailPoet's export writes translated column labels rather than canonical field names,
   * so accept both. Built from the same source the exporter writes its header from.
   *
   * @return array<string, string> Lowercased exported label => canonical field name.
   */
  private function getExportedLabelMap(): array {
    if ($this->exportedLabelMap === null) {
      $map = [];
      $exportFactory = new ImportExportFactory(ImportExportFactory::EXPORT_ACTION);
      foreach ($exportFactory->getSubscriberFields() as $field => $label) {
        $map[strtolower((string)$label)] = (string)$field;
      }
      $this->exportedLabelMap = $map;
    }
    return $this->exportedLabelMap;
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
