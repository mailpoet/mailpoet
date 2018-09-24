<?php
namespace MailPoet\Mailer\Methods\ErrorMappers;

use MailPoet\Mailer\MailerError;

trait ConnectionErrorMapperTrait {
  function getConnectionError($message) {
    return new MailerError(
      MailerError::OPERATION_CONNECT,
      MailerError::LEVEL_HARD,
      $message
    );
  }
}
