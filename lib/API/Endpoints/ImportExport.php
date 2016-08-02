<?php
namespace MailPoet\API\Endpoints;

use MailPoet\Subscribers\ImportExport\Import\MailChimp;
use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;

if(!defined('ABSPATH')) exit;

class ImportExport {
  function getMailChimpLists($data) {
    $mailChimp = new MailChimp($data['api_key']);
    return $mailChimp->getLists();
  }

  function getMailChimpSubscribers($data) {
    $mailChimp = new MailChimp($data['api_key']);
    return $mailChimp->getSubscribers($data['lists']);
  }

  function addSegment($data) {
    $segment = Segment::createOrUpdate($data);
    return (
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
    return (
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
    $import = new \MailPoet\Subscribers\ImportExport\Import\Import(
      json_decode($data, true)
    );
    return $import->process();
  }

  function processExport($data) {
    $export = new \MailPoet\Subscribers\ImportExport\Export\Export(
      json_decode($data, true)
    );
    return $export->process();
  }
}