<?php
namespace MailPoet\Router;

use MailPoet\ImportExport\Import\MailChimp;
use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;

if(!defined('ABSPATH')) exit;

class ImportExport {
  function getMailChimpLists($data) {
    $mailChimp = new MailChimp($data['api_key']);
    wp_send_json($mailChimp->getLists());
  }

  function getMailChimpSubscribers($data) {
    $mailChimp = new MailChimp($data['api_key']);
    wp_send_json($mailChimp->getSubscribers($data['lists']));
  }

  function addSegment($data) {
    $segment = Segment::createOrUpdate($data);
    wp_send_json(
      ($segment->id) ?
        array(
          'result' => true,
          'segment' => $segment->asArray()
        ) :
        array(
          'result' => false
        )
    );
  }

  function addCustomField($data) {
    $customField = CustomField::create();
    $customField->hydrate($data);
    $result = $customField->save();
    wp_send_json(
      ($result) ?
        array(
          'result' => true,
          'customField' => $customField->asArray()
        ) :
        array(
          'result' => false
        )
    );
  }

  function processImport($data) {
    $import = new \MailPoet\ImportExport\Import\Import(json_decode($data, true));
    wp_send_json($import->process());
  }

  function processExport($data) {
    $export = new \MailPoet\ImportExport\Export\Export(json_decode($data, true));
    wp_send_json($export->process());
  }
}