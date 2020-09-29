<?php

namespace MailPoet\API\JSON\ResponseBuilders;

use MailPoet\Entities\FormEntity;

class FormsResponseBuilder {
  const DATE_FORMAT = 'Y-m-d H:i:s';

  public function build(FormEntity $form) {
    return [
      'id' => (string)$form->getId(), // (string) for BC
      'name' => $form->getName(),
      'status' => $form->getStatus(),
      'body' => $form->getBody(),
      'settings' => $form->getSettings(),
      'styles' => $form->getStyles(),
      'created_at' => $form->getCreatedAt()->format(self::DATE_FORMAT),
      'updated_at' => $form->getUpdatedAt()->format(self::DATE_FORMAT),
      'deleted_at' => ($deletedAt = $form->getDeletedAt()) ? $deletedAt->format(self::DATE_FORMAT) : null,
    ];
  }
}
