<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\Config\AccessControl;
use MailPoet\Models\NewsletterLink;

class NewsletterLinks extends APIEndpoint {

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SEGMENTS,
  ];

  function get($data = []) {
    $links = NewsletterLink::select(['id', 'url'])->where('newsletter_id', $data['newsletterId'])->findArray();
    return $this->successResponse($links);
  }

}
