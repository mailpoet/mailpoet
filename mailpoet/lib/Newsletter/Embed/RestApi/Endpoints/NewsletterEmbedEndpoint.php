<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Embed\RestApi\Endpoints;

use MailPoet\API\REST\Endpoint;
use MailPoet\WP\Functions as WPFunctions;

abstract class NewsletterEmbedEndpoint extends Endpoint {
  public function checkPermissions(): bool {
    return WPFunctions::get()->currentUserCan('edit_posts');
  }
}
