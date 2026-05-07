<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine\Builder;

use MailPoet\Automation\Engine\Builder\CreateAutomationFromTemplateController;
use MailPoet\Automation\Engine\Builder\DeleteAutomationController;
use MailPoet\Automation\Engine\Builder\DuplicateAutomationController;
use MailPoet\Automation\Engine\Builder\UpdateAutomationController;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationTemplate;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Test\DataFactories\Automation as AutomationFactory;

class AutomationLifecycleHooksTest extends \MailPoetTest {
  /** @var AutomationStorage */
  private $automationStorage;

  /** @var array{automation_id: int, status: string, previous_status: string|null}|null */
  private $saveEvent = null;

  /** @var array{automation_id: int, status: string, previous_status: string, action_args?: array|null, previous_action_args?: array|null}|null */
  private $updateEvent = null;

  /** @var array{automation_id: int, status: string}|null */
  private $deleteEvent = null;

  /** @var array{automation_id: int, source_automation_id: int, status: string}|null */
  private $duplicateEvent = null;

  /** @var array{automation_id: int, template_slug: string, status: string}|null */
  private $templateEvent = null;

  /** @var array{automation_id: int, status: string}|null */
  private $createEvent = null;

  /** @var array<string, callable> */
  private $hookCallbacks = [];

  public function _before(): void {
    $this->automationStorage = $this->diContainer->get(AutomationStorage::class);
    $this->automationStorage->truncate();
    $this->saveEvent = null;
    $this->updateEvent = null;
    $this->deleteEvent = null;
    $this->duplicateEvent = null;
    $this->templateEvent = null;
    $this->createEvent = null;
    $this->hookCallbacks = [];
    $this->registerHooks();
  }

  public function _after(): void {
    $wp = $this->diContainer->get(\MailPoet\Automation\Engine\WordPress::class);
    foreach ($this->hookCallbacks as $hook => $callback) {
      $wp->removeAction($hook, $callback);
    }
    $this->automationStorage->truncate();
  }

  public function testItFiresUpdateHooksWithPreviousAutomationState(): void {
    $automation = (new AutomationFactory())
      ->withSomeoneSubscribesTrigger()
      ->withDelayAction()
      ->withStatusActive()
      ->create();

    $updated = $this->diContainer->get(UpdateAutomationController::class)->updateAutomation($automation->getId(), [
      'status' => Automation::STATUS_DRAFT,
    ]);

    $updateEvent = $this->getUpdateEvent();
    $saveEvent = $this->getSaveEvent();
    $this->assertSame($updated->getId(), $updateEvent['automation_id']);
    $this->assertSame(Automation::STATUS_DRAFT, $updateEvent['status']);
    $this->assertSame(Automation::STATUS_ACTIVE, $updateEvent['previous_status']);
    $this->assertSame($updated->getId(), $saveEvent['automation_id']);
    $this->assertSame(Automation::STATUS_ACTIVE, $saveEvent['previous_status']);
  }

  public function testItFiresUpdateHooksWithPreviousStepConfig(): void {
    $steps = [
      'root' => new Step('root', Step::TYPE_ROOT, 'core:root', [], [new NextStep('trigger')]),
      'trigger' => new Step('trigger', Step::TYPE_TRIGGER, 'mailpoet:someone-subscribes', [], [new NextStep('action')]),
      'action' => new Step('action', Step::TYPE_ACTION, 'core:delay', [
        'delay' => 1,
        'delay_type' => 'MINUTES',
      ], []),
    ];
    $automation = (new AutomationFactory())
      ->withSteps($steps)
      ->withStatusActive()
      ->create();

    $updatedSteps = array_map(function(Step $step): array {
      return $step->toArray();
    }, $automation->getSteps());
    $updatedSteps['action']['args']['delay'] = 2;

    $this->diContainer->get(UpdateAutomationController::class)->updateAutomation($automation->getId(), [
      'steps' => $updatedSteps,
    ]);

    $updateEvent = $this->getUpdateEvent();
    $previousActionArgs = $updateEvent['previous_action_args'] ?? null;
    $actionArgs = $updateEvent['action_args'] ?? null;
    $this->assertIsArray($previousActionArgs);
    $this->assertIsArray($actionArgs);
    $this->assertSame(1, $previousActionArgs['delay']);
    $this->assertSame(2, $actionArgs['delay']);
  }

  public function testItFiresDuplicateHooksWithSourceAutomation(): void {
    $source = (new AutomationFactory())
      ->withSomeoneSubscribesTrigger()
      ->withDelayAction()
      ->create();

    $duplicate = $this->diContainer->get(DuplicateAutomationController::class)->duplicateAutomation($source->getId());

    $duplicateEvent = $this->getDuplicateEvent();
    $createEvent = $this->getCreateEvent();
    $this->assertSame($duplicate->getId(), $duplicateEvent['automation_id']);
    $this->assertSame($source->getId(), $duplicateEvent['source_automation_id']);
    $this->assertSame(Automation::STATUS_DRAFT, $createEvent['status']);
  }

  public function testItFiresDeleteHooksWithDeletedAutomationState(): void {
    $automation = (new AutomationFactory())
      ->withSomeoneSubscribesTrigger()
      ->withDelayAction()
      ->withStatus(Automation::STATUS_TRASH)
      ->create();

    $deleted = $this->diContainer->get(DeleteAutomationController::class)->deleteAutomation($automation->getId());

    $deleteEvent = $this->getDeleteEvent();
    $this->assertSame($deleted->getId(), $deleteEvent['automation_id']);
    $this->assertSame(Automation::STATUS_TRASH, $deleteEvent['status']);
  }

  public function testItFiresCreateFromTemplateHooksWithActiveAutomationState(): void {
    $templateSlug = 'lifecycle-hook-template';
    $this->diContainer->get(Registry::class)->addTemplate(new AutomationTemplate(
      $templateSlug,
      'welcome',
      'Lifecycle hook template',
      'Lifecycle hook template',
      function(): Automation {
        return $this->createAutomationFromTemplateData();
      }
    ));

    $automation = $this->diContainer->get(CreateAutomationFromTemplateController::class)->createAutomation($templateSlug);

    $templateEvent = $this->getTemplateEvent();
    $createEvent = $this->getCreateEvent();
    $this->assertSame($automation->getId(), $templateEvent['automation_id']);
    $this->assertSame($templateSlug, $templateEvent['template_slug']);
    $this->assertSame(Automation::STATUS_ACTIVE, $templateEvent['status']);
    $this->assertSame($automation->getId(), $createEvent['automation_id']);
    $this->assertSame(Automation::STATUS_ACTIVE, $createEvent['status']);
  }

  private function registerHooks(): void {
    $wp = $this->diContainer->get(\MailPoet\Automation\Engine\WordPress::class);
    $this->hookCallbacks[Hooks::AUTOMATION_AFTER_SAVE] = function(Automation $automation, ?Automation $previousAutomation = null): void {
      $this->saveEvent = [
        'automation_id' => $automation->getId(),
        'status' => $automation->getStatus(),
        'previous_status' => $previousAutomation ? $previousAutomation->getStatus() : null,
      ];
    };
    $wp->addAction(Hooks::AUTOMATION_AFTER_SAVE, $this->hookCallbacks[Hooks::AUTOMATION_AFTER_SAVE], 10, 2);
    $this->hookCallbacks[Hooks::AUTOMATION_AFTER_UPDATE] = function(Automation $automation, Automation $previousAutomation): void {
      $step = $automation->getStep('action');
      $previousStep = $previousAutomation->getStep('action');
      $this->updateEvent = [
        'automation_id' => $automation->getId(),
        'status' => $automation->getStatus(),
        'previous_status' => $previousAutomation->getStatus(),
        'action_args' => $step ? $step->getArgs() : null,
        'previous_action_args' => $previousStep ? $previousStep->getArgs() : null,
      ];
    };
    $wp->addAction(Hooks::AUTOMATION_AFTER_UPDATE, $this->hookCallbacks[Hooks::AUTOMATION_AFTER_UPDATE], 10, 2);
    $this->hookCallbacks[Hooks::AUTOMATION_AFTER_DELETE] = function(Automation $automation): void {
      $this->deleteEvent = [
        'automation_id' => $automation->getId(),
        'status' => $automation->getStatus(),
      ];
    };
    $wp->addAction(Hooks::AUTOMATION_AFTER_DELETE, $this->hookCallbacks[Hooks::AUTOMATION_AFTER_DELETE]);
    $this->hookCallbacks[Hooks::AUTOMATION_AFTER_DUPLICATE] = function(Automation $automation, Automation $sourceAutomation): void {
      $this->duplicateEvent = [
        'automation_id' => $automation->getId(),
        'source_automation_id' => $sourceAutomation->getId(),
        'status' => $automation->getStatus(),
      ];
    };
    $wp->addAction(Hooks::AUTOMATION_AFTER_DUPLICATE, $this->hookCallbacks[Hooks::AUTOMATION_AFTER_DUPLICATE], 10, 2);
    $this->hookCallbacks[Hooks::AUTOMATION_AFTER_CREATE_FROM_TEMPLATE] = function(Automation $automation, string $templateSlug): void {
      $this->templateEvent = [
        'automation_id' => $automation->getId(),
        'template_slug' => $templateSlug,
        'status' => $automation->getStatus(),
      ];
    };
    $wp->addAction(Hooks::AUTOMATION_AFTER_CREATE_FROM_TEMPLATE, $this->hookCallbacks[Hooks::AUTOMATION_AFTER_CREATE_FROM_TEMPLATE], 10, 2);
    $this->hookCallbacks[Hooks::AUTOMATION_AFTER_CREATE] = function(Automation $automation): void {
      $this->createEvent = [
        'automation_id' => $automation->getId(),
        'status' => $automation->getStatus(),
      ];
    };
    $wp->addAction(Hooks::AUTOMATION_AFTER_CREATE, $this->hookCallbacks[Hooks::AUTOMATION_AFTER_CREATE]);
  }

  /** @return array{automation_id: int, status: string, previous_status: string|null} */
  private function getSaveEvent(): array {
    if ($this->saveEvent === null) {
      $this->fail('Save event was not fired.');
    }
    return $this->saveEvent;
  }

  /** @return array{automation_id: int, status: string, previous_status: string, action_args?: array|null, previous_action_args?: array|null} */
  private function getUpdateEvent(): array {
    if ($this->updateEvent === null) {
      $this->fail('Update event was not fired.');
    }
    return $this->updateEvent;
  }

  /** @return array{automation_id: int, status: string} */
  private function getDeleteEvent(): array {
    if ($this->deleteEvent === null) {
      $this->fail('Delete event was not fired.');
    }
    return $this->deleteEvent;
  }

  /** @return array{automation_id: int, source_automation_id: int, status: string} */
  private function getDuplicateEvent(): array {
    if ($this->duplicateEvent === null) {
      $this->fail('Duplicate event was not fired.');
    }
    return $this->duplicateEvent;
  }

  /** @return array{automation_id: int, template_slug: string, status: string} */
  private function getTemplateEvent(): array {
    if ($this->templateEvent === null) {
      $this->fail('Create from template event was not fired.');
    }
    return $this->templateEvent;
  }

  /** @return array{automation_id: int, status: string} */
  private function getCreateEvent(): array {
    if ($this->createEvent === null) {
      $this->fail('Create event was not fired.');
    }
    return $this->createEvent;
  }

  private function createAutomationFromTemplateData(): Automation {
    $steps = [
      'root' => new Step('root', Step::TYPE_ROOT, 'core:root', [], [new NextStep('trigger')]),
      'trigger' => new Step('trigger', Step::TYPE_TRIGGER, 'mailpoet:someone-subscribes', [], [new NextStep('action')]),
      'action' => new Step('action', Step::TYPE_ACTION, 'core:delay', [
        'delay' => 1,
        'delay_type' => 'MINUTES',
      ], []),
    ];
    $automation = new Automation('Active template automation', $steps, new \WP_User(1));
    $automation->setStatus(Automation::STATUS_ACTIVE);
    return $automation;
  }
}
