<?php

namespace MailPoet\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Data\WorkflowTemplate;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Integrations\Core\Actions\DelayAction;
use MailPoet\Automation\Integrations\MailPoet\Actions\SendEmailAction;
use MailPoet\Automation\Integrations\MailPoet\Templates\WorkflowBuilder;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SegmentSubscribedTrigger;
use MailPoet\WP\Functions as WPFunctions;

class WorkflowTemplateStorage
{

  /** @var WorkflowTemplate[]  */
  private $templates = [];

  /** @var WorkflowBuilder  */
  private $builder;

  /** @var WPFunctions  */
  private $wp;

  public function __construct(WorkflowBuilder $builder, WPFunctions $wp) {
    $this->builder = $builder;
    $this->wp = $wp;
    $this->templates = $this->createTemplates();
  }

  public function getTemplateBySlug(string $slug) : ?WorkflowTemplate {
    foreach ($this->templates as $template) {
      if ($template->getSlug() === $slug) {
        return $template;
      }
    }
    return null;
  }

  /** @return WorkflowTemplate[] */
  public function getTemplates(int $category = null) : array {
    if (! $category) {
      return $this->templates;
    }
    return array_values(
      array_filter(
        $this->templates,
        function(WorkflowTemplate $template) use ($category) : bool {
            return $template->getCategory() === $category;
        }
    )
    );
  }

  private function createTemplates() : array {
    $simpleWelcomeEmail = new WorkflowTemplate(
      'simple-welcome-email',
      WorkflowTemplate::CATEGORY_WELCOME,
      "Automation template description is going to be here. Let's describe a lot of interesting ideas which incorporated into this beautiful and useful template",
      $this->builder->createFromSequence(
        __('Simple welcome email', 'mailpoet'),
        [
          'mailpoet:segment:subscribed',
          'core:delay',
          'mailpoet:send-email',
        ]
      )
    );

    $templates = $this->wp->applyFilters(Hooks::WORKFLOW_TEMPLATES,[
      $simpleWelcomeEmail,
    ]);
    return is_array($templates)?$templates:[];
  }
}
