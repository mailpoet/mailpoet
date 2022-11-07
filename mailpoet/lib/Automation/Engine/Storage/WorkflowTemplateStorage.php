<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Data\WorkflowTemplate;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Integrations\MailPoet\Templates\WorkflowBuilder;
use MailPoet\WP\Functions as WPFunctions;

class WorkflowTemplateStorage {


  /** @var WorkflowTemplate[]  */
  private $templates = [];

  /** @var WorkflowBuilder  */
  private $builder;

  /** @var WPFunctions  */
  private $wp;

  public function __construct(
    WorkflowBuilder $builder,
    WPFunctions $wp
  ) {
    $this->builder = $builder;
    $this->wp = $wp;
    $this->templates = $this->createTemplates();
  }

  public function getTemplateBySlug(string $slug): ?WorkflowTemplate {
    foreach ($this->templates as $template) {
      if ($template->getSlug() === $slug) {
        return $template;
      }
    }
    return null;
  }

  /** @return WorkflowTemplate[] */
  public function getTemplates(int $category = null): array {
    if (!$category) {
      return $this->templates;
    }
    return array_values(
      array_filter(
        $this->templates,
        function(WorkflowTemplate $template) use ($category): bool {
            return $template->getCategory() === $category;
        }
    )
    );
  }

  private function createTemplates(): array {
    $subscriberWelcomeEmail = new WorkflowTemplate(
      'subscriber-welcome-email',
      WorkflowTemplate::CATEGORY_WELCOME,
      __(
        "Send a welcome email when someone subscribes to your list. Optionally, you can choose to delay this email by a couple of hours or days.",
        'mailpoet'
      ),
      $this->builder->createFromSequence(
        __('Welcome new subscribers', 'mailpoet'),
        [
          'mailpoet:someone-subscribes',
          'core:delay',
          'mailpoet:send-email',
        ],
        [
          [],
          [
            'delay' => 1,
            'delay_type' => 'MINUTES',
          ],
          [],
        ]
      )
    );
    $userWelcomeEmail = new WorkflowTemplate(
      'user-welcome-email',
      WorkflowTemplate::CATEGORY_WELCOME,
      __(
        "Send a welcome email when a new WordPress user registers to your website. Optionally, you can choose to delay this email by a couple of hours or days.",
        'mailpoet'
      ),
      $this->builder->createFromSequence(
        __('Welcome new WordPress users', 'mailpoet'),
        [
          'mailpoet:wp-user-registered',
          'core:delay',
          'mailpoet:send-email',
        ],
        [
          [],
          [
            'delay' => 1,
            'delay_type' => 'MINUTES',
          ],
          [],
        ]
      )
    );

    $templates = $this->wp->applyFilters(Hooks::WORKFLOW_TEMPLATES, [
      $subscriberWelcomeEmail,
      $userWelcomeEmail,
    ]);
    return is_array($templates) ? $templates : [];
  }
}
