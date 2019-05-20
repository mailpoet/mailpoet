<?php
namespace MailPoet\Listing;

if (!defined('ABSPATH')) exit;

class BulkActionFactory {
  /** @var array */
  private $actions = [];

  function registerAction($model_class, $bulk_action_method, $action_class) {
    $this->ensureMethodExists($action_class, $bulk_action_method);
    $this->actions[$model_class][$bulk_action_method] = $action_class;
  }

  function getActionClass($model_class, $bulk_action_method) {
    $resulting_class = $model_class;
    if (!empty($this->actions[$model_class][$bulk_action_method])) {
      $resulting_class = $this->actions[$model_class][$bulk_action_method];
    }
    $this->ensureMethodExists($resulting_class, $bulk_action_method);
    return $resulting_class;
  }

  private function ensureMethodExists($action_class, $bulk_action_method) {
    if (!method_exists($action_class, $bulk_action_method)) {
      throw new \Exception(
        (is_object($action_class) ? get_class($action_class) : $action_class) . ' has no method "' . $bulk_action_method . '"'
      );
    }
  }
}
