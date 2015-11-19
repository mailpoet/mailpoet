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
use Symfony\Component\Console\Helper\Helper;

class Export {
  public function __construct($data) {
    $this->exportConfirmedOption = $data['exportConfirmedOption'];
    $this->exportFormatOption = $data['exportFormatOption'];
    $this->groupBySegmentOption = $data['groupBySegmentOption'];
    $this->segments = $data['segments'];
    $this->subscribersWithoutSegment = array_search(0, $this->segments);
    $this->subscriberFields = $data['subscriberFields'];
    $this->exportFile = $this->getExportFile($this->exportFormatOption);
    $this->exportFileURL = $this->getExportFileURL($this->exportFile);
    $this->profilerStart = microtime(true);
  }

  function process() {
    $subscribers = $this->getSubscribers();
    $subscriberCustomFields = $this->getSubscriberCustomFields();
    $formattedSubscriberFields = $this->formatSubscriberFields(
      $this->subscriberFields,
      $subscriberCustomFields
    );
    try {
      if($this->exportFormatOption === 'csv') {
        $CSVFile = fopen($this->exportFile, 'w');
        $formatCSV = function ($row) {
          return '"' . str_replace('"', '\"', $row) . '"';
        };
        // add UTF-8 BOM (3 bytes, hex EF BB BF) at the start of the file for
        // Excel to automatically recognize the encoding
        fwrite($CSVFile, chr(0xEF) . chr(0xBB) . chr(0xBF));
        if($this->groupBySegmentOption) {
          $formattedSubscriberFields[] = __('List');
        }
        fwrite(
          $CSVFile,
          implode(
            ',',
            array_map(
              $formatCSV,
              $formattedSubscriberFields
            )
          ) . "\n"
        );
        foreach ($subscribers as $subscriber) {
          $row = $this->formatSubscriberData(
            $subscriber,
            $formattedSubscriberFields
          );
          if($this->groupBySegmentOption) {
            $row[] = ucwords($subscriber['segment_name']);
          }
          fwrite($CSVFile, implode(',', array_map($formatCSV, $row)) . "\n");
        }
        fclose($CSVFile);
      } else {
        $writer = new XLSXWriter();
        $writer->setAuthor('MailPoet (www.mailpoet.com)');
        $headerRow = array($formattedSubscriberFields);
        $lastSegment = false;
        $rows = array();
        foreach ($subscribers as $subscriber) {
          if($lastSegment && $lastSegment !== $subscriber['segment_name'] &&
            $this->groupBySegmentOption
          ) {
            $writer->writeSheet(
              array_merge($headerRow, $rows), ucwords($lastSegment)
            );
            $rows = array();
          }
          // detect RTL language and set Excel to properly display the sheet
          $RTLRegex = '/\p{Arabic}|\p{Hebrew}/u';
          if(!$writer->rtl && (
              preg_grep($RTLRegex, $subscriber) ||
              preg_grep($RTLRegex, $formattedSubscriberFields))
          ) {
            $writer->rtl = true;
          }
          $rows[] = $this->formatSubscriberData(
            $subscriber,
            $formattedSubscriberFields
          );
          $lastSegment = $subscriber['segment_name'];
        }
        $writer->writeSheet(array_merge($headerRow, $rows), 'MailPoet');
        $writer->writeToFile($this->exportFile);
      }
    } catch (Exception $e) {
      return array(
        'result' => false,
        'error' => $e->getMessage()
      );
    }
    return array(
      'result' => true,
      'data' => array(
        'totalExported' => count($subscribers),
        'exportFileURL' => $this->exportFileURL
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
    if($this->subscribersWithoutSegment !== false) {
      $subscribers = $subscribers
        ->selectExpr('CASE WHEN ' . Segment::$_table . '.name IS NOT NULL ' .
                     'THEN ' . Segment::$_table . '.name ' .
                     'ELSE "' . __('Not In List') . '" END as segment_name'
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
    if(!$this->groupBySegmentOption) {
      $subscribers =
        $subscribers->groupBy(Subscriber::$_table . '.id');
    }
    if($this->exportConfirmedOption) {
      $subscribers =
        $subscribers->where(Subscriber::$_table . '.status', 'subscribed');
    }
    return $subscribers->findArray();
  }

  function getExportFileURL($file) {
    return sprintf(
      '%s/%s/%s',
      Env::$plugin_url,
      Env::$temp_name,
      basename($file)
    );
  }

  function getExportFile($format) {
    return sprintf(
      Env::$temp_path . '/MailPoet_export_%s.%s',
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

  function formatSubscriberFields($subscriberFields, $subscriberCustomFields) {
    $bootStrapMenu = new BootStrapMenu();
    $translatedFields = $bootStrapMenu->getSubscriberFields();
    return array_map(function ($field) use (
      $translatedFields, $subscriberCustomFields
    ) {
      $field = (isset($translatedFields[$field])) ?
        ucfirst($translatedFields[$field]) :
        ucfirst($field);
      return (isset($subscriberCustomFields[$field])) ?
        $subscriberCustomFields[$field] : $field;
    }, $subscriberFields);
  }

  function formatSubscriberData($subscriber, $subscriberCustomFields) {
    return array_map(function ($field) use (
      $subscriber, $subscriberCustomFields
    ) {
      return (isset($subscriberCustomFields[$field])) ?
        $subscriberCustomFields[$field] :
        $subscriber[$field];
    }, $this->subscriberFields);
  }

  function timeExecution() {
    $profilerEnd = microtime(true);
    return ($profilerEnd - $this->profilerStart) / 60;
  }
}