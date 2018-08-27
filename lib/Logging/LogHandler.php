<?php

namespace MailPoet\Logging;

use MailPoet\Dependencies\Monolog\Handler\AbstractProcessingHandler;
use MailPoet\Models\Log;

class LogHandler extends AbstractProcessingHandler {


  protected function write(array $record) {
    $model = Log::create();
    $model->hydrate([
      'name' => $record['channel'],
      'level' => $record['level'],
      'message' => $record['formatted'],
      'created_at' => $record['datetime']->format('Y-m-d H:i:s'),
    ]);
    $model->save();
  }

}