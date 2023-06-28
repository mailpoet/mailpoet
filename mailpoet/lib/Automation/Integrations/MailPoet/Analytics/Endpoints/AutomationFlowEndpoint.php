<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Analytics\Endpoints;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\Exceptions\NotFoundException;
use MailPoet\Automation\Engine\Mappers\AutomationMapper;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\MailPoet\Analytics\Entities\Query;
use MailPoet\Validator\Builder;

class AutomationFlowEndpoint extends Endpoint {

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var AutomationMapper */
  private $automationMapper;

  public function __construct(
    AutomationStorage $automationStorage,
    AutomationMapper $automationMapper
  ) {
    $this->automationStorage = $automationStorage;
    $this->automationMapper = $automationMapper;
  }

  public function handle(Request $request): Response {
    //@ToDo Get the correct automation version
    //@ToDo Get the automation flow data
    $automation = $this->automationStorage->getAutomation(absint($request->getParam('id')));
    if (!$automation) {
      throw new NotFoundException(__('Automation not found', 'mailpoet'));
    }
    $data = [
      'automation' => $this->automationMapper->buildAutomation($automation),
    ];
    return new Response($data);
  }

  public static function getRequestSchema(): array {
    return [
      'id' => Builder::integer()->required(),
      'query' => Query::getRequestSchema(),
    ];
  }
}
