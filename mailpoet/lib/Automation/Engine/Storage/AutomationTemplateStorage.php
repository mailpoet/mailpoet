<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Data\AutomationTemplate;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Integrations\MailPoet\Templates\AutomationBuilder;
use MailPoet\WP\Functions as WPFunctions;

class AutomationTemplateStorage {


  /** @var AutomationTemplate[]  */
  private $templates = [];

  /** @var AutomationBuilder  */
  private $builder;

  /** @var WPFunctions  */
  private $wp;

  public function __construct(
    AutomationBuilder $builder,
    WPFunctions $wp
  ) {
    $this->builder = $builder;
    $this->wp = $wp;
    $this->templates = $this->createTemplates();
  }

  public function getTemplateBySlug(string $slug): ?AutomationTemplate {
    foreach ($this->templates as $template) {
      if ($template->getSlug() === $slug) {
        return $template;
      }
    }
    return null;
  }

  /** @return AutomationTemplate[] */
  public function getTemplates(int $category = null): array {
    if (!$category) {
      return $this->templates;
    }
    return array_values(
      array_filter(
        $this->templates,
        function(AutomationTemplate $template) use ($category): bool {
            return $template->getCategory() === $category;
        }
    )
    );
  }

  private function createTemplates(): array {
    $subscriberWelcomeEmail = new AutomationTemplate(
      'subscriber-welcome-email',
      AutomationTemplate::CATEGORY_WELCOME,
      __(
        "Send a welcome email when someone subscribes to your list. Optionally, you can choose to send this email after a specified period.",
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
      AutomationTemplate::TYPE_FREE_ONLY
    );

    $userWelcomeEmail = new AutomationTemplate(
      'user-welcome-email',
      AutomationTemplate::CATEGORY_WELCOME,
      __(
        "Send a welcome email when a new WordPress user registers to your website. Optionally, you can choose to send this email after a specified period.",
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
      AutomationTemplate::TYPE_FREE_ONLY
    );

    $subscriberWelcomeSeries = new AutomationTemplate(
      'subscriber-welcome-series',
      AutomationTemplate::CATEGORY_WELCOME,
      __(
        "Welcome new subscribers and start building a relationship with them. Send an email immediately after someone subscribes to your list to introduce your brand and a follow-up two days later to keep the conversation going.",
        'mailpoet'
      ),
      $this->builder->createFromSequence(
        __('Welcome series for new subscribers', 'mailpoet'),
        []
      ),
      AutomationTemplate::TYPE_PREMIUM
    );

    $userWelcomeSeries = new AutomationTemplate(
      'user-welcome-series',
      AutomationTemplate::CATEGORY_WELCOME,
      __(
        "Welcome new WordPress users to your site. Send an email immediately after a WordPress user registers. Send a follow-up email two days later with more in-depth information.",
        'mailpoet'
      ),
      $this->builder->createFromSequence(
        __('Welcome series for new WordPress users', 'mailpoet'),
        []
      ),
      AutomationTemplate::TYPE_PREMIUM
    );

    $firstPurchase = new AutomationTemplate(
      'first-purchase',
      AutomationTemplate::CATEGORY_WOOCOMMERCE,
      __(
        "Welcome your first-time customers by sending an email with a special offer for their next purchase. Make them feel appreciated within your brand.",
        'mailpoet'
      ),
      $this->builder->createFromSequence(
        __('Celebrate first-time buyers', 'mailpoet'),
        []
      ),
      AutomationTemplate::TYPE_COMING_SOON
    );

    $loyalCustomers = new AutomationTemplate(
      'loyal-customers',
      AutomationTemplate::CATEGORY_WOOCOMMERCE,
      __(
        "These are your most important customers. Make them feel special by sending a thank you note for supporting your brand.",
        'mailpoet'
      ),
      $this->builder->createFromSequence(
        __('Thank loyal customers', 'mailpoet'),
        []
      ),
      AutomationTemplate::TYPE_COMING_SOON
    );

    $abandonedCart = new AutomationTemplate(
      'abandoned-cart',
      AutomationTemplate::CATEGORY_ABANDONED_CART,
      __(
        "Nudge your shoppers to complete the purchase after they added a product to the cart but haven't completed the order. Offer a coupon code as a last resort to convert them to customers.",
        'mailpoet'
      ),
      $this->builder->createFromSequence(
        __('Abandoned cart reminder', 'mailpoet'),
        []
      ),
      AutomationTemplate::TYPE_COMING_SOON
    );



    $templates = $this->wp->applyFilters(Hooks::AUTOMATION_TEMPLATES, [
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
