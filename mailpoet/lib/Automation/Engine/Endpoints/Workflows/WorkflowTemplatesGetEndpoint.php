<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Endpoints\Workflows;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\Data\WorkflowTemplate;
use MailPoet\Automation\Engine\Storage\WorkflowTemplateStorage;
use MailPoet\Validator\Builder;

class WorkflowTemplatesGetEndpoint extends Endpoint {


  private $storage;

  public function __construct(
    WorkflowTemplateStorage $storage
  ) {
    $this->storage = $storage;
  }

  public function handle(Request $request): Response {
    $templates = $this->storage->getTemplates((int)$request->getParam('category'));
    return new Response(array_map(function (WorkflowTemplate $workflow) {
      return $workflow->toArray();
    }, $templates));
  }

  public static function getRequestSchema(): array {
    return [
      'category' => Builder::integer()->nullable(),
    ];
  }
}
