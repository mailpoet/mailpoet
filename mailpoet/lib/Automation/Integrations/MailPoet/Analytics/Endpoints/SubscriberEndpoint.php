<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Analytics\Endpoints;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\Exceptions\NotFoundException;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\MailPoet\Analytics\Entities\Query;
use MailPoet\Automation\Integrations\MailPoet\Analytics\Factories\SubscriberControllerFactory;
use MailPoet\Validator\Builder;

class SubscriberEndpoint extends Endpoint {

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var SubscriberControllerFactory */
  private $subscriberControllerFactory;

  public function __construct(
    AutomationStorage $automationStorage,
    SubscriberControllerFactory $subscriberControllerFactory
  ) {
    $this->automationStorage = $automationStorage;
    $this->subscriberControllerFactory = $subscriberControllerFactory;
  }

  public static function getRequestSchema(): array {
    return [
      'id' => Builder::integer()->required(),
      'query' => Query::getRequestSchema(),
    ];
  }

  public function handle(Request $request): Response {
    $automation = $this->automationStorage->getAutomation(absint($request->getParam('id')));
    if (!$automation) {
      throw new NotFoundException(__('Automation not found', 'mailpoet'));
    }

    $query = Query::fromRequest($request);
    $controller = $this->subscriberControllerFactory->getSubscriberController();
    $result = $controller->getSubscribersForAutomation($automation, $query);
    return new Response($result);
  }
}
