<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Builder;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Exceptions\InvalidStateException;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Engine\Storage\AutomationTemplateStorage;
use MailPoet\Automation\Engine\Validation\AutomationValidator;

class CreateAutomationFromTemplateController {
  /** @var AutomationStorage */
  private $storage;

  /** @var AutomationTemplateStorage  */
  private $templateStorage;

  /** @var AutomationValidator */
  private $automationValidator;

  public function __construct(
    AutomationStorage $storage,
    AutomationTemplateStorage $templateStorage,
    AutomationValidator $automationValidator
  ) {
    $this->storage = $storage;
    $this->templateStorage = $templateStorage;
    $this->automationValidator = $automationValidator;
  }

  public function createAutomation(string $slug): Automation {
    $template = $this->templateStorage->getTemplateBySlug($slug);
    if (!$template) {
      throw Exceptions::automationTemplateNotFound($slug);
    }

    $automation = $template->getAutomation();
    $this->automationValidator->validate($automation);
    $automationId = $this->storage->createAutomation($automation);
    $savedAutomation = $this->storage->getAutomation($automationId);
    if (!$savedAutomation) {
      throw new InvalidStateException('Automation not found.');
    }
    return $savedAutomation;
  }
}
