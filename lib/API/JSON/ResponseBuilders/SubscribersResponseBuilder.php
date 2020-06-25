<?php

namespace MailPoet\API\JSON\ResponseBuilders;

use MailPoet\Entities\SubscriberEntity;

class SubscribersResponseBuilder {

  public function build(SubscriberEntity $subscriberEntity): array {
    $data = [
      'id' => $subscriberEntity->getId(),
      'wp_user_id' => $subscriberEntity->getWpUserId(),
      'is_woocommerce_user' => $subscriberEntity->getIsWoocommerceUser(),
      'subscriptions' => [],// TODO
      'unsubscribes' => [],// TODO
      // TODO custom fields
      'status' => $subscriberEntity->getStatus(),
      'last_name' => $subscriberEntity->getLastName(),
      'first_name' => $subscriberEntity->getFirstName(),
      'email' => $subscriberEntity->getEmail(),
    ];

    return $data;
  }
}
