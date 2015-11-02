<?php
namespace MailPoet\Router;

use MailPoet\Import\MailChimp;
use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;

if(!defined('ABSPATH')) exit;

class Import {
  function getMailChimpLists($data) {
    $mailChimp = new MailChimp($data['api_key']);
    wp_send_json($mailChimp->getLists());
  }

  function getMailChimpSubscribers($data) {
    $mailChimp = new MailChimp($data['api_key'], $data['lists']);
    wp_send_json($mailChimp->getSubscribers());
  }

  function addSegment($data) {
    $segment = Segment::createOrUpdate($data, $returnObject = true);
    wp_send_json(
      (!is_array($segment)) ?
        array(
          'status' => 'error',
          'message' => $segment
        ) :
        array(
          'status' => 'success',
          'segment' => $segment
        )
    );
  }

  function addCustomField($data) {
    $customField = CustomField::create();
    $customField->hydrate($data);
    $result = $customField->save();
    wp_send_json(
      (!$result) ?
        array(
          'status' => 'error'
        ) :
        array(
          'status' => 'success',
          'customField' => $customField->asArray()
        )
    );
  }

  function process($data) {
    $import = new \MailPoet\Import\Import(json_decode($data, true));
    wp_send_json($import->process());
  }
}