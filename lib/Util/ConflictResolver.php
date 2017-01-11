<?php
namespace MailPoet\Util;

class ConflictResolver {
  function init() {
    add_action('mailpoet_conflict_url_query_parameters', array($this, 'resolveRouterUrlQueryParametersConflict'));
  }

  function resolveRouterUrlQueryParametersConflict() {
    // prevents other plugins from overtaking URL query parameters 'action=' and 'endpoint='
    unset($_GET['endpoint'], $_GET['action']);
  }
}