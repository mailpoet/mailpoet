<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Analytics\Controller;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Integrations\MailPoet\Analytics\Entities\Query;

interface SubscriberController {
  public function getSubscribersForAutomation(Automation $automation, Query $query): array;
}
