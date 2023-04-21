<?php declare(strict_types = 1);

namespace unit\Automation\Engine\Validation\AutomationRules;

require_once __DIR__ . '/AutomationRuleTest.php';

use Codeception\Stub\Expected;
use MailPoet\Automation\Engine\Control\RootStep;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Filter as FilterData;
use MailPoet\Automation\Engine\Data\FilterGroup;
use MailPoet\Automation\Engine\Data\Filters;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Integration\Filter;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Validation\AutomationGraph\AutomationWalker;
use MailPoet\Automation\Engine\Validation\AutomationRules\AutomationRuleTest;
use MailPoet\Automation\Engine\Validation\AutomationRules\ValidStepFiltersRule;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;
use MailPoet\Validator\Validator;

class ValidStepFiltersRuleTest extends AutomationRuleTest {
  public function testItRunsFiltersValidation(): void {
    $registry = $this->make(Registry::class, [
      'steps' => ['core:root' => new RootStep()],
      'filters' => ['string' => $this->getFilter()],
    ]);

    $validator = $this->make(Validator::class, [
      'validate' => Expected::once(),
    ]);

    $filters = [new FilterData('f1', 'string', 'test:key', 'is', ['value' => 'test'])];
    $rule = new ValidStepFiltersRule($registry, $validator);
    $automation = $this->getAutomation($filters);
    (new AutomationWalker())->walk($automation, [$rule]);
  }

  public function testItSkipsFiltersValidationForNonExistentFilter(): void {
    $registry = $this->make(Registry::class);
    $validator = $this->make(Validator::class, [
      'validate' => Expected::never(),
    ]);

    $filters = [new FilterData('f1', 'string', 'test:key', 'is', ['value' => 'test'])];
    $rule = new ValidStepFiltersRule($registry, $validator);
    $automation = $this->getAutomation($filters);
    (new AutomationWalker())->walk($automation, [$rule]);
  }

  private function getAutomation(array $filters): Automation {
    $filters = new Filters('and', [new FilterGroup('g1', 'and', $filters)]);
    return $this->make(Automation::class, [
      'getSteps' => [
        'root' => new Step('root', 'root', 'core:root', [], [], $filters),
      ],
    ]);
  }

  private function getFilter(): Filter {
    return new class() implements Filter {
      public function getFieldType(): string {
        return '';
      }

      public function getConditions(): array {
        return [];
      }

      public function getArgsSchema(): ObjectSchema {
        return Builder::object();
      }

      public function matches(FilterData $data, $value): bool {
        return true;
      }
    };
  }
}
