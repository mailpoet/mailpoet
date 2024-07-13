<?php declare(strict_types = 1);

namespace MailPoet\Doctrine\WPDB;

use MailPoetUnitTest;

class ResultTest extends MailPoetUnitTest {
  /** @var object[] */
  private array $data;

  public function _before() {
    parent::_before();
    $this->data = [
      (object)['id' => '1', 'value' => 'aaa'],
      (object)['id' => '2', 'value' => 'bbb'],
      (object)['id' => '3', 'value' => 'ccc'],
    ];
  }

  public function testFetchNumeric(): void {
    $result = new Result($this->data, 2);
    $this->assertSame(['1', 'aaa'], $result->fetchNumeric());
    $this->assertSame(['2', 'bbb'], $result->fetchNumeric());
    $this->assertSame(['3', 'ccc'], $result->fetchNumeric());
    $this->assertSame(false, $result->fetchNumeric());

    $result = new Result([], 0);
    $this->assertSame(false, $result->fetchNumeric());
  }

  public function testFetchAssociative(): void {
    $result = new Result($this->data, 2);
    $this->assertSame(['id' => '1', 'value' => 'aaa'], $result->fetchAssociative());
    $this->assertSame(['id' => '2', 'value' => 'bbb'], $result->fetchAssociative());
    $this->assertSame(['id' => '3', 'value' => 'ccc'], $result->fetchAssociative());
    $this->assertSame(false, $result->fetchAssociative());

    $result = new Result([], 0);
    $this->assertSame(false, $result->fetchAssociative());
  }

  public function testFetchOne(): void {
    $result = new Result($this->data, 2);
    $this->assertSame('1', $result->fetchOne());
    $this->assertSame('2', $result->fetchOne());
    $this->assertSame('3', $result->fetchOne());
    $this->assertSame(false, $result->fetchOne());

    $result = new Result([], 0);
    $this->assertSame(false, $result->fetchOne());
  }

  public function testFetchAllNumeric(): void {
    $result = new Result($this->data, 2);
    $this->assertSame([['1', 'aaa'], ['2', 'bbb'], ['3', 'ccc']], $result->fetchAllNumeric());

    $result = new Result([], 0);
    $this->assertSame([], $result->fetchAllNumeric());
  }

  public function testFetchAllAssociative(): void {
    $result = new Result($this->data, 2);
    $this->assertSame([
      ['id' => '1', 'value' => 'aaa'],
      ['id' => '2', 'value' => 'bbb'],
      ['id' => '3', 'value' => 'ccc'],
    ], $result->fetchAllAssociative());

    $result = new Result([], 0);
    $this->assertSame([], $result->fetchAllAssociative());
  }

  public function testFetchFirstColumn(): void {
    $result = new Result($this->data, 2);
    $this->assertSame(['1', '2', '3'], $result->fetchFirstColumn());

    $result = new Result([], 0);
    $this->assertSame([], $result->fetchFirstColumn());
  }

  public function testRowCount(): void {
    $result = new Result($this->data, 2);
    $this->assertSame(2, $result->rowCount());

    $result = new Result([], 0);
    $this->assertSame(0, $result->rowCount());
  }

  public function testColumnCount(): void {
    $result = new Result($this->data, 2);
    $this->assertSame(2, $result->columnCount());

    $result = new Result([], 0);
    $this->assertSame(0, $result->columnCount());
  }

  public function testFree(): void {
    $result = new Result($this->data, 2);
    $this->assertSame('1', $result->fetchOne());
    $this->assertSame('2', $result->fetchOne());
    $this->assertSame('3', $result->fetchOne());
    $this->assertSame(false, $result->fetchOne());

    $result->free();
    $this->assertSame('1', $result->fetchOne());
  }
}
