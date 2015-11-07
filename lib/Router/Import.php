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
      (!is_object($segment)) ?
        array(
          'result' => false,
        ) :
        array(
          'result' => true,
          'segment' => $segment->asArray()
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
          'result' => false
        ) :
        array(
          'result' => true,
          'customField' => $customField->asArray()
        )
    );
  }

  function process($data) {
    $data = file_get_contents(dirname(__FILE__) . '/../../export.txt');
    $import = new \MailPoet\Import\Import(json_decode($data, true));
    try {
      wp_send_json($import->process());
    } catch (\Exception $e) {
      wp_send_json(
        array(
          'result' => false,
          'error' => $e->getMessage()
        )
      );
    }
  }
}