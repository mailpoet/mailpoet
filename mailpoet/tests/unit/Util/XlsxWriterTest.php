<?php declare(strict_types = 1);

namespace MailPoet\Test\Util;

class XlsxWriterTest extends \MailPoetUnitTest {
  public function _before() {
  }

  public function testItCanBeCreated() {
    $writer = new \MailPoetVendor\XLSXWriter();
  }

  public function testItWritesTheCreationTimeInUtc() {
    $file = sys_get_temp_dir() . '/mailpoet-xlsx-' . uniqid('', true) . '.xlsx';
    $originalTimezone = date_default_timezone_get();
    date_default_timezone_set('Pacific/Auckland'); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.timezone_change_date_default_timezone_set -- The test needs a non-UTC timezone.
    try {
      $before = time();
      $writer = new \MailPoetVendor\XLSXWriter();
      $writer->writeSheetRow('Sheet1', ['a']);
      $writer->writeToFile($file);
      $after = time();

      $archive = new \ZipArchive();
      verify($archive->open($file))->true();
      $coreXml = (string)$archive->getFromName('docProps/core.xml');
      $archive->close();
    } finally {
      date_default_timezone_set($originalTimezone); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.timezone_change_date_default_timezone_set -- Restores the timezone set above.
      if (file_exists($file)) {
        unlink($file);
      }
    }

    verify(preg_match('~<dcterms:created[^>]*>(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})\.00Z</dcterms:created>~', $coreXml, $matches))->equals(1);
    $created = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', $matches[1] ?? '', new \DateTimeZone('UTC'));
    $this->assertInstanceOf(\DateTimeImmutable::class, $created);
    verify($created->getTimestamp())->greaterThanOrEqual($before);
    verify($created->getTimestamp())->lessThanOrEqual($after);
  }

  public function _after() {
  }
}
