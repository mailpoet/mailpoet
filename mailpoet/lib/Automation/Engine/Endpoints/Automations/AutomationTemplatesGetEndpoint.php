<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Endpoints\Automations;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\Data\AutomationTemplate;
use MailPoet\Automation\Engine\Storage\AutomationTemplateStorage;
use MailPoet\Validator\Builder;

class AutomationTemplatesGetEndpoint extends Endpoint {


  private $storage;

  public function __construct(
    AutomationTemplateStorage $storage
  ) {
    $this->storage = $storage;
  }

  public function handle(Request $request): Response {
    $templates = $this->storage->getTemplates((int)$request->getParam('category'));
    return new Response(array_map(function (AutomationTemplate $automation) {
      return $automation->toArray();
    }, $templates));
  }

  public static function getRequestSchema(): array {
    return [
      'category' => Builder::integer()->nullable(),
    ];
  }
}
