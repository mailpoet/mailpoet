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

  public function __construct() {
    $this->wp = new WPFunctions;
  }

  public function getEventOptions($data) {
    $query = (!empty($data['query'])) ? $data['query'] : null;
    $filter = (!empty($data['filter'])) ? $data['filter'] : null;
    $emailSlug = (!empty($data['email_slug'])) ? $data['email_slug'] : null;
    $eventSlug = (!empty($data['event_slug'])) ? $data['event_slug'] : null;

    if (!$query || !$filter || !$emailSlug || !$eventSlug) {
      return $this->errorResponse(
        [
          APIError::BAD_REQUEST => WPFunctions::get()->__('Improperly formatted request.', 'mailpoet'),
        ]
      );
    }

    $automaticEmails = new \MailPoet\AutomaticEmails\AutomaticEmails();
    $event = $automaticEmails->getAutomaticEmailEventBySlug($emailSlug, $eventSlug);
    $eventFilter = (!empty($event['options']['remoteQueryFilter'])) ? $event['options']['remoteQueryFilter'] : null;

    return ($eventFilter === $filter && WPFunctions::get()->hasFilter($eventFilter)) ?
      $this->successResponse($this->wp->applyFilters($eventFilter, $query)) :
      $this->errorResponse(
        [
          APIError::BAD_REQUEST => WPFunctions::get()->__('Automatic email event filter does not exist.', 'mailpoet'),
        ]
      );
  }

  public function getEventShortcodes($data) {
    $emailSlug = (!empty($data['email_slug'])) ? $data['email_slug'] : null;
    $eventSlug = (!empty($data['event_slug'])) ? $data['event_slug'] : null;

    if (!$emailSlug || !$eventSlug) {
      return $this->errorResponse(
        [
          APIError::BAD_REQUEST => WPFunctions::get()->__('Improperly formatted request.', 'mailpoet'),
        ]
      );
    }

    $automaticEmails = new \MailPoet\AutomaticEmails\AutomaticEmails();
    $automaticEmail = $automaticEmails->getAutomaticEmailBySlug($emailSlug);
    $event = $automaticEmails->getAutomaticEmailEventBySlug($emailSlug, $eventSlug);

    if (!$event) {
      return $this->errorResponse(
        [
          APIError::BAD_REQUEST => WPFunctions::get()->__('Automatic email event does not exist.', 'mailpoet'),
        ]
      );
    }

    $eventShortcodes = (!empty($event['shortcodes']) && is_array($event['shortcodes'])) ?
      [
        $automaticEmail['title'] => $event['shortcodes'],
      ] :
      null;

    return $this->successResponse($eventShortcodes);
  }
}
