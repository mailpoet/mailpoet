<?php

namespace MailPoet\Config;

use MailPoet\Subscribers\ImportExport\PersonalDataExporters\SegmentsExporter;

class PersonalDataExporters {

  function init() {
    add_filter('wp_privacy_personal_data_exporters', array($this, 'registerSegmentsExporter'));
  }

  function registerSegmentsExporter($exporters) {
    $exporters[] = array(
      'exporter_friendly_name' => __('MailPoet Lists'),
      'callback' => array(new SegmentsExporter(), 'export'),
    );
    return $exporters;
  }

}