<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Endpoints\Automations;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Automation\Engine\API\Endpoint;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Integrations\MailPoet\Templates\TemplateEmailPreviewRenderer;
use MailPoet\Validator\Builder;

class AutomationTemplateEmailPreviewEndpoint extends Endpoint {
  /** @var TemplateEmailPreviewRenderer */
  private $previewRenderer;

  public function __construct(
    TemplateEmailPreviewRenderer $previewRenderer
  ) {
    $this->previewRenderer = $previewRenderer;
  }

  public function handle(Request $request): Response {
    /** @var string|null $patternParam - for PHPStan because strval() doesn't accept a value of mixed */
    $patternParam = $request->getParam('pattern');
    $pattern = strval($patternParam);
    /** @var string|null $subjectParam */
    $subjectParam = $request->getParam('subject');
    $subject = strval($subjectParam ?? '');
    /** @var string|null $preheaderParam */
    $preheaderParam = $request->getParam('preheader');
    $preheader = strval($preheaderParam ?? '');

    $html = $this->previewRenderer->render($pattern, $subject, $preheader);
    if ($html === null) {
      throw Exceptions::automationTemplateEmailPatternNotFound($pattern);
    }

    return new Response(['html' => $html]);
  }

  public static function getRequestSchema(): array {
    return [
      'pattern' => Builder::string()->required(),
      'subject' => Builder::string(),
      'preheader' => Builder::string(),
    ];
  }
}
