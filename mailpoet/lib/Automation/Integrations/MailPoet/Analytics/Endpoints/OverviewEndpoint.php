<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Analytics\Endpoints;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\Exceptions\NotFoundException;
use MailPoet\Automation\Engine\Exceptions\RuntimeException;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\MailPoet\Analytics\Controller\OverviewStatisticsController;
use MailPoet\Automation\Integrations\MailPoet\Analytics\Entities\Query;
use MailPoet\Validator\Builder;

class OverviewEndpoint extends Endpoint {

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var OverviewStatisticsController */
  private $overviewStatisticsController;

  public function __construct(
    AutomationStorage $automationStorage,
    OverviewStatisticsController $overviewStatisticsController
  ) {
    $this->automationStorage = $automationStorage;
    $this->overviewStatisticsController = $overviewStatisticsController;
  }

  public function handle(Request $request): Response {
    $automationid = $request->getParam('id');
    if (!is_int($automationid)) {
      throw new RuntimeException('Invalid automation id');
    }
    $automation = $this->automationStorage->getAutomation($automationid);
    if (!$automation) {
      throw new NotFoundException(__('Automation not found', 'mailpoet'));
    }
    $query = Query::fromRequest($request);

    $result = $this->overviewStatisticsController->getStatisticsForAutomation($automation, $query);
    return new Response($result);
  }

  public static function getRequestSchema(): array {
    return [
      'id' => Builder::integer()->required(),
      'query' => Builder::object(
        [
          'primary' => Builder::object(
            [
              'after' => Builder::string()->formatDateTime()->required(),
              'before' => Builder::string()->formatDateTime()->required(),
            ]
          ),
          'secondary' => Builder::object(
            [
              'after' => Builder::string()->formatDateTime()->required(),
              'before' => Builder::string()->formatDateTime()->required(),
            ]
          ),
        ]
      ),
    ];
  }
}
