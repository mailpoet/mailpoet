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
use PHPStan\Type\TypeCombinator;

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

  public function getTypeFromMethodCall(MethodReflection $reflection, MethodCall $call, Scope $scope): ?Type {
    $args = $call->getArgs();
    if (!isset($args[0])) {
      return null;
    }
    $argType = $scope->getType($args[0]->value);
    if ($argType instanceof ConstantStringType) {
      $targetType = new ObjectType($argType->getValue());
    } elseif ($argType instanceof ObjectType) {
      $targetType = $argType;
    } else {
      return null;
    }
    return TypeCombinator::intersect($targetType, new ObjectType(\PHPUnit\Framework\MockObject\MockObject::class));
  }
}
