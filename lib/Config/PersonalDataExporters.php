<?php

namespace MailPoet\Config;

use MailPoet\Subscribers\ImportExport\PersonalDataExporters\NewsletterClicksExporter;
use MailPoet\Subscribers\ImportExport\PersonalDataExporters\NewslettersExporter;
use MailPoet\Subscribers\ImportExport\PersonalDataExporters\SegmentsExporter;
use MailPoet\Subscribers\ImportExport\PersonalDataExporters\SubscriberExporter;
use MailPoet\WP\Functions as WPFunctions;

class PersonalDataExporters {

  function init() {
    WPFunctions::get()->addFilter('wp_privacy_personal_data_exporters', array($this, 'registerSubscriberExporter'));
    WPFunctions::get()->addFilter('wp_privacy_personal_data_exporters', array($this, 'registerSegmentsExporter'));
    WPFunctions::get()->addFilter('wp_privacy_personal_data_exporters', array($this, 'registerNewslettersExporter'));
    WPFunctions::get()->addFilter('wp_privacy_personal_data_exporters', array($this, 'registerNewsletterClicksExporter'));
  }

  function registerSegmentsExporter($exporters) {
    $exporters[] = array(
      'exporter_friendly_name' => WPFunctions::get()->__('MailPoet Lists', 'mailpoet'),
      'callback' => array(new SegmentsExporter(), 'export'),
    );
    return $exporters;
  }

  function registerSubscriberExporter($exporters) {
    $exporters[] = array(
      'exporter_friendly_name' => WPFunctions::get()->__('MailPoet Subscriber Data', 'mailpoet'),
      'callback' => array(new SubscriberExporter(), 'export'),
    );
    return $exporters;
  }

  function registerNewslettersExporter($exporters) {
    $exporters[] = array(
      'exporter_friendly_name' => WPFunctions::get()->__('MailPoet Emails', 'mailpoet'),
      'callback' => array(new NewslettersExporter(), 'export'),
    );
    return $exporters;
  }

  function registerNewsletterClicksExporter($exporters) {
    $exporters[] = array(
      'exporter_friendly_name' => WPFunctions::get()->__('MailPoet Email Clicks', 'mailpoet'),
      'callback' => array(new NewsletterClicksExporter(), 'export'),
    );
    return $exporters;
  }

}
