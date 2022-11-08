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
      ),
      WorkflowTemplate::TYPE_FREE_ONLY
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
      ),
      WorkflowTemplate::TYPE_FREE_ONLY
    );

    $subscriberWelcomeSeries = new WorkflowTemplate(
      'subscriber-welcome-series',
      WorkflowTemplate::CATEGORY_WELCOME,
      __(
        "Welcome new subscribers and start building a relationship with them. Send an email immediately after someone subscribes to your list to introduce your brand and a follow-up two days later to keep the conversation going.",
        'mailpoet'
      ),
      $this->builder->createFromSequence(
        __('Welcome series for new subscribers', 'mailpoet'),
        []
      ),
      WorkflowTemplate::TYPE_PREMIUM
    );

    $userWelcomeSeries = new WorkflowTemplate(
      'user-welcome-series',
      WorkflowTemplate::CATEGORY_WELCOME,
      __(
        "Welcome new WordPress users to your site. Send an email immediately after a WordPress user registers. Send a follow-up email two days later with more in-depth information.",
        'mailpoet'
      ),
      $this->builder->createFromSequence(
        __('Welcome series for new WordPress users', 'mailpoet'),
        []
      ),
      WorkflowTemplate::TYPE_PREMIUM
    );

    $firstPurchase = new WorkflowTemplate(
      'first-purchase',
      WorkflowTemplate::CATEGORY_WOOCOMMERCE,
      __(
        "Welcome your first-time customers by sending an email with a special offer for their next purchase. Make them feel appreciated within your brand.",
        'mailpoet'
      ),
      $this->builder->createFromSequence(
        __('Celebrate first-time buyers', 'mailpoet'),
        []
      ),
      WorkflowTemplate::TYPE_COMING_SOON
    );

    $loyalCustomers = new WorkflowTemplate(
      'loyal-customers',
      WorkflowTemplate::CATEGORY_WOOCOMMERCE,
      __(
        "These are your most important customers. Make them feel special by sending a thank you note for supporting your brand.",
        'mailpoet'
      ),
      $this->builder->createFromSequence(
        __('Thank loyal customers', 'mailpoet'),
        []
      ),
      WorkflowTemplate::TYPE_COMING_SOON
    );

    $abandonedCart = new WorkflowTemplate(
      'abandoned-cart',
      WorkflowTemplate::CATEGORY_ABANDONED_CART,
      __(
        "Nudge your shoppers to complete the purchase after they added a product to the cart but haven't completed the order. Offer a coupon code as a last resort to convert them to customers.",
        'mailpoet'
      ),
      $this->builder->createFromSequence(
        __('Abandoned cart reminder', 'mailpoet'),
        []
      ),
      WorkflowTemplate::TYPE_COMING_SOON
    );



    $templates = $this->wp->applyFilters(Hooks::WORKFLOW_TEMPLATES, [
      $subscriberWelcomeEmail,
      $userWelcomeEmail,
      $subscriberWelcomeSeries,
      $userWelcomeSeries,
      $firstPurchase,
      $loyalCustomers,
      $abandonedCart,
    ]);
    return is_array($templates) ? $templates : [];
  }
}
