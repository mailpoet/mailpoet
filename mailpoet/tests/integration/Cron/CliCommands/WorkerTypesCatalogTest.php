<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\CliCommands;

use MailPoet\Cron\CliCommands\WorkerTypesCatalog;
use MailPoet\Cron\CronWorkerInterface;
use MailPoet\Cron\Workers\WorkersFactory;
use ReflectionMethod;

class WorkerTypesCatalogTest extends \MailPoetTest {
  /** @var WorkerTypesCatalog */
  private $catalog;

  public function _before() {
    parent::_before();
    $this->catalog = $this->diContainer->get(WorkerTypesCatalog::class);
  }

  public function testItReturnsTheConfiguredColumns() {
    $rows = $this->catalog->getRows();

    verify($rows)->arrayHasKey(0);
    verify(array_keys($rows[0]))->same(WorkerTypesCatalog::FIELDS);
  }

  public function testItSortsTypesAlphabetically() {
    $rows = $this->catalog->getRows();

    $types = array_column($rows, 'type');
    $sorted = $types;
    sort($sorted);
    verify($types)->same($sorted);
  }

  public function testItListsTypesWithoutDuplicates() {
    $types = array_column($this->catalog->getRows(), 'type');

    verify($types)->same(array_values(array_unique($types)));
  }

  public function testSimpleWorkersAreAddableAndStandard() {
    $woocommerceSync = $this->findRow('woocommerce_sync');
    verify($woocommerceSync['addable'])->true();
    verify($woocommerceSync['mailing'])->false();
    verify($woocommerceSync['schedule_automatically'])->false();
    verify($woocommerceSync['supports_multiple_instances'])->false();

    $logCleanup = $this->findRow('log_cleanup');
    verify($logCleanup['addable'])->true();
    verify($logCleanup['mailing'])->false();
    verify($logCleanup['schedule_automatically'])->true();
    verify($logCleanup['supports_multiple_instances'])->false();

    // Built by createStatsNotificationsWorkerForAutomatedEmails, whose name does not end in "Worker".
    $automatedEmails = $this->findRow('stats_notification_automated_emails');
    verify($automatedEmails['addable'])->true();
    verify($automatedEmails['mailing'])->false();
  }

  public function testMailingTypesAppearOnceWithMailingFlags() {
    $rows = $this->catalog->getRows();

    foreach (['sending', 'stats_notification'] as $type) {
      $matching = array_values(array_filter($rows, function (array $row) use ($type): bool {
        return $row['type'] === $type;
      }));
      verify($matching)->arrayCount(1);
      verify($matching[0]['mailing'])->true();
      verify($matching[0]['addable'])->false();
      verify($matching[0]['schedule_automatically'])->false();
      verify($matching[0]['supports_multiple_instances'])->false();
    }
  }

  public function testGetAddableTypesReturnsStandardTypesWithoutMailingOnes() {
    $addable = $this->catalog->getAddableTypes();

    // Sorted and unique.
    $sorted = $addable;
    sort($sorted);
    verify($addable)->same($sorted);
    verify($addable)->same(array_values(array_unique($addable)));

    // Excludes the mailing types.
    foreach (WorkerTypesCatalog::MAILING_TYPES as $mailingType) {
      verify(in_array($mailingType, $addable, true))->false();
    }

    // Matches exactly the rows flagged addable in the catalog.
    $expected = array_column(array_filter($this->catalog->getRows(), function (array $row): bool {
      return $row['addable'] === true;
    }), 'type');
    sort($expected);
    verify($addable)->same($expected);

    // A known standard type is present.
    verify(in_array('log_cleanup', $addable, true))->true();
  }

  public function testItListsExactlyTheKnownTaskTypes() {
    // Hardcoded ground truth on purpose: adding or removing a worker must be a deliberate change here.
    // Deriving the expected list from the factory (as this test used to) only mirrors the implementation,
    // so it would pass even if the catalog and the factory drifted together.
    $expected = [
      'authorized_email_addresses_check',
      'automation_abandoned_cart',
      'backfill_engagement_data',
      'bounce',
      'bounce_task_subscribers_cleanup',
      'bulk_confirmation_email_resend',
      'export_files_cleanup',
      'inactive_subscribers_maintenance',
      'log_cleanup',
      'mixpanel',
      'newsletter_templates_thumbnails',
      'premium_key_check',
      'schedule_re_engagement_email',
      'sending',
      'sending_queue_body_cleanup',
      'sending_service_key_check',
      'sending_task_subscribers_cleanup',
      'statistics_export',
      'stats_notification',
      'stats_notification_automated_emails',
      'subscriber_limit_notification',
      'subscriber_link_tokens',
      'subscribers_count_cache_recalculation',
      'subscribers_engagement_score',
      'subscribers_last_engagement',
      'subscribers_segments_count_sync',
      'subscribers_stats_report',
      'tracks',
      'unconfirmed_subscribers_cleanup',
      'unsubscribe_tokens',
      'woocommerce_past_orders',
      'woocommerce_sync',
    ];
    sort($expected);

    $types = array_column($this->catalog->getRows(), 'type');
    sort($types);

    verify($types)->same($expected);
  }

  public function testEveryFactoryCreateMethodResolvesToAManagedWorker() {
    // Guards the reflection-based discovery in WorkerTypesCatalog: every argument-less create*() on the
    // factory must either be a known mailing method or resolve to a CronWorkerInterface the catalog
    // exposes. A worker added with a different naming or return convention fails here instead of
    // silently disappearing from `wp mailpoet cron`.
    $factory = $this->diContainer->get(WorkersFactory::class);
    $exposedTypes = $this->catalog->getTypes();

    foreach (get_class_methods($factory) as $method) {
      if (strpos($method, 'create') !== 0) {
        continue;
      }
      if (in_array($method, WorkerTypesCatalog::MAILING_FACTORY_METHODS, true)) {
        continue;
      }

      // A create*() needing arguments is skipped by the catalog, hiding its worker from the CLI.
      verify((new ReflectionMethod($factory, $method))->getNumberOfRequiredParameters())->same(0);

      $worker = $factory->{$method}();
      $this->assertInstanceOf(
        CronWorkerInterface::class,
        $worker,
        sprintf("%s() must return a CronWorkerInterface or be listed in WorkerTypesCatalog::MAILING_FACTORY_METHODS.", $method)
      );
      verify(in_array($worker->getTaskType(), $exposedTypes, true))->true();
    }
  }

  /**
   * @return array{type: string, addable: bool, schedule_automatically: bool, supports_multiple_instances: bool, mailing: bool}
   */
  private function findRow(string $type): array {
    foreach ($this->catalog->getRows() as $row) {
      if ($row['type'] === $type) {
        return $row;
      }
    }
    $this->fail("No catalog row found for type '{$type}'.");
  }
}
