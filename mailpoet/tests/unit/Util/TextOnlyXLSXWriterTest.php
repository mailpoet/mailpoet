<?php declare(strict_types = 1);

namespace MailPoet\Util;

class TextOnlyXLSXWriterTest extends \MailPoetUnitTest {
  /** @var string */
  private $file;

  public function _before() {
    $this->file = sys_get_temp_dir() . '/mailpoet-xlsx-' . uniqid('', true) . '.xlsx';
  }

  public function _after() {
    if (file_exists($this->file)) {
      unlink($this->file);
    }
  }

  public function testItWritesALeadingEqualsAsTextInsteadOfAFormula() {
    $writer = new TextOnlyXLSXWriter();
    $writer->writeSheetRow('Sheet1', ['=SUM(1+1)']);
    $writer->writeToFile($this->file);

    verify($this->readWorksheet())->stringNotContainsString('<f>');
    verify($this->readSharedStrings())->stringContainsString('=SUM(1+1)');
  }

  public function testItWritesALeadingEqualsInAHeaderAsText() {
    $writer = new TextOnlyXLSXWriter();
    $writer->writeSheetHeader('Sheet1', ['=SUM(1+1)' => 'string']);
    $writer->writeToFile($this->file);

    verify($this->readWorksheet())->stringNotContainsString('<f>');
    verify($this->readSharedStrings())->stringContainsString('=SUM(1+1)');
  }

  public function testItKeepsNumbersAsNumbers() {
    $writer = new TextOnlyXLSXWriter();
    $writer->writeSheetHeader('Sheet1', ['a' => 'string', 'b' => 'string', 'c' => 'string']);
    $writer->writeSheetRow('Sheet1', [-5, 12.5, '-1234']);
    $writer->writeToFile($this->file);

    $worksheet = $this->readWorksheet();
    verify(substr_count($worksheet, 't="n"'))->equals(3);
    verify($worksheet)->stringNotContainsString("'-");
  }

  public function testItLeavesOrdinaryTextUntouched() {
    $writer = new TextOnlyXLSXWriter();
    $writer->writeSheetHeader('Sheet1', ['a' => 'string', 'b' => 'string', 'c' => 'string']);
    $writer->writeSheetRow('Sheet1', ['@handle', '+420777123456', "Anne-Marie O'Brien"]);
    $writer->writeToFile($this->file);

    $worksheet = $this->readWorksheet();
    verify($worksheet)->stringNotContainsString('<f>');
    $shared = $this->readSharedStrings();
    verify($shared)->stringContainsString('@handle');
    verify($shared)->stringContainsString("Anne-Marie O'Brien");
    // The vendored writer treats a leading "+" as a number, so the phone number becomes
    // a numeric cell rather than a shared string. Existing behaviour, and not a formula.
    verify($worksheet)->stringContainsString('<v>+420777123456</v>');
  }

  private function readWorksheet(): string {
    return $this->readEntry('xl/worksheets/sheet1.xml');
  }

  private function readSharedStrings(): string {
    return html_entity_decode($this->readEntry('xl/sharedStrings.xml'), ENT_QUOTES);
  }

  private function readEntry(string $name): string {
    $archive = new \ZipArchive();
    verify($archive->open($this->file))->true();
    $contents = (string)$archive->getFromName($name);
    $archive->close();
    return $contents;
  }
}
