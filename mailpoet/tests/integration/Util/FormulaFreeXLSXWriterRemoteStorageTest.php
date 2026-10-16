<?php declare(strict_types = 1);

namespace MailPoet\Test\Util;

use MailPoet\Test\Router\Endpoints\RemoteStorageStreamWrapper;
use MailPoet\Util\FormulaFreeXLSXWriter;

require_once __DIR__ . '/../Router/Endpoints/RemoteStorageStreamWrapper.php';

class FormulaFreeXLSXWriterRemoteStorageTest extends \MailPoetTest {
  public function _before() {
    parent::_before();
    RemoteStorageStreamWrapper::register();
  }

  public function _after() {
    RemoteStorageStreamWrapper::unregister();
    parent::_after();
  }

  public function testItWritesTheFileToRemoteStorage() {
    $file = RemoteStorageStreamWrapper::getRoot() . '/mailpoet/exports/export.xlsx';
    $writer = new FormulaFreeXLSXWriter();
    $writer->writeSheetRow('Sheet1', ['email@example.com']);

    $writer->writeToFile($file);

    verify(RemoteStorageStreamWrapper::$files)->arrayHasKey($file);
    verify(substr(RemoteStorageStreamWrapper::$files[$file], 0, 2))->equals('PK');
  }

  public function testItFailsWhenRemoteStorageRejectsTheFile() {
    RemoteStorageStreamWrapper::$rejectWrites = true;
    $writer = new FormulaFreeXLSXWriter();
    $writer->writeSheetRow('Sheet1', ['email@example.com']);
    $previousErrorReporting = error_reporting(E_ALL & ~E_WARNING);
    $exception = null;

    try {
      $writer->writeToFile(RemoteStorageStreamWrapper::getRoot() . '/mailpoet/exports/export.xlsx');
    } catch (\RuntimeException $e) {
      $exception = $e;
    } finally {
      error_reporting($previousErrorReporting);
    }

    verify($exception)->instanceOf(\RuntimeException::class);
    verify(RemoteStorageStreamWrapper::$files)->arrayCount(0);
  }
}
