<?php

namespace MailPoet\Listing;

class BulkActionController {
  /** @var BulkActionFactory */
  private $factory;

  /** @var Handler */
  private $handler;

  public function __construct(
      BulkActionFactory $factory,
      Handler $handler
  ) {
    $this->factory = $factory;
    $this->handler = $handler;
  }

  public function apply($modelClass, array $data) {
    $bulkActionMethod = 'bulk' . ucfirst($data['action']);
    unset($data['action']);

    $actionClass = $this->factory->getActionClass($modelClass, $bulkActionMethod);
    $callback = [$actionClass, $bulkActionMethod];

    if (is_callable($callback)) {
      return call_user_func_array(
        $callback,
        [$this->handler->getSelection($modelClass, $data['listing']), $data]
      );
    }
  }
}
