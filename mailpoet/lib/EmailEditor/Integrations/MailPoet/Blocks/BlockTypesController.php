<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Blocks;

class BlockTypesController {
  public function initialize(): void {
    $this->registerBlockTypes();
  }

  public function registerBlockTypes() {
    foreach ($this->getBlockTypes() as $type) {
        $block_type_class = __NAMESPACE__ . '\\BlockTypes\\' . $type;
        new $block_type_class();
    }
  }

  private function getBlockTypes() {
    return [
      'EmailContent',
    ];
  }
}
