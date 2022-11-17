<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\PHPStan\Extensions\CodeceptionExtension\Type;

use Codeception\Stub;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

class StubDynamicReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension {
  public function getClass(): string {
    return Stub::class;
  }

  public function isStaticMethodSupported(MethodReflection $reflection): bool {
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

  public function getTypeFromStaticMethodCall(MethodReflection $reflection, StaticCall $call, Scope $scope): Type {
    $type = $scope->getType($call->args[0]->value);
    if ($type instanceof ConstantStringType) {
      return new ObjectType($type->getValue()); // $type is class name
    }
    return $type;
  }
}
