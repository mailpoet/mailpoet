<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Analytics\Endpoints;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\Exceptions\NotFoundException;
use MailPoet\Automation\Engine\Mappers\AutomationMapper;
use MailPoet\Automation\Engine\Storage\AutomationStatisticsStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\MailPoet\Analytics\Controller\AutomationTimeSpanController;
use MailPoet\Automation\Integrations\MailPoet\Analytics\Entities\Query;
use MailPoet\Validator\Builder;

class AutomationFlowEndpoint extends Endpoint {

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var AutomationStatisticsStorage */
  private $automationStatisticsStorage;

  /** @var AutomationMapper */
  private $automationMapper;

  /** @var AutomationTimeSpanController */
  private $automationTimeSpanController;

  public function __construct(
    AutomationStorage $automationStorage,
    AutomationStatisticsStorage $automationStatisticsStorage,
    AutomationMapper $automationMapper,
    AutomationTimeSpanController $automationTimeSpanController
  ) {
    $this->automationStorage = $automationStorage;
    $this->automationStatisticsStorage = $automationStatisticsStorage;
    $this->automationMapper = $automationMapper;
    $this->automationTimeSpanController = $automationTimeSpanController;
  }

  public function handle(Request $request): Response {
    //@ToDo Get the automation flow data
    $automation = $this->automationStorage->getAutomation(absint($request->getParam('id')));
    if (!$automation) {
      throw new NotFoundException(__('Automation not found', 'mailpoet'));
    }
    $query = Query::fromRequest($request);
    $automations = $this->automationTimeSpanController->getAutomationsInTimespan($automation, $query->getAfter(), $query->getBefore());
    if (!count($automations)) {
      throw new NotFoundException(__('The automation did not exist in the selected time span', 'mailpoet'));
    }
    $automation = end($automations);
    $shortStatistics = $this->automationStatisticsStorage->getAutomationStats(
      $automation->getId(),
      null,
      $query->getAfter(),
      $query->getBefore()
    );

    $data = [
      'automation' => $this->automationMapper->buildAutomation($automation, $shortStatistics),
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
