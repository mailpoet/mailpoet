<?php
namespace MailPoet\Subscribers\ImportExport\Export;

use MailPoet\Config\Env;
use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Subscribers\ImportExport\BootStrapMenu;
use MailPoet\Util\Helpers;
use MailPoet\Util\XLSXWriter;

class Export {
  public $export_confirmed_option;
  public $export_format_option;
  public $group_by_segment_option;
  public $segments;
  public $subscribers_without_segment;
  public $subscriber_fields;
  public $export_path;
  public $export_file;
  public $export_file_URL;
  public $profiler_start;

  public function __construct($data) {
    $this->export_confirmed_option = $data['export_confirmed_option'];
    $this->export_format_option = $data['export_format_option'];
    $this->group_by_segment_option = $data['group_by_segment_option'];
    $this->segments = $data['segments'];
    $this->subscribers_without_segment = array_search(0, $this->segments);
    $this->subscriber_fields = $data['subscriber_fields'];
    $this->export_path = Env::$temp_path;
    $this->export_file = $this->getExportFile($this->export_format_option);
    $this->export_file_URL = $this->getExportFileURL($this->export_file);
    $this->profiler_start = microtime(true);
  }

  function process() {
    try {
      if(is_writable($this->export_path) === false) {
        throw new \Exception(__("Couldn't save export file on the server."));
      }
      $subscribers = $this->getSubscribers();
      $subscriber_custom_fields = $this->getSubscriberCustomFields();
      $formatted_subscriber_fields = $this->formatSubscriberFields(
        $this->subscriber_fields,
        $subscriber_custom_fields
      );
      if($this->export_format_option === 'csv') {
        $CSV_file = fopen($this->export_file, 'w');
        $format_CSV = function($row) {
          return '"' . str_replace('"', '\"', $row) . '"';
        };
        // add UTF-8 BOM (3 bytes, hex EF BB BF) at the start of the file for
        // Excel to automatically recognize the encoding
        fwrite($CSV_file, chr(0xEF) . chr(0xBB) . chr(0xBF));
        if($this->group_by_segment_option) {
          $formatted_subscriber_fields[] = __('Segment');
        }
        fwrite(
          $CSV_file,
          implode(
            ',',
            array_map(
              $format_CSV,
              $formatted_subscriber_fields
            )
          ) . "\n"
        );
        foreach($subscribers as $subscriber) {
          $row = $this->formatSubscriberData($subscriber);
          if($this->group_by_segment_option) {
            $row[] = ucwords($subscriber['segment_name']);
          }
          fwrite($CSV_file, implode(',', array_map($format_CSV, $row)) . "\n");
        }
        fclose($CSV_file);
      } else {
        $writer = new XLSXWriter();
        $writer->setAuthor('MailPoet (www.mailpoet.com)');
        $header_row = array($formatted_subscriber_fields);
        $last_segment = false;
        $rows = array();
        foreach($subscribers as $subscriber) {
          if($last_segment && $last_segment !== $subscriber['segment_name'] &&
            $this->group_by_segment_option
          ) {
            $writer->writeSheet(
              array_merge($header_row, $rows), ucwords($last_segment)
            );
            $rows = array();
          }
          // detect RTL language and set Excel to properly display the sheet
          $RTL_regex = '/\p{Arabic}|\p{Hebrew}/u';
          if(!$writer->rtl && (
              preg_grep($RTL_regex, $subscriber) ||
              preg_grep($RTL_regex, $formatted_subscriber_fields))
          ) {
            $writer->rtl = true;
          }
          $rows[] = $this->formatSubscriberData($subscriber);
          $last_segment = $subscriber['segment_name'];
        }
        $writer->writeSheet(
          array_merge($header_row, $rows),
          ($this->group_by_segment_option) ?
            ucwords($subscriber['segment_name']) :
            __('All Segments')
        );
        $writer->writeToFile($this->export_file);
      }
    } catch(\Exception $e) {
      return array(
        'result' => false,
        'errors' => array($e->getMessage())
      );
    }
    return array(
      'result' => true,
      'data' => array(
        'totalExported' => count($subscribers),
        'exportFileURL' => $this->export_file_URL
      ),
      'profiler' => $this->timeExecution()
    );
  }

  function getSubscribers() {
    $subscribers = Subscriber::
    left_outer_join(
      SubscriberSegment::$_table,
      array(
        Subscriber::$_table . '.id',
        '=',
        SubscriberSegment::$_table . '.subscriber_id'
      ))
      ->left_outer_join(
        Segment::$_table,
        array(
          Segment::$_table . '.id',
          '=',
          SubscriberSegment::$_table . '.segment_id'
        ))
      ->orderByAsc('segment_name')
      ->filter('filterWithCustomFieldsForExport');
    if($this->subscribers_without_segment !== false) {
      $subscribers = $subscribers
        ->selectExpr('CASE WHEN ' . Segment::$_table . '.name IS NOT NULL ' .
                     'THEN ' . Segment::$_table . '.name ' .
                     'ELSE "' . __('Not In Segment') . '" END as segment_name'
        )
        ->whereRaw(
          SubscriberSegment::$_table . '.segment_id IN (' .
          rtrim(str_repeat('?,', count($this->segments)), ',') . ') ' .
          'OR ' . SubscriberSegment::$_table . '.segment_id IS NULL ',
          $this->segments
        );
    } else {
      $subscribers = $subscribers
        ->select(Segment::$_table . '.name', 'segment_name')
        ->whereIn(SubscriberSegment::$_table . '.segment_id', $this->segments);
    }
    if(!$this->group_by_segment_option) {
      $subscribers =
        $subscribers->groupBy(Subscriber::$_table . '.id');
    }
    if($this->export_confirmed_option) {
      $subscribers =
        $subscribers->where(Subscriber::$_table . '.status', 'subscribed');
    }
    $subscribers = $subscribers->whereNull(Subscriber::$_table . '.deleted_at');

    return $subscribers->findArray();
  }

  function getExportFileURL($file) {
    return sprintf(
      '%s/%s',
      Env::$temp_URL,
      basename($file)
    );
  }

  function getExportFile($format) {
    return sprintf(
      $this->export_path . '/MailPoet_export_%s.%s',
      substr(md5(time()), 0, 4),
      $format
    );
  }

  function getSubscriberCustomFields() {
    return Helpers::arrayColumn(
      CustomField::findArray(),
      'name',
      'id'
    );
  }

  function formatSubscriberFields($subscriber_fields, $subscriber_custom_fields) {
    $bootstrap_menu = new BootStrapMenu();
    $translated_fields = $bootstrap_menu->getSubscriberFields();
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

  function timeExecution() {
    $profiler_end = microtime(true);
    return ($profiler_end - $this->profiler_start) / 60;
  }
}