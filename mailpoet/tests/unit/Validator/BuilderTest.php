<?php declare(strict_types = 1);

namespace MailPoet\Validator;

use MailPoet\Validator\Schema\AnyOfSchema;
use MailPoet\Validator\Schema\ArraySchema;
use MailPoet\Validator\Schema\BooleanSchema;
use MailPoet\Validator\Schema\IntegerSchema;
use MailPoet\Validator\Schema\NullSchema;
use MailPoet\Validator\Schema\NumberSchema;
use MailPoet\Validator\Schema\ObjectSchema;
use MailPoet\Validator\Schema\OneOfSchema;
use MailPoet\Validator\Schema\StringSchema;
use MailPoetUnitTest;

class BuilderTest extends MailPoetUnitTest {
  public function testBuildSchemas(): void {
    $this->assertInstanceOf(StringSchema::class, Builder::string());
    $this->assertInstanceOf(NumberSchema::class, Builder::number());
    $this->assertInstanceOf(IntegerSchema::class, Builder::integer());
    $this->assertInstanceOf(BooleanSchema::class, Builder::boolean());
    $this->assertInstanceOf(NullSchema::class, Builder::null());
    $this->assertInstanceOf(ArraySchema::class, Builder::array());
    $this->assertInstanceOf(ObjectSchema::class, Builder::object());
    $this->assertInstanceOf(OneOfSchema::class, Builder::oneOf([]));
    $this->assertInstanceOf(AnyOfSchema::class, Builder::anyOf([]));
  }
}
