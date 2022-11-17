<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\PHPStan\Extensions\CodeceptionExtension\Type;

use Codeception\Test\Unit;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

class TestCaseDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension {
  public function getClass(): string {
    return Unit::class;
  }

  public function isMethodSupported(MethodReflection $reflection): bool {
    return in_array($reflection->getName(), [
      'make',
      'makeEmpty',
      'makeEmptyExcept',
      'construct',
      'constructEmpty',
      'constructEmptyExcept',
      'copy',
    ], true);
  }

  public function getTypeFromMethodCall(MethodReflection $reflection, MethodCall $call, Scope $scope): Type {
    $type = $scope->getType($call->args[0]->value);
    if ($type instanceof ConstantStringType) {
      return new ObjectType($type->getValue()); // $type is class name
    }
    return $type;
  }
}
