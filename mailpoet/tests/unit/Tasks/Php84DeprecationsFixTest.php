<?php declare(strict_types = 1);

namespace MailPoet\Test\Tasks;

use MailPoet\Tasks\Php84DeprecationsFix;
use ReflectionClass;

require_once __DIR__ . '/../../../tasks/Php84DeprecationsFix.php';

class Php84DeprecationsFixTest extends \MailPoetUnitTest {
  private Php84DeprecationsFix $fixer;

  public function _before() {
    parent::_before();
    $this->fixer = new Php84DeprecationsFix([]);
  }

  public function testItAddsNullableToSimpleTypeWithNull() {
    $input = 'function foo(string $bar = null) {}';
    $expected = 'function foo(?string $bar = null) {}';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItAddsNullableToArrayType() {
    $input = 'function foo(array $items = null) {}';
    $expected = 'function foo(?array $items = null) {}';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItAddsNullableToFullyQualifiedClass() {
    $input = 'function foo(\DateTime $date = null) {}';
    $expected = 'function foo(?\DateTime $date = null) {}';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItAddsNullableToNamespacedClass() {
    $input = 'function foo(Some\Namespace\Class $obj = null) {}';
    $expected = 'function foo(?Some\Namespace\Class $obj = null) {}';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItHandlesReferenceParameter() {
    $input = 'function foo(string &$bar = null) {}';
    $expected = 'function foo(?string &$bar = null) {}';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItHandlesVariadicParameter() {
    $input = 'function foo(string ...$bars = null) {}';
    $expected = 'function foo(?string ...$bars = null) {}';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItHandlesReferenceAndVariadicCombined() {
    $input = 'function foo(string &...$bars = null) {}';
    $expected = 'function foo(?string &...$bars = null) {}';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItHandlesNullInUpperCase() {
    $input = 'function foo(string $bar = NULL) {}';
    $expected = 'function foo(?string $bar = NULL) {}';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItHandlesMultipleParameters() {
    $input = 'function foo(string $a = null, int $b = null, array $c = null) {}';
    // Note: The pattern doesn't preserve space after comma, but result is still valid PHP
    $expected = 'function foo(?string $a = null,?int $b = null,?array $c = null) {}';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItDoesNotModifyAlreadyNullableParameter() {
    $input = 'function foo(?string $bar = null) {}';
    $expected = 'function foo(?string $bar = null) {}';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItDoesNotModifyMixedType() {
    $input = 'function foo(mixed $bar = null) {}';
    $expected = 'function foo(mixed $bar = null) {}';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItDoesNotModifyUnionTypes() {
    $input = 'function foo(string|int $bar = null) {}';
    $expected = 'function foo(string|int $bar = null) {}';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItDoesNotModifyUnionTypesWithNull() {
    $input = 'function foo(string|null $bar = null) {}';
    $expected = 'function foo(string|null $bar = null) {}';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItDoesNotModifyPrivateVisibility() {
    $input = 'function foo(private $bar = null) {}';
    $expected = 'function foo(private $bar = null) {}';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItDoesNotModifyPublicVisibility() {
    $input = 'function __construct(public $bar = null) {}';
    $expected = 'function __construct(public $bar = null) {}';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItDoesNotModifyProtectedVisibility() {
    $input = 'function __construct(protected $bar = null) {}';
    $expected = 'function __construct(protected $bar = null) {}';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItDoesNotModifyStaticKeyword() {
    $input = 'function foo(static $bar = null) {}';
    $expected = 'function foo(static $bar = null) {}';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItDoesNotModifyParameterWithoutDefaultNull() {
    $input = 'function foo(string $bar) {}';
    $expected = 'function foo(string $bar) {}';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItDoesNotModifyParameterWithDifferentDefault() {
    $input = 'function foo(string $bar = "default") {}';
    $expected = 'function foo(string $bar = "default") {}';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItDoesNotModifyParameterWithIntegerDefault() {
    $input = 'function foo(int $bar = 0) {}';
    $expected = 'function foo(int $bar = 0) {}';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItHandlesMethodDeclaration() {
    $input = 'public function myMethod(string $param = null): void {}';
    $expected = 'public function myMethod(?string $param = null): void {}';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItHandlesConstructorPromotedProperties() {
    $input = 'public function __construct(string $name = null, int $age = null) {}';
    $expected = 'public function __construct(?string $name = null,?int $age = null) {}';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItHandlesMultilineDeclaration() {
    $input = "function foo(\n  string \$bar = null,\n  int \$baz = null\n) {}";
    // Note: Whitespace formatting changes but result is valid
    $expected = "function foo(?string \$bar = null,?int \$baz = null\n) {}";
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItHandlesDifferentWhitespace() {
    $input = 'function foo(  string   $bar   =   null  ) {}';
    // Note: Leading whitespace after ( is consumed by pattern
    $expected = 'function foo(?string   $bar   =   null  ) {}';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItHandlesInterfaceDeclaration() {
    $input = 'interface Foo { public function bar(string $baz = null); }';
    $expected = 'interface Foo { public function bar(?string $baz = null); }';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItHandlesAbstractMethodDeclaration() {
    $input = 'abstract public function process(array $data = null);';
    $expected = 'abstract public function process(?array $data = null);';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItHandlesCallableType() {
    $input = 'function foo(callable $callback = null) {}';
    $expected = 'function foo(?callable $callback = null) {}';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItHandlesObjectType() {
    $input = 'function foo(object $obj = null) {}';
    $expected = 'function foo(?object $obj = null) {}';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItHandlesIterableType() {
    $input = 'function foo(iterable $items = null) {}';
    $expected = 'function foo(?iterable $items = null) {}';
    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  public function testItHandlesRealWorldExample() {
    $input = <<<'PHP'
class Example {
  public function __construct(
    private string $name = null,
    protected array $data = null,
    public \DateTime $date = null
  ) {}

  public function process(
    string $input = null,
    callable $callback = null
  ): void {}
}
PHP;

    $expected = <<<'PHP'
class Example {
  public function __construct(
    private ?string $name = null,
    protected ?array $data = null,
    public ?\DateTime $date = null
  ) {}

  public function process(?string $input = null,?callable $callback = null
  ): void {}
}
PHP;

    verify($this->fixImplicitlyNullableParameters($input))->equals($expected);
  }

  /**
   * Helper method to access private method via reflection
   */
  private function fixImplicitlyNullableParameters(string $content): string {
    $reflection = new ReflectionClass($this->fixer);
    $method = $reflection->getMethod('fixImplicitlyNullableParameters');
    $method->setAccessible(true);
    $value = $method->invoke($this->fixer, $content);
    return is_string($value) ? $value : '';
  }
}
