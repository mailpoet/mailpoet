<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Mappers;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\AutomationStatistics;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStatisticsStorage;

class AutomationMapper {
  /** @var AutomationStatisticsStorage */
  private $statisticsStorage;

  /** @var AutomationRunStorage */
  private $runStorage;

  public function __construct(
    AutomationStatisticsStorage $statisticsStorage,
    AutomationRunStorage $runStorage
  ) {
    $this->statisticsStorage = $statisticsStorage;
    $this->runStorage = $runStorage;
  }

  public function buildAutomation(Automation $automation, ?AutomationStatistics $statistics = null): array {

    return [
      'id' => $automation->getId(),
      'name' => $automation->getName(),
      'status' => $automation->getStatus(),
      'created_at' => $automation->getCreatedAt()->format(DateTimeImmutable::W3C),
      'updated_at' => $automation->getUpdatedAt()->format(DateTimeImmutable::W3C),
      'activated_at' => $automation->getActivatedAt() ? $automation->getActivatedAt()->format(DateTimeImmutable::W3C) : null,
      'author' => [
        'id' => $automation->getAuthor()->ID,
        'name' => $automation->getAuthor()->display_name,
      ],
      'stats' => $statistics ? $statistics->toArray() : $this->statisticsStorage->getAutomationStats($automation->getId())->toArray(),
      'steps' => array_map(function (Step $step) {
        return [
          'id' => $step->getId(),
          'type' => $step->getType(),
          'key' => $step->getKey(),
          'args' => $step->getArgs(),
          'next_steps' => array_map(function (NextStep $nextStep) {
            return $nextStep->toArray();
          }, $step->getNextSteps()),
          'filters' => $step->getFilters() ? $step->getFilters()->toArray() : null,
        ];
      }, $automation->getSteps()),
      'meta' => (object)$automation->getAllMetas(),
    ];
  }

  /** @param Automation[] $automations */
  public function buildAutomationList(array $automations): array {
    $statistics = $this->statisticsStorage->getAutomationStatisticsForAutomations(...$automations);
    $lastRuns = $this->runStorage->getLastAutomationRunsForAutomations(...$automations);
    return array_map(function (Automation $automation) use ($statistics, $lastRuns) {
      return $this->buildAutomationListItem(
        $automation,
        $statistics[$automation->getId()],
        $lastRuns[$automation->getId()] ?? null
      );
    }, $automations);
  }

  private function buildAutomationListItem(Automation $automation, AutomationStatistics $statistics, ?AutomationRun $lastRun): array {
    return [
      'id' => $automation->getId(),
      'name' => $automation->getName(),
      'status' => $automation->getStatus(),
      'created_at' => $automation->getCreatedAt()->format(DateTimeImmutable::W3C),
      'updated_at' => $automation->getUpdatedAt()->format(DateTimeImmutable::W3C),
      'stats' => $statistics->toArray(),
      'activated_at' => $automation->getActivatedAt() ? $automation->getActivatedAt()->format(DateTimeImmutable::W3C) : null,
      'author' => [
        'id' => $automation->getAuthor()->ID,
        'name' => $automation->getAuthor()->display_name,
      ],
      'last_run' => $lastRun ? [
        'id' => $lastRun->getId(),
        'status' => $lastRun->getStatus(),
        'created_at' => $lastRun->getCreatedAt()->format(DateTimeImmutable::W3C),
        'updated_at' => $lastRun->getUpdatedAt()->format(DateTimeImmutable::W3C),
      ] : null,
    ];
  }
}
