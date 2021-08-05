<?php

namespace MailPoet\Config;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Subscribers\ImportExport\PersonalDataExporters\NewsletterClicksExporter;
use MailPoet\Subscribers\ImportExport\PersonalDataExporters\NewslettersExporter;
use MailPoet\Subscribers\ImportExport\PersonalDataExporters\SegmentsExporter;
use MailPoet\Subscribers\ImportExport\PersonalDataExporters\SubscriberExporter;
use MailPoet\WP\Functions as WPFunctions;

class PersonalDataExporters {
  public function init() {
    WPFunctions::get()->addFilter('wp_privacy_personal_data_exporters', [$this, 'registerSubscriberExporter']);
    WPFunctions::get()->addFilter('wp_privacy_personal_data_exporters', [$this, 'registerSegmentsExporter']);
    WPFunctions::get()->addFilter('wp_privacy_personal_data_exporters', [$this, 'registerNewslettersExporter']);
    WPFunctions::get()->addFilter('wp_privacy_personal_data_exporters', [$this, 'registerNewsletterClicksExporter']);
  }

  public function registerSegmentsExporter($exporters) {
    $exporters[] = [
      'exporter_friendly_name' => WPFunctions::get()->__('MailPoet Lists', 'mailpoet'),
      'callback' => [new SegmentsExporter(), 'export'],
    ];
    return $exporters;
  }

  public function registerSubscriberExporter($exporters) {
    $exporters[] = [
      'exporter_friendly_name' => WPFunctions::get()->__('MailPoet Subscriber Data', 'mailpoet'),
      'callback' => [new SubscriberExporter(), 'export'],
    ];
    return $exporters;
  }

  public function registerNewslettersExporter($exporters) {
    $newsletterExporter = ContainerWrapper::getInstance()->get(NewslettersExporter::class);
    $exporters[] = [
      'exporter_friendly_name' => WPFunctions::get()->__('MailPoet Emails', 'mailpoet'),
      'callback' => [$newsletterExporter, 'export'],
    ];
    return $exporters;
  }

  public function registerNewsletterClicksExporter($exporters) {
    $exporters[] = [
      'exporter_friendly_name' => WPFunctions::get()->__('MailPoet Email Clicks', 'mailpoet'),
      'callback' => [new NewsletterClicksExporter(), 'export'],
    ];
    return $exporters;
  }
}
