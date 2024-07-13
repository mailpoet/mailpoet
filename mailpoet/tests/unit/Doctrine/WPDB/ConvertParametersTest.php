<?php declare(strict_types = 1);

namespace MailPoet\Doctrine\WPDB;

use DateTimeImmutable;
use DateTimeZone;
use MailPoet\Doctrine\WPDB\Exceptions\MissingParameterException;
use MailPoetUnitTest;
use MailPoetVendor\Doctrine\DBAL\ParameterType;
use stdClass;

class ConvertParametersTest extends MailPoetUnitTest {
  public function testPositionalParameters(): void {
    $params = new ConvertParameters([
      1 => [1, 123, ParameterType::INTEGER],
      2 => [2, 'aaa', ParameterType::STRING],
      3 => [3, true, ParameterType::BOOLEAN],
    ]);

    $params->acceptOther('SELECT * FROM test_table WHERE id = ');
    $params->acceptPositionalParameter('?');
    $params->acceptOther(' AND value = ');
    $params->acceptPositionalParameter('?');
    $params->acceptOther(' AND isDeleted = ');
    $params->acceptPositionalParameter('?');

    $this->assertSame(
      'SELECT * FROM test_table WHERE id = %d AND value = %s AND isDeleted = %d',
      $params->getSQL()
    );
    $this->assertSame([123, 'aaa', true], $params->getValues());
  }

  public function testNamedParameters(): void {
    $params = new ConvertParameters([
      'id' => ['id', 123, ParameterType::INTEGER],
      'value' => ['value', 'aaa', ParameterType::STRING],
      'isDeleted' => ['isDeleted', true, ParameterType::BOOLEAN],
    ]);

    $params->acceptOther('SELECT * FROM test_table WHERE id = ');
    $params->acceptNamedParameter(':id');
    $params->acceptOther(' AND value = ');
    $params->acceptNamedParameter(':value');
    $params->acceptOther(' AND isDeleted = ');
    $params->acceptNamedParameter(':isDeleted');

    $this->assertSame(
      'SELECT * FROM test_table WHERE id = %d AND value = %s AND isDeleted = %d',
      $params->getSQL()
    );
    $this->assertSame([123, 'aaa', true], $params->getValues());
  }

  public function testRepeatedNamedParameters(): void {
    $params = new ConvertParameters([
      'value' => ['value', 'aaa', ParameterType::STRING],
    ]);

    $params->acceptOther('SELECT * FROM test_table WHERE value1 = ');
    $params->acceptNamedParameter(':value');
    $params->acceptOther(' AND value2 = ');
    $params->acceptNamedParameter(':value');

    $this->assertSame(
      'SELECT * FROM test_table WHERE value1 = %s AND value2 = %s',
      $params->getSQL()
    );
    $this->assertSame(['aaa', 'aaa'], $params->getValues());
  }

  public function testMixedParameters(): void {
    $params = new ConvertParameters([
      1 => [1, 123, ParameterType::INTEGER],
      2 => [2, 'aaa', ParameterType::STRING],
      3 => [3, true, ParameterType::BOOLEAN],
      'named1' => ['named1', 'bbb', ParameterType::STRING],
      'named2' => ['named2', 'ccc', ParameterType::ASCII],
    ]);

    $params->acceptOther('SELECT * FROM test_table WHERE id = ');
    $params->acceptPositionalParameter('?');
    $params->acceptOther(' AND named1 = ');
    $params->acceptNamedParameter(':named1');
    $params->acceptOther(' AND value = ');
    $params->acceptPositionalParameter('?');
    $params->acceptOther(' AND named2 = ');
    $params->acceptNamedParameter(':named2');
    $params->acceptOther(' AND isDeleted = ');
    $params->acceptPositionalParameter('?');

    $this->assertSame(
      'SELECT * FROM test_table WHERE id = %d AND named1 = %s AND value = %s AND named2 = %s AND isDeleted = %d',
      $params->getSQL()
    );
    $this->assertSame([123, 'bbb', 'aaa', 'ccc', true], $params->getValues());
  }

  public function testMissingPositionalParameter(): void {
    $params = new ConvertParameters([
      1 => [1, 123, ParameterType::INTEGER],
    ]);

    $params->acceptOther('SELECT * FROM test_table WHERE id = ');
    $params->acceptPositionalParameter('?');
    $params->acceptOther(' AND value = ');

    $this->expectException(MissingParameterException::class);
    $this->expectExceptionMessage("Parameter '2' was defined in the query, but not provided.");

    $params->acceptPositionalParameter('?');
  }

  public function testMissingNamedParameter(): void {
    $params = new ConvertParameters([
      'id' => ['id', 123, ParameterType::INTEGER],
    ]);

    $params->acceptOther('SELECT * FROM test_table WHERE id = ');
    $params->acceptNamedParameter(':id');
    $params->acceptOther(' AND value = ');

    $this->expectException(MissingParameterException::class);
    $this->expectExceptionMessage("Parameter 'value' was defined in the query, but not provided.");

    $params->acceptNamedParameter(':value');
  }

  public function testNullValues(): void {
    $params = new ConvertParameters([
      1 => [1, null, ParameterType::STRING],
      'named' => ['named', null, ParameterType::INTEGER],
    ]);

    $params->acceptOther('SELECT * FROM test_table WHERE id = ');
    $params->acceptPositionalParameter('?');
    $params->acceptOther(' AND value = ');
    $params->acceptNamedParameter(':named');

    $this->assertSame(
      'SELECT * FROM test_table WHERE id = NULL AND value = NULL',
      $params->getSQL()
    );
    $this->assertSame([], $params->getValues());
  }

  public function testNonScalarValues(): void {
    $dateTime = new class('2021-01-01 12:34:56', new DateTimeZone('UTC')) extends DateTimeImmutable {
      public function __toString(): string {
        return $this->format(DateTimeImmutable::W3C);
      }
    };

    $params = new ConvertParameters([
      1 => [1, $dateTime, ParameterType::STRING],
      2 => [2, new stdClass(), ParameterType::BOOLEAN],
      3 => [3, ['abc'], ParameterType::INTEGER],
    ]);

    $params->acceptOther('SELECT * FROM test_table WHERE datetime = ');
    $params->acceptPositionalParameter('?');
    $params->acceptOther(' AND boolean = ');
    $params->acceptPositionalParameter('?');
    $params->acceptOther(' AND integer = ');
    $params->acceptPositionalParameter('?');

    $this->assertSame(
      'SELECT * FROM test_table WHERE datetime = %s AND boolean = %d AND integer = %d',
      $params->getSQL()
    );
    $this->assertSame(['2021-01-01T12:34:56+00:00', true, 1], $params->getValues());
  }
}
