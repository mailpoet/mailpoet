<?php

namespace MailPoet\Subscribers\ImportExport\Export;

use MailPoet\Config\Env;
use MailPoet\Models\CustomField;
use MailPoet\Subscribers\ImportExport\ImportExportFactory;
use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\XLSXWriter;

class Export {
  const SUBSCRIBER_BATCH_SIZE = 15000;

  public $exportFormatOption;
  public $subscriberFields;
  public $subscriberCustomFields;
  public $formattedSubscriberFields;
  public $exportPath;
  public $exportFile;
  public $exportFileURL;
  public $defaultSubscribersGetter;
  public $dynamicSubscribersGetter;

  public function __construct($data) {
    if (strpos((string)@ini_get('disable_functions'), 'set_time_limit') === false) {
      set_time_limit(0);
    }

    $this->defaultSubscribersGetter = new DefaultSubscribersGetter(
      $data['segments'],
      self::SUBSCRIBER_BATCH_SIZE
    );

    $this->dynamicSubscribersGetter = new DynamicSubscribersGetter(
      $data['segments'],
      self::SUBSCRIBER_BATCH_SIZE
    );

    $this->exportFormatOption = $data['export_format_option'];
    $this->subscriberFields = $data['subscriber_fields'];
    $this->subscriberCustomFields = $this->getSubscriberCustomFields();
    $this->formattedSubscriberFields = $this->formatSubscriberFields(
      $this->subscriberFields,
      $this->subscriberCustomFields
    );
    $this->exportPath = self::getExportPath();
    $this->exportFile = $this->getExportFile($this->exportFormatOption);
    $this->exportFileURL = $this->getExportFileURL($this->exportFile);
  }

  public static function getFilePrefix() {
    return 'MailPoet_export_';
  }

  public static function getExportPath() {
    return Env::$tempPath;
  }

  public function process() {
    $processedSubscribers = 0;
    $this->defaultSubscribersGetter->reset();
    try {
      if (is_writable($this->exportPath) === false) {
        throw new \Exception(__('The export file could not be saved on the server.', 'mailpoet'));
      }
      if (!extension_loaded('zip') && ($this->exportFormatOption === 'xlsx')) {
        throw new \Exception(__('Export requires a ZIP extension to be installed on the host.', 'mailpoet'));
      }
      $callback = [
        $this,
        'generate' . strtoupper($this->exportFormatOption),
      ];
      if (is_callable($callback)) {
        $processedSubscribers = call_user_func($callback);
      }
    } catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }
    return [
      'totalExported' => $processedSubscribers,
      'exportFileURL' => $this->exportFileURL,
    ];
  }

  public function generateCSV() {
    $processedSubscribers = 0;
    $formattedSubscriberFields = $this->formattedSubscriberFields;
    $cSVFile = fopen($this->exportFile, 'w');
    if ($cSVFile === false) {
      throw new \Exception(__('Failed opening file for export.', 'mailpoet'));
    }
    $formatCSV = function($row) {
      return '"' . str_replace('"', '\"', $row) . '"';
    };
    $formattedSubscriberFields[] = WPFunctions::get()->__('List', 'mailpoet');
    // add UTF-8 BOM (3 bytes, hex EF BB BF) at the start of the file for
    // Excel to automatically recognize the encoding
    fwrite($cSVFile, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fwrite(
      $cSVFile,
      implode(
        ',',
        array_map(
          $formatCSV,
          $formattedSubscriberFields
        )
      ) . PHP_EOL
    );

    $subscribers = $this->getSubscribers();
    while ($subscribers !== false) {
      $processedSubscribers += count($subscribers);
      foreach ($subscribers as $subscriber) {
        $row = $this->formatSubscriberData($subscriber);
        $row[] = ucwords($subscriber['segment_name']);
        fwrite($cSVFile, implode(',', array_map($formatCSV, $row)) . "\n");
      }
      $subscribers = $this->getSubscribers();
    }
    fclose($cSVFile);
    return $processedSubscribers;
  }

  public function generateXLSX() {
    $processedSubscribers = 0;
    $xLSXWriter = new XLSXWriter();
    $xLSXWriter->setAuthor('MailPoet (www.mailpoet.com)');
    $lastSegment = false;
    $processedSegments = [];

    $subscribers = $this->getSubscribers();
    while ($subscribers !== false) {
      $processedSubscribers += count($subscribers);
      foreach ($subscribers as $i => $subscriber) {
        $currentSegment = ucwords($subscriber['segment_name']);
        // Sheet header (1st row) will be written only if:
        // * This is the first time we're processing a segment
        // * The previous subscriber's segment is different from the current subscriber's segment
        // Header will NOT be written if:
        // * We have already processed the segment. Because SQL results are not
        // sorted by segment name (due to slow queries when using ORDER BY and LIMIT),
        // we need to keep track of processed segments so that we do not create header
        // multiple times when switching from one segment to another and back.
        if ((!count($processedSegments) || $lastSegment !== $currentSegment) &&
          (!in_array($lastSegment, $processedSegments) || !in_array($currentSegment, $processedSegments))
        ) {
          $this->writeXLSX(
            $xLSXWriter,
            $subscriber['segment_name'],
            $this->formattedSubscriberFields
          );
          $processedSegments[] = $currentSegment;
        }
        $lastSegment = ucwords($subscriber['segment_name']);
        // detect RTL language and set Excel to properly display the sheet
        $rTLRegex = '/\p{Arabic}|\p{Hebrew}/u';
        if (!$xLSXWriter->rtl && (
            preg_grep($rTLRegex, $subscriber) ||
            preg_grep($rTLRegex, $this->formattedSubscriberFields))
        ) {
          $xLSXWriter->rtl = true;
        }
        $this->writeXLSX(
          $xLSXWriter,
          $lastSegment,
          $this->formatSubscriberData($subscriber)
        );
      }
      $subscribers = $this->getSubscribers();
    }
    $xLSXWriter->writeToFile($this->exportFile);
    return $processedSubscribers;
  }

  public function writeXLSX($xLSXWriter, $segment, $data) {
    return $xLSXWriter->writeSheetRow(ucwords($segment), $data);
  }

  public function getSubscribers() {
    $subscribers = $this->defaultSubscribersGetter->get();
    if ($subscribers === false) {
      $subscribers = $this->dynamicSubscribersGetter->get();
    }
    return $subscribers;
  }

  public function getExportFileURL($file) {
    return sprintf(
      '%s/%s',
      Env::$tempUrl,
      basename($file)
    );
  }

  public function getExportFile($format) {
    return sprintf(
      $this->exportPath . '/' . self::getFilePrefix() . '%s.%s',
      Security::generateRandomString(15),
      $format
    );
  }

  public function getSubscriberCustomFields() {
    return array_column(
      CustomField::findArray(),
      'name',
      'id'
    );
  }

  public function formatSubscriberFields($subscriberFields, $subscriberCustomFields) {
    $exportFactory = new ImportExportFactory('export');
    $translatedFields = $exportFactory->getSubscriberFields();
    return array_map(function($field) use (
      $translatedFields, $subscriberCustomFields
    ) {
      $field = (isset($translatedFields[$field])) ?
        ucfirst($translatedFields[$field]) :
        ucfirst($field);
      return (isset($subscriberCustomFields[$field])) ?
        ucfirst($subscriberCustomFields[$field]) : $field;
    }, $subscriberFields);
  }

  public function formatSubscriberData($subscriber) {
    return array_map(function($field) use ($subscriber) {
      return $subscriber[$field];
    }, $this->subscriberFields);
  }
}
