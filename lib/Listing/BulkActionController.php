<?php
namespace MailPoet\Listing;

if(!defined('ABSPATH')) exit;

class BulkActionController {
  function apply($model_class, array $data) {
    $bulk_action_method = 'bulk'.ucfirst($data['action']);
    unset($data['action']);

    if(!method_exists($model_class, $bulk_action_method)) {
      throw new \Exception(
        $model_class. ' has no method "'.$bulk_action_method.'"'
      );
    }

    $listing_handler = new Handler();

    return call_user_func_array(
      array($model_class, $bulk_action_method),
      array($listing_handler->getSelection($model_class, $data['listing']), $data)
    );
  }
}
