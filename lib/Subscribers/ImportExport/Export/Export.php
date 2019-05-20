<?php

namespace MailPoet\Subscribers\ImportExport\Export;

use MailPoet\Config\Env;
use MailPoet\Models\CustomField;
use MailPoet\Subscribers\ImportExport\ImportExportFactory;
use function MailPoet\Util\array_column;
use MailPoet\Util\Security;
use MailPoet\Util\XLSXWriter;
use MailPoet\WP\Functions as WPFunctions;

class Export {
  const SUBSCRIBER_BATCH_SIZE = 15000;

  public $export_format_option;
  public $subscriber_fields;
  public $subscriber_custom_fields;
  public $formatted_subscriber_fields;
  public $export_path;
  public $export_file;
  public $export_file_URL;
  public $default_subscribers_getter;
  public $dynamic_subscribers_getter;

  public function __construct($data) {
    if (strpos(@ini_get('disable_functions'), 'set_time_limit') === false) {
      set_time_limit(0);
    }

    $this->default_subscribers_getter = new DefaultSubscribersGetter(
      $data['segments'],
      self::SUBSCRIBER_BATCH_SIZE
    );

    $this->dynamic_subscribers_getter = new DynamicSubscribersGetter(
      $data['segments'],
      self::SUBSCRIBER_BATCH_SIZE
    );

    $this->export_format_option = $data['export_format_option'];
    $this->subscriber_fields = $data['subscriber_fields'];
    $this->subscriber_custom_fields = $this->getSubscriberCustomFields();
    $this->formatted_subscriber_fields = $this->formatSubscriberFields(
      $this->subscriber_fields,
      $this->subscriber_custom_fields
    );
    $this->export_path = self::getExportPath();
    $this->export_file = $this->getExportFile($this->export_format_option);
    $this->export_file_URL = $this->getExportFileURL($this->export_file);
  }

  static function getFilePrefix() {
    return 'MailPoet_export_';
  }

  static function getExportPath() {
    return Env::$temp_path;
  }

  function process() {
    $processed_subscribers = 0;
    $this->default_subscribers_getter->reset();
    try {
      if (is_writable($this->export_path) === false) {
        throw new \Exception(__('The export file could not be saved on the server.', 'mailpoet'));
      }
      if (!extension_loaded('zip')) {
        throw new \Exception(__('Export requires a ZIP extension to be installed on the host.', 'mailpoet'));
      }
      $callback = [
        $this,
        'generate' . strtoupper($this->export_format_option),
      ];
      if (is_callable($callback)) {
        $processed_subscribers = call_user_func($callback);
      }
    } catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }
    return [
      'totalExported' => $processed_subscribers,
      'exportFileURL' => $this->export_file_URL,
    ];
  }

  function generateCSV() {
    $processed_subscribers = 0;
    $formatted_subscriber_fields = $this->formatted_subscriber_fields;
    $CSV_file = fopen($this->export_file, 'w');
    $format_CSV = function($row) {
      return '"' . str_replace('"', '\"', $row) . '"';
    };
    $formatted_subscriber_fields[] = WPFunctions::get()->__('List', 'mailpoet');
    // add UTF-8 BOM (3 bytes, hex EF BB BF) at the start of the file for
    // Excel to automatically recognize the encoding
    fwrite($CSV_file, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fwrite(
      $CSV_file,
      implode(
        ',',
        array_map(
          $format_CSV,
          $formatted_subscriber_fields
        )
      ) . PHP_EOL
    );

    $subscribers = $this->getSubscribers();
    while ($subscribers !== false) {
      $processed_subscribers += count($subscribers);
      foreach ($subscribers as $subscriber) {
        $row = $this->formatSubscriberData($subscriber);
        $row[] = ucwords($subscriber['segment_name']);
        fwrite($CSV_file, implode(',', array_map($format_CSV, $row)) . "\n");
      }
      $subscribers = $this->getSubscribers();
    }
    fclose($CSV_file);
    return $processed_subscribers;
  }

  function generateXLSX() {
    $processed_subscribers = 0;
    $XLSX_writer = new XLSXWriter();
    $XLSX_writer->setAuthor('MailPoet (www.mailpoet.com)');
    $last_segment = false;
    $processed_segments = [];

    $subscribers = $this->getSubscribers();
    while ($subscribers !== false) {
      $processed_subscribers += count($subscribers);
      foreach ($subscribers as $i => $subscriber) {
        $current_segment = ucwords($subscriber['segment_name']);
        // Sheet header (1st row) will be written only if:
        // * This is the first time we're processing a segment
        // * The previous subscriber's segment is different from the current subscriber's segment
        // Header will NOT be written if:
        // * We have already processed the segment. Because SQL results are not
        // sorted by segment name (due to slow queries when using ORDER BY and LIMIT),
        // we need to keep track of processed segments so that we do not create header
        // multiple times when switching from one segment to another and back.
        if ((!count($processed_segments) || $last_segment !== $current_segment) &&
          (!in_array($last_segment, $processed_segments) || !in_array($current_segment, $processed_segments))
        ) {
          $this->writeXLSX(
            $XLSX_writer,
            $subscriber['segment_name'],
            $this->formatted_subscriber_fields
          );
          $processed_segments[] = $current_segment;
        }
        $last_segment = ucwords($subscriber['segment_name']);
        // detect RTL language and set Excel to properly display the sheet
        $RTL_regex = '/\p{Arabic}|\p{Hebrew}/u';
        if (!$XLSX_writer->rtl && (
            preg_grep($RTL_regex, $subscriber) ||
            preg_grep($RTL_regex, $this->formatted_subscriber_fields))
        ) {
          $XLSX_writer->rtl = true;
        }
        $this->writeXLSX(
          $XLSX_writer,
          $last_segment,
          $this->formatSubscriberData($subscriber)
        );
      }
      $subscribers = $this->getSubscribers();
    }
    $XLSX_writer->writeToFile($this->export_file);
    return $processed_subscribers;
  }

  function writeXLSX($XLSX_writer, $segment, $data) {
    return $XLSX_writer->writeSheetRow(ucwords($segment), $data);
  }

  function getSubscribers() {
    $subscribers = $this->default_subscribers_getter->get();
    if ($subscribers === false) {
      $subscribers = $this->dynamic_subscribers_getter->get();
    }
    return $subscribers;
  }

  function getExportFileURL($file) {
    return sprintf(
      '%s/%s',
      Env::$temp_url,
      basename($file)
    );
  }

  function getExportFile($format) {
    return sprintf(
      $this->export_path . '/' . self::getFilePrefix() . '%s.%s',
      Security::generateRandomString(15),
      $format
    );
  }

  function getSubscriberCustomFields() {
    return array_column(
      CustomField::findArray(),
      'name',
      'id'
    );
  }

  function formatSubscriberFields($subscriber_fields, $subscriber_custom_fields) {
    $export_factory = new ImportExportFactory('export');
    $translated_fields = $export_factory->getSubscriberFields();
    return array_map(function($field) use (
      $translated_fields, $subscriber_custom_fields
    ) {
      $field = (isset($translated_fields[$field])) ?
        ucfirst($translated_fields[$field]) :
        ucfirst($field);
      return (isset($subscriber_custom_fields[$field])) ?
        ucfirst($subscriber_custom_fields[$field]) : $field;
    }, $subscriber_fields);
  }

  function formatSubscriberData($subscriber) {
    return array_map(function($field) use ($subscriber) {
      return $subscriber[$field];
    }, $this->subscriber_fields);
  }
}
