<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\WP\Functions as WPFunctions;

class AutomaticEmails extends APIEndpoint {
  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SEGMENTS,
  ];

  private $wp;

  function __construct() {
    $this->wp = new WPFunctions;
  }

  function getEventOptions($data) {
    $query = (!empty($data['query'])) ? $data['query'] : null;
    $filter = (!empty($data['filter'])) ? $data['filter'] : null;
    $email_slug = (!empty($data['email_slug'])) ? $data['email_slug'] : null;
    $event_slug = (!empty($data['event_slug'])) ? $data['event_slug'] : null;

    if (!$query || !$filter || !$email_slug || !$event_slug) {
      return $this->errorResponse(
        [
          APIError::BAD_REQUEST => WPFunctions::get()->__('Improperly formatted request.', 'mailpoet'),
        ]
      );
    }

    $automatic_emails = new \MailPoet\AutomaticEmails\AutomaticEmails();
    $event = $automatic_emails->getAutomaticEmailEventBySlug($email_slug, $event_slug);
    $event_filter = (!empty($event['options']['remoteQueryFilter'])) ? $event['options']['remoteQueryFilter'] : null;

    return ($event_filter === $filter && WPFunctions::get()->hasFilter($event_filter)) ?
      $this->successResponse($this->wp->applyFilters($event_filter, $query)) :
      $this->errorResponse(
        [
          APIError::BAD_REQUEST => WPFunctions::get()->__('Automatic email event filter does not exist.', 'mailpoet'),
        ]
      );
  }

  function getEventShortcodes($data) {
    $email_slug = (!empty($data['email_slug'])) ? $data['email_slug'] : null;
    $event_slug = (!empty($data['event_slug'])) ? $data['event_slug'] : null;

    if (!$email_slug || !$event_slug) {
      return $this->errorResponse(
        [
          APIError::BAD_REQUEST => WPFunctions::get()->__('Improperly formatted request.', 'mailpoet'),
        ]
      );
    }

    $automatic_emails = new \MailPoet\AutomaticEmails\AutomaticEmails();
    $automatic_email = $automatic_emails->getAutomaticEmailBySlug($email_slug);
    $event = $automatic_emails->getAutomaticEmailEventBySlug($email_slug, $event_slug);

    if (!$event) {
      return $this->errorResponse(
        [
          APIError::BAD_REQUEST => WPFunctions::get()->__('Automatic email event does not exist.', 'mailpoet'),
        ]
      );
    }

    $event_shortcodes = (!empty($event['shortcodes']) && is_array($event['shortcodes'])) ?
      [
        $automatic_email['title'] => $event['shortcodes'],
      ] :
      null;

    return $this->successResponse($event_shortcodes);
  }
}
