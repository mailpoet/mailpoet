<?php
namespace MailPoet\Subscribers\ImportExport\Export;

use MailPoet\Config\Env;
use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Subscribers\ImportExport\ImportExportFactory;
use MailPoet\Util\Helpers;
use MailPoet\Util\Security;
use MailPoet\Util\XLSXWriter;

class Export {
  public $export_confirmed_option;
  public $export_format_option;
  public $group_by_segment_option;
  public $segments;
  public $subscribers_without_segment;
  public $subscriber_fields;
  public $subscriber_custom_fields;
  public $formatted_subscriber_fields;
  public $export_path;
  public $export_file;
  public $export_file_URL;
  public $subscriber_batch_size;

  public function __construct($data) {
    set_time_limit(0);
    $this->export_confirmed_option = $data['export_confirmed_option'];
    $this->export_format_option = $data['export_format_option'];
    $this->group_by_segment_option = $data['group_by_segment_option'];
    $this->segments = $data['segments'];
    $this->subscribers_without_segment = array_search(0, $this->segments);
    $this->subscriber_fields = $data['subscriber_fields'];
    $this->subscriber_custom_fields = $this->getSubscriberCustomFields();
    $this->formatted_subscriber_fields = $this->formatSubscriberFields(
      $this->subscriber_fields,
      $this->subscriber_custom_fields
    );
    $this->export_path = Env::$temp_path;
    $this->export_file = $this->getExportFile($this->export_format_option);
    $this->export_file_URL = $this->getExportFileURL($this->export_file);
    $this->subscriber_batch_size = 15000;
  }

  function process() {
    try {
      if(is_writable($this->export_path) === false) {
        throw new \Exception(__("The export file could not be saved on the server.", 'mailpoet'));
      }
      if(!extension_loaded('zip')) {
        throw new \Exception(__('Export requires a ZIP extension to be installed on the host.', 'mailpoet'));
      }
      $processed_subscribers = call_user_func(
        array(
          $this,
          'generate' . strtoupper($this->export_format_option)
        )
      );
    } catch(\Exception $e) {
      throw new \Exception($e->getMessage());
    }
    return array(
      'totalExported' => $processed_subscribers,
      'exportFileURL' => $this->export_file_URL
    );
  }

  function generateCSV() {
    $processed_subscribers = 0;
    $offset = 0;
    $formatted_subscriber_fields = $this->formatted_subscriber_fields;
    $CSV_file = fopen($this->export_file, 'w');
    $format_CSV = function($row) {
      return '"' . str_replace('"', '\"', $row) . '"';
    };
    // add UTF-8 BOM (3 bytes, hex EF BB BF) at the start of the file for
    // Excel to automatically recognize the encoding
    fwrite($CSV_file, chr(0xEF) . chr(0xBB) . chr(0xBF));
    if($this->group_by_segment_option) {
      $formatted_subscriber_fields[] = __('List', 'mailpoet');
    }
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
    do {
      $subscribers = $this->getSubscribers($offset, $this->subscriber_batch_size);
      $processed_subscribers += count($subscribers);
      foreach($subscribers as $subscriber) {
        $row = $this->formatSubscriberData($subscriber);
        if($this->group_by_segment_option) {
          $row[] = ucwords($subscriber['segment_name']);
        }
        fwrite($CSV_file, implode(',', array_map($format_CSV, $row)) . "\n");
      }
      $offset += $this->subscriber_batch_size;
    } while(count($subscribers) === $this->subscriber_batch_size);
    fclose($CSV_file);
    return $processed_subscribers;
  }

  function generateXLSX() {
    $processed_subscribers = 0;
    $offset = 0;
    $XLSX_writer = new XLSXWriter();
    $XLSX_writer->setAuthor('MailPoet (www.mailpoet.com)');
    $last_segment = false;
    $processed_segments = array();
    do {
      $subscribers = $this->getSubscribers($offset, $this->subscriber_batch_size);
      $processed_subscribers += count($subscribers);
      foreach($subscribers as $i => $subscriber) {
        $current_segment = ucwords($subscriber['segment_name']);
        // Sheet header (1st row) will be written only if:
        // * This is the first time we're processing a segment
        // * "Group by subscriber option" is turned AND the previous subscriber's
        // segment is different from the current subscriber's segment
        // Header will NOT be written if:
        // * We have already processed the segment. Because SQL results are not
        // sorted by segment name (due to slow queries when using ORDER BY and LIMIT),
        // we need to keep track of processed segments so that we do not create header
        // multiple times when switching from one segment to another and back.
        if((!count($processed_segments) ||
            ($last_segment !== $current_segment && $this->group_by_segment_option)
          ) &&
          (!in_array($last_segment, $processed_segments) ||
            !in_array($current_segment, $processed_segments)
          )
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
        if(!$XLSX_writer->rtl && (
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
      $offset += $this->subscriber_batch_size;
    } while(count($subscribers) === $this->subscriber_batch_size);
    $XLSX_writer->writeToFile($this->export_file);
    return $processed_subscribers;
  }

  function writeXLSX($XLSX_writer, $segment, $data) {
    return $XLSX_writer->writeSheetRow(
      ($this->group_by_segment_option) ?
        ucwords($segment) :
        __('All Lists', 'mailpoet'),
      $data
    );
  }

  function getSubscribers($offset, $limit) {
    // JOIN subscribers on segment and subscriber_segment tables
    $subscribers = Subscriber::left_outer_join(
      SubscriberSegment::$_table,
      array(
        Subscriber::$_table . '.id',
        '=',
        SubscriberSegment::$_table . '.subscriber_id'
      )
    )
      ->left_outer_join(
        Segment::$_table,
        array(
          Segment::$_table . '.id',
          '=',
          SubscriberSegment::$_table . '.segment_id'
        )
      )
      ->filter('filterWithCustomFieldsForExport')
      ->groupBy(Subscriber::$_table . '.id');

    if($this->subscribers_without_segment !== false) {
      // if there are subscribers who do not belong to any segment, use
      // a CASE function to group them under "Not In Segment"
      $subscribers = $subscribers
        ->selectExpr(
          'MAX(CASE WHEN ' . Segment::$_table . '.name IS NOT NULL ' .
          'THEN ' . Segment::$_table . '.name ' .
          'ELSE "' . __('Not In Segment', 'mailpoet') . '" END) as segment_name'
        )
        ->whereRaw(
          SubscriberSegment::$_table . '.segment_id IN (' .
          rtrim(str_repeat('?,', count($this->segments)), ',') . ') ' .
          'OR ' . SubscriberSegment::$_table . '.segment_id IS NULL ',
          $this->segments
        );
    } else {
      // if all subscribers belong to at least one segment, select the segment name
      $subscribers = $subscribers
        ->selectExpr('MAX('.Segment::$_table . '.name) as segment_name')
        ->whereIn(SubscriberSegment::$_table . '.segment_id', $this->segments);
    }
    if($this->group_by_segment_option) {
      $subscribers = $subscribers->groupBy(Segment::$_table . '.id');
    }
    if($this->export_confirmed_option) {
      // select only subscribers with "subscribed" status
      $subscribers =
        $subscribers->where(Subscriber::$_table . '.status', 'subscribed');
    }
    $subscribers = $subscribers
      ->whereNull(Subscriber::$_table . '.deleted_at')
      ->offset($offset)
      ->limit($limit)
      ->findArray();
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
      $this->export_path . '/MailPoet_export_%s.%s',
      Security::generateRandomString(15),
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
    $export_factory = new ImportExportFactory();
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
