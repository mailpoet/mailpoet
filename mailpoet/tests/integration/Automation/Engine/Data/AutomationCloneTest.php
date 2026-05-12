<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine\Data;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Filter;
use MailPoet\Automation\Engine\Data\FilterGroup;
use MailPoet\Automation\Engine\Data\Filters;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;

class AutomationCloneTest extends \MailPoetTest {
  public function testItDeepClonesStepsAndFilters(): void {
    $filter = new Filter(
      'filter',
      'string',
      'mailpoet:subscriber:email',
      'contains',
      ['value' => '@example.com']
    );
    $filters = new Filters(
      Filters::OPERATOR_AND,
      [new FilterGroup('group', FilterGroup::OPERATOR_AND, [$filter])]
    );
    $automation = new Automation('Clone test', [
      'root' => new Step('root', Step::TYPE_ROOT, 'core:root', [], [new NextStep('trigger')]),
      'trigger' => new Step('trigger', Step::TYPE_TRIGGER, 'mailpoet:someone-subscribes', [], [new NextStep('action')]),
      'action' => new Step('action', Step::TYPE_ACTION, 'core:if-else', [], [new NextStep(null), new NextStep(null)], $filters),
    ], new \WP_User(1));

    $clone = clone $automation;

    $originalTrigger = $automation->getStep('trigger');
    $originalAction = $automation->getStep('action');
    $clonedAction = $clone->getStep('action');
    $this->assertInstanceOf(Step::class, $originalTrigger);
    $this->assertInstanceOf(Step::class, $originalAction);
    $this->assertInstanceOf(Step::class, $clonedAction);
    $this->assertNotSame($originalAction, $clonedAction);
    $this->assertNotSame($originalAction->getNextSteps()[0], $clonedAction->getNextSteps()[0]);
    $this->assertNotSame($originalAction->getFilters(), $clonedAction->getFilters());

    $originalFilters = $originalAction->getFilters();
    $clonedFilters = $clonedAction->getFilters();
    $this->assertInstanceOf(Filters::class, $originalFilters);
    $this->assertInstanceOf(Filters::class, $clonedFilters);
    $this->assertNotSame($originalFilters->getGroups()[0], $clonedFilters->getGroups()[0]);
    $this->assertNotSame($originalFilters->getGroups()[0]->getFilters()[0], $clonedFilters->getGroups()[0]->getFilters()[0]);

    $clonedAction->setNextSteps([new NextStep('changed')]);
    $this->assertSame('action', $originalTrigger->getNextStepIds()[0]);
    $this->assertNull($originalAction->getNextSteps()[0]->getId());
    $this->assertSame('changed', $clonedAction->getNextSteps()[0]->getId());
  }
}
