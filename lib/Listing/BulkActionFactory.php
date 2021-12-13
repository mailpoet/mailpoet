<?php

namespace MailPoet\Listing;

class BulkActionFactory {
  /** @var array */
  private $actions = [];

  public function registerAction($modelClass, $bulkActionMethod, $actionClass) {
    $this->ensureMethodExists($actionClass, $bulkActionMethod);
    $this->actions[$modelClass][$bulkActionMethod] = $actionClass;
  }

  public function getActionClass($modelClass, $bulkActionMethod) {
    $resultingClass = $modelClass;
    if (!empty($this->actions[$modelClass][$bulkActionMethod])) {
      $resultingClass = $this->actions[$modelClass][$bulkActionMethod];
    }
    $this->ensureMethodExists($resultingClass, $bulkActionMethod);
    return $resultingClass;
  }

  private function ensureMethodExists($actionClass, $bulkActionMethod) {
    if (!method_exists($actionClass, $bulkActionMethod)) {
      throw new \Exception(
        (is_object($actionClass) ? get_class($actionClass) : $actionClass) . ' has no method "' . $bulkActionMethod . '"'
      );
    }
  }
}
