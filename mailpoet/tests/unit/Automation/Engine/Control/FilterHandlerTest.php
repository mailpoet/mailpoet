<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine\Control;

use Codeception\Stub;
use MailPoet\Automation\Engine\Control\FilterHandler;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Engine\Data\Filter as FilterData;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\Subject as SubjectData;
use MailPoet\Automation\Engine\Data\SubjectEntry;
use MailPoet\Automation\Engine\Integration\Filter;
use MailPoet\Automation\Engine\Integration\Payload;
use MailPoet\Automation\Engine\Integration\Subject;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;
use MailPoetUnitTest;

class FilterHandlerTest extends MailPoetUnitTest {
  /** @dataProvider dataForTestItFilters */
  public function testItFilters(array $stepFilters, bool $expectation): void {
    $step = new Step('step', Step::TYPE_TRIGGER, 'test:step', [], [], $stepFilters);
    $subject = $this->createSubject('subject', [
      new Field('test:field-string', Field::TYPE_STRING, 'Test field string', function () {
        return 'abc';
      }),
      new Field('test:field-integer', Field::TYPE_INTEGER, 'Test field integer', function () {
        return 123;
      }),
      new Field('test:field-boolean', Field::TYPE_BOOLEAN, 'Test field boolean', function () {
        return true;
      }),
    ]);

    $stepRunArgs = new StepRunArgs(
      $this->createMock(Automation::class),
      $this->createMock(AutomationRun::class),
      $step,
      [new SubjectEntry($subject, new SubjectData($subject->getKey(), []))]
    );

    $registry = Stub::make(Registry::class, [
      'filters' => [
        Field::TYPE_STRING => $this->createFilter(Field::TYPE_STRING),
        Field::TYPE_INTEGER => $this->createFilter(Field::TYPE_INTEGER),
        Field::TYPE_BOOLEAN => $this->createFilter(Field::TYPE_BOOLEAN),
      ],
    ]);

    $handler = new FilterHandler($registry);
    $result = $handler->matchesFilters($stepRunArgs);
    $this->assertSame($expectation, $result);
  }

  public function dataForTestItFilters(): array {
    return [
      // no filters
      [
        [],
        true,
      ],

      // matching
      [
        [
          new FilterData(Field::TYPE_STRING, 'test:field-string', '', ['value' => 'abc']),
          new FilterData(Field::TYPE_INTEGER, 'test:field-integer', '', ['value' => 123]),
        ],
        true,
      ],

      // not matching
      [
        [
          new FilterData(Field::TYPE_INTEGER, 'test:field-integer', '', ['value' => 999]),
        ],
        false,
      ],
      [
        [
          new FilterData(Field::TYPE_STRING, 'test:field-string', '', ['value' => 'abc']),
          new FilterData(Field::TYPE_INTEGER, 'test:field-integer', '', ['value' => 999]),
          new FilterData(Field::TYPE_BOOLEAN, 'test:field-boolean', '', ['value' => true]),
        ],
        false,
      ],
    ];
  }

  /** @return Subject<Payload> */
  private function createSubject(string $key, array $fields): Subject {
    return new class($key, $fields) implements Subject {
      /** @var string */
      private $key;

      /** @var array */
      private $fields;

      public function __construct(
        string $key,
        array $fields
      ) {
        $this->key = $key;
        $this->fields = $fields;
      }

      public function getKey(): string {
        return 'test:' . $this->key;
      }

      public function getName(): string {
        return 'Test subject ' . $this->key;
      }

      public function getArgsSchema(): ObjectSchema {
        return Builder::object();
      }

      public function getFields(): array {
        return $this->fields;
      }

      public function getPayload(SubjectData $subjectData): Payload {
        return new class implements Payload {
        };
      }
    };
  }

  private function createFilter(string $fieldType): Filter {
    return new class($fieldType) implements Filter {
      /** @var string */
      private $fieldType;

      public function __construct(
        string $fieldType
      ) {
        $this->fieldType = $fieldType;
      }

      public function getFieldType(): string {
        return $this->fieldType;
      }

      public function getConditions(): array {
        return [];
      }

      public function getArgsSchema(): ObjectSchema {
        return Builder::object();
      }

      public function matches(FilterData $data, $value): bool {
        return $data->getArgs()['value'] === $value;
      }
    };
  }
}
