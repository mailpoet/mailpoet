<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Analytics\Endpoints;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\Exceptions\NotFoundException;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\MailPoet\Analytics\Entities\Query;
use MailPoet\Automation\Integrations\MailPoet\Analytics\Factories\OrderControllerFactory;
use MailPoet\Validator\Builder;

class OrderEndpoint extends Endpoint {

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var OrderControllerFactory */
  private $orderControllerFactory;

  public function __construct(
    AutomationStorage $automationStorage,
    OrderControllerFactory $orderControllerFactory
  ) {
    $this->automationStorage = $automationStorage;
    $this->orderControllerFactory = $orderControllerFactory;
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
    $controller = $this->orderControllerFactory->getOrderController();
    $result = $controller->getOrdersForAutomation($automation, $query);
    return new Response($result);
  }
}
