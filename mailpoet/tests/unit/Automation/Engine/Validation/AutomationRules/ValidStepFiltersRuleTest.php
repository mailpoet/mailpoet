<?php declare(strict_types = 1);

namespace unit\Automation\Engine\Validation\AutomationRules;

require_once __DIR__ . '/AutomationRuleTest.php';

use Codeception\Stub\Expected;
use MailPoet\Automation\Engine\Control\RootStep;
use MailPoet\Automation\Engine\Control\SubjectTransformerHandler;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Engine\Data\Filter as FilterData;
use MailPoet\Automation\Engine\Data\FilterGroup;
use MailPoet\Automation\Engine\Data\Filters;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Subject as SubjectData;
use MailPoet\Automation\Engine\Integration\Filter;
use MailPoet\Automation\Engine\Integration\Payload;
use MailPoet\Automation\Engine\Integration\Subject;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Validation\AutomationGraph\AutomationWalker;
use MailPoet\Automation\Engine\Validation\AutomationRules\AutomationRuleTest;
use MailPoet\Automation\Engine\Validation\AutomationRules\ValidStepFiltersRule;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;
use MailPoet\Validator\ValidationException;
use MailPoet\Validator\Validator;

class ValidStepFiltersRuleTest extends AutomationRuleTest {
  public function testItRunsFiltersValidation(): void {
    $field = new Field('test:field', Field::TYPE_STRING, 'Test field', [$this, 'callback']);
    $registry = $this->make(Registry::class, [
      'steps' => ['core:root' => new RootStep()],
      'filters' => ['string' => $this->getFilter()],
      'subjects' => [$this->getSubject('test:subject-key', 'Test subject', [$field])],
    ]);

    $validator = $this->make(Validator::class, [
      'validate' => Expected::once(),
    ]);

    $transformer = $this->make(SubjectTransformerHandler::class, [
      'getSubjectKeysForAutomation' => ['test:subject-key'],
    ]);

    $filters = [new FilterData('f1', 'string', 'test:field', 'is', ['value' => 'test'])];
    $rule = new ValidStepFiltersRule($registry, $transformer, $validator);
    $automation = $this->getAutomation($filters);
    (new AutomationWalker())->walk($automation, [$rule]);
  }

  public function testItSkipsFiltersValidationForNonExistentFilter(): void {
    $registry = $this->make(Registry::class);
    $validator = $this->make(Validator::class, [
      'validate' => Expected::never(),
    ]);
    $transformer = $this->make(SubjectTransformerHandler::class);

    $filters = [new FilterData('f1', 'string', 'test:key', 'is', ['value' => 'test'])];
    $rule = new ValidStepFiltersRule($registry, $transformer, $validator);
    $automation = $this->getAutomation($filters);
    (new AutomationWalker())->walk($automation, [$rule]);
  }

  public function testItValidatesFieldsAreProvidedBySubjects(): void {
    $field = new Field('test:field', Field::TYPE_STRING, 'Test field', [$this, 'callback']);
    $registry = $this->make(Registry::class, [
      'steps' => ['core:root' => new RootStep()],
      'filters' => ['string' => $this->getFilter()],
      'subjects' => [$this->getSubject('test:subject', 'Test subject', [$field])],
    ]);

    $validator = $this->make(Validator::class, [
      'validate' => Expected::exactly(2),
    ]);

    $transformer = $this->make(SubjectTransformerHandler::class, [
      'getSubjectKeysForAutomation' => [],
    ]);

    $filters = [
      new FilterData('f1', 'string', 'test:field', 'is', ['value' => 'test']),
      new FilterData('f2', 'string', 'test:unknown-field', 'is', ['value' => 'test']),
    ];
    $rule = new ValidStepFiltersRule($registry, $transformer, $validator);
    $automation = $this->getAutomation($filters);

    $error = null;
    try {
      (new AutomationWalker())->walk($automation, [$rule]);
    } catch (ValidationException $error) {
      $this->assertCount(2, $error->getErrors());
      $this->assertSame('A trigger that provides Test subject is required', $error->getErrors()['f1']);
      $this->assertSame('Field not found', $error->getErrors()['f2']);
    }
    $this->assertNotNull($error);
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

      public function getArgsSchema(string $condition): ObjectSchema {
        return Builder::object();
      }

      public function matches(FilterData $data, $value): bool {
        return true;
      }
    };
  }

  /** @return Subject<Payload> */
  private function getSubject(string $key, string $name, array $fields): Subject {
    return new class($key, $name, $fields) implements Subject {
      /** @var string */
      private $key;

      /** @var string */
      private $name;

      /** @var array */
      private $fields;

      public function __construct(
        string $key,
        string $name,
        array $fields
      ) {
        $this->key = $key;
        $this->name = $name;
        $this->fields = $fields;
      }

      public function getKey(): string {
        return $this->key;
      }

      public function getName(): string {
        return $this->name;
      }

      public function getArgsSchema(): ObjectSchema {
        return Builder::object();
      }

      public function getFields(): array {
        return $this->fields;
      }

      public function getPayload(SubjectData $subjectData): Payload {
        return new class() implements Payload {
        };
      }
    };
  }
}
