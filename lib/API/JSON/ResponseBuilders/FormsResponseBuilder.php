<?php

namespace MailPoet\API\JSON\ResponseBuilders;

use MailPoet\Entities\FormEntity;
use MailPoet\Models\StatisticsForms;

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

  public function buildForListing(array $forms) {
    $data = [];

    foreach ($forms as $form) {
      $form = $this->build($form);
      $form['signups'] = StatisticsForms::getTotalSignups($form['id']);
      $form['segments'] = (
        !empty($form['settings']['segments'])
        ? $form['settings']['segments']
        : []
      );

      $data[] = $form;
    }

    return $data;
  }
}
