<?php
namespace MailPoet\Subscribers\ImportExport\Export;

use MailPoet\Config\Env;
use MailPoet\Subscribers\ImportExport\BootStrapMenu;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Util\XLSXWriter;

class Export {
  public function __construct($data) {
    $this->exportConfirmedOption = $data['exportConfirmedOption'];
    $this->exportFormatOption = $data['exportFormatOption'];
    $this->groupBySegmentOption = $data['groupBySegmentOption'];
    $this->segments = $data['segments'];
    $this->subscribersWithoutSegment = array_search(0, $this->segments);
    $this->subscriberFields = $data['subscriberFields'];
    $this->profilerStart = microtime(true);
    $this->exportFile = sprintf(
      Env::$temp_path . '/mailpoet_export_%s.%s',
      substr(md5(time()), 0, 4),
      $this->exportFormatOption
    );
    $this->exportFileURL = sprintf(
      '%s/%s/%s/%s',
      plugins_url(),
      Env::$plugin_name,
      Env::$temp_name,
      basename($this->exportFile)
    );
  }
  
  function process() {
    $subscribers = SubscriberSegment::
    left_outer_join(
      Subscriber::$_table,
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
      ->select(Segment::$_table . '.name', 'segment_name')
      ->orderByAsc('segment_name')
      ->filter('filterWithCustomFields')
      ->whereIn(SubscriberSegment::$_table . '.segment_id', $this->segments);
    if(!$this->groupBySegmentOption) $subscribers = $subscribers->groupBy(Subscriber::$_table . '.id');
    if($this->exportConfirmedOption) $subscribers = $subscribers->where(Subscriber::$_table . '.status', 'confirmed');
    $subscribers = $subscribers->findArray();
    $formattedSubscriberFields = $this->formatSubscriberFields($this->subscriberFields);
    try {
      if($this->exportFormatOption === 'csv') {
        $CSVFile = fopen($this->exportFile, 'w');
        $formatCSV = function ($row) {
          return '"' . str_replace('"', '\"', $row) . '"';
        };
        // add UTF-8 BOM (3 bytes, hex EF BB BF) at the start of the file for Excel to automatically recognize the encoding
        fwrite($CSVFile, chr(0xEF) . chr(0xBB) . chr(0xBF));
        if($this->groupBySegmentOption) $formattedSubscriberFields[] = __('List');
        fwrite($CSVFile, implode(",", array_map($formatCSV, $formattedSubscriberFields)) . "\n");
        foreach ($subscribers as $subscriber) {
          $row = array_map(function ($field) use ($subscriber) {
            return $subscriber[$field];
          }, $this->subscriberFields);
          if($this->groupBySegmentOption) $row[] = $subscriber['segment_name'];
          fwrite($CSVFile, implode(",", array_map($formatCSV, $row)) . "\n");
        }
        fclose($CSVFile);
      } else {
        $writer = new XLSXWriter();
        $writer->setAuthor('MailPoet (www.mailpoet.com)');
        $headerRow = array($formattedSubscriberFields);
        $segment = null;
        $rows = array();
        foreach ($subscribers as $subscriber) {
          if($segment && $segment != $subscriber['segment_name'] && $this->groupBySegmentOption) {
            $writer->writeSheet(array_merge($headerRow, $rows), ucwords($segment));
            $rows = array();
          }
          // detect RTL language and set Excel to properly display the sheet
          if(!$writer->rtl && preg_grep('/\p{Arabic}|\p{Hebrew}/u', $subscriber)) {
            $writer->rtl = true;
          }
          $rows[] = array_map(function ($field) use ($subscriber) {
            return $subscriber[$field];
          }, $this->subscriberFields);
          $segment = $subscriber['segment_name'];
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
      )
    );
  }

  function formatSubscriberFields($subscriberFields) {
    $bootStrapMenu = new BootStrapMenu();
    $translatedFields = $bootStrapMenu->getSubscriberFields();
    return array_map(function ($field) use ($translatedFields) {
      return (isset($translatedFields[$field])) ?
        ucfirst($translatedFields[$field]) :
        ucfirst($field);
    }, $subscriberFields);
  }

  function timeExecution() {
    $profilerEnd = microtime(true);
    return ($profilerEnd - $this->profilerStart) / 60;
  }
}