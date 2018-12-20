<?php
namespace MailPoet\Listing;

if(!defined('ABSPATH')) exit;

class BulkActionController {
  /** @var Handler */
  private $handler;

  function __construct(Handler $handler) {
    $this->handler = $handler;
  }

  function apply($model_class, array $data) {
    $bulk_action_method = 'bulk'.ucfirst($data['action']);
    unset($data['action']);

    if(!method_exists($model_class, $bulk_action_method)) {
      throw new \Exception(
        $model_class. ' has no method "'.$bulk_action_method.'"'
      );
    }

    return call_user_func_array(
      array($model_class, $bulk_action_method),
      array($this->handler->getSelection($model_class, $data['listing']), $data)
    );
  }
}
