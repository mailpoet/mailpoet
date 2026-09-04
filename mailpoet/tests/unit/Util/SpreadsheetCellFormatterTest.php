<?php declare(strict_types = 1);

namespace MailPoet\Util;

class SpreadsheetCellFormatterTest extends \MailPoetUnitTest {
  public function testItPrefixesValuesASpreadsheetWouldEvaluate() {
    verify(SpreadsheetCellFormatter::format('=SUM(1+1)'))->equals("'=SUM(1+1)");
    verify(SpreadsheetCellFormatter::format('+1234'))->equals("'+1234");
    verify(SpreadsheetCellFormatter::format('-1234'))->equals("'-1234");
    verify(SpreadsheetCellFormatter::format('@user'))->equals("'@user");
    verify(SpreadsheetCellFormatter::format("\t=1+1"))->equals("'\t=1+1");
    verify(SpreadsheetCellFormatter::format("\r=1+1"))->equals("'\r=1+1");
  }

  public function testItLeavesOrdinaryTextUntouched() {
    verify(SpreadsheetCellFormatter::format('Jane Doe'))->equals('Jane Doe');
    verify(SpreadsheetCellFormatter::format('jane@example.com'))->equals('jane@example.com');
    verify(SpreadsheetCellFormatter::format('Spring "sale"!'))->equals('Spring "sale"!');
    verify(SpreadsheetCellFormatter::format('1 - 2'))->equals('1 - 2');
    verify(SpreadsheetCellFormatter::format(''))->equals('');
  }

  public function testItLeavesNonStringsUntouched() {
    verify(SpreadsheetCellFormatter::format(-5))->equals(-5);
    verify(SpreadsheetCellFormatter::format(-12.5))->equals(-12.5);
    verify(SpreadsheetCellFormatter::format(0))->equals(0);
    verify(SpreadsheetCellFormatter::format(null))->equals(null);
  }

  public function testItFormatsAListOfStrings() {
    verify(SpreadsheetCellFormatter::formatStrings(['=SUM(1+1)', 'Country', '-5']))
      ->equals(["'=SUM(1+1)", 'Country', "'-5"]);
  }

  public function testItFormatsAWholeRowAndKeepsKeys() {
    $row = [
      'name' => '=cmd()',
      'email' => 'jane@example.com',
      'sent' => 500,
      'revenue' => -12.5,
    ];

    verify(SpreadsheetCellFormatter::formatRow($row))->equals([
      'name' => "'=cmd()",
      'email' => 'jane@example.com',
      'sent' => 500,
      'revenue' => -12.5,
    ]);
  }
}
