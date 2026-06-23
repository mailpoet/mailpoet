<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\CliCommands;

use MailPoet\Cron\CliCommands\DaemonRunner;
use MailPoet\Cron\CliCommands\ExecutionLimitOverride;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\CronWorkerScheduler;
use MailPoet\Cron\Daemon;
use MailPoet\Cron\Workers\UnsubscribeTokens;
use MailPoet\Cron\Workers\WorkersFactory;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Mailer\Mailer;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class DaemonRunnerTest extends \MailPoetTest {
  /** @var DaemonRunner */
  private $daemonRunner;

  /** @var ScheduledTaskFactory */
  private $taskFactory;

  public function _before() {
    parent::_before();
    $this->daemonRunner = $this->diContainer->get(DaemonRunner::class);
    $this->taskFactory = new ScheduledTaskFactory();
    // A full daemon pass instantiates the SendingQueue worker, which resolves the mailer eagerly and
    // throws on a site with no sender configured (a configured site is the realistic case).
    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_PHPMAIL]);
    $settings->set('sender', ['name' => 'Sender', 'address' => 'sender@example.com']);
  }

  public function testItCompletesADueTaskInOnePassWithoutErrors() {
    $task = $this->taskFactory->create(UnsubscribeTokens::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now());
    $taskId = (int)$task->getId();

    $result = $this->daemonRunner->run();

    verify($result['errors'])->equals([]);

    // The daemon clears the entity manager during its pass, so re-fetch the task by ID.
    $scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $processed = $scheduledTasksRepository->findOneById($taskId);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $processed);
    verify($processed->getStatus())->same(ScheduledTaskEntity::STATUS_COMPLETED);
  }

  public function testItSurfacesWorkerErrorsCollectedDuringThePass() {
    // Forcing a real worker to throw mid-pass is brittle, and createDaemon() resets last_error so
    // pre-seeding it would be wiped. Instead substitute a daemon that persists an error exactly the
    // way Daemon::run does (saveDaemonLastError) and assert the real retrieval path surfaces it.
    $persistedError = [['worker' => 'BounceWorker', 'message' => 'Boom']];

    $result = $this->makeDaemonRunnerPersisting($persistedError)->run();

    verify($result['errors'])->equals($persistedError);
  }

  public function testItNormalisesMalformedPersistedErrors() {
    // last_error is persisted untyped, so readErrors() must coerce unexpected shapes. A non-string
    // worker plus a missing message must normalise to empty strings rather than leak raw values.
    $malformedError = [['worker' => 123, 'unexpected' => 'x']];

    $result = $this->makeDaemonRunnerPersisting($malformedError)->run();

    verify($result['errors'])->equals([['worker' => '', 'message' => '']]);
  }

  private function makeDaemonRunnerPersisting(array $persistedError): DaemonRunner {
    $cronHelper = $this->diContainer->get(CronHelper::class);

    return new class(
      $this->diContainer->get(ExecutionLimitOverride::class),
      $cronHelper,
      $this->diContainer->get(CronWorkerScheduler::class),
      $this->diContainer->get(ScheduledTasksRepository::class),
      $this->diContainer->get(EntityManager::class),
      $this->diContainer->get(LoggerFactory::class),
      $this->diContainer->get(WorkersFactory::class),
      $persistedError
    ) extends DaemonRunner {
      /** @var CronHelper */
      private $stubCronHelper;

      /** @var array */
      private $persistedError;

      public function __construct(
        ExecutionLimitOverride $executionLimitOverride,
        CronHelper $cronHelper,
        CronWorkerScheduler $cronWorkerScheduler,
        ScheduledTasksRepository $scheduledTasksRepository,
        EntityManager $entityManager,
        LoggerFactory $loggerFactory,
        WorkersFactory $workersFactory,
        array $persistedError
      ) {
        parent::__construct(
          $executionLimitOverride,
          $cronHelper,
          $cronWorkerScheduler,
          $scheduledTasksRepository,
          $entityManager,
          $loggerFactory,
          $workersFactory
        );
        $this->stubCronHelper = $cronHelper;
        $this->persistedError = $persistedError;
      }

      protected function makeDaemon(): Daemon {
        $cronHelper = $this->stubCronHelper;
        $persistedError = $this->persistedError;
        return new class($cronHelper, $persistedError) extends Daemon {
          /** @var CronHelper */
          private $cronHelper;

          /** @var array */
          private $persistedError;

          // Intentionally bypasses the parent Daemon constructor; this stub only persists an error.
          public function __construct(
            CronHelper $cronHelper,
            array $persistedError
          ) {
            $this->cronHelper = $cronHelper;
            $this->persistedError = $persistedError;
          }

          public function run($settingsDaemonData) {
            $this->cronHelper->saveDaemonLastError($this->persistedError);
          }
        };
      }
    };
  }
}
