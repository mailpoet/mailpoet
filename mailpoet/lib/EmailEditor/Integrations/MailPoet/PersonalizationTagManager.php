<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\Link;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\LinksToShortcodesConvertor;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\Site;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\Subscriber;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\WP\Functions as WPFunctions;

class PersonalizationTagManager {
  private Subscriber $subscriber;
  private Site $site;
  private Link $link;
  private WPFunctions $wp;
  private LinksToShortcodesConvertor $linksToShortcodesConvertor;
  private AutomationStorage $automationStorage;
  private Registry $registry;
  private NewslettersRepository $newslettersRepository;

  public function __construct(
    Subscriber $subscriber,
    Site $site,
    Link $link,
    WPFunctions $wp,
    LinksToShortcodesConvertor $linksToShortcodesConvertor,
    AutomationStorage $automationStorage,
    Registry $registry,
    NewslettersRepository $newslettersRepository
  ) {
    $this->subscriber = $subscriber;
    $this->site = $site;
    $this->link = $link;
    $this->wp = $wp;
    $this->linksToShortcodesConvertor = $linksToShortcodesConvertor;
    $this->automationStorage = $automationStorage;
    $this->registry = $registry;
    $this->newslettersRepository = $newslettersRepository;
  }

  /**
   * Extend personalization tags for a specific post.
   * Called via woocommerce_email_editor_personalization_tags_for_post action.
   *
   * @param int|string $postId The WordPress post ID
   */
  public function extendPersonalizationTagsForPost($postId): void {
    $postId = (int)$postId;

    $newsletter = $this->newslettersRepository->findOneBy(['wpPost' => $postId]);
    if (!$newsletter || (!$newsletter->isAutomation() && !$newsletter->isAutomationTransactional())) {
      return;
    }

    $automationId = $newsletter->getOptionValue('automationId');
    if ($automationId) {
      $this->extendPersonalizationTagsByAutomationSubjects((int)$automationId);
    }
  }

  /**
   * Extend WooCommerce tags with current automation context.
   *
   * @param int $automationId The automation ID
   */
  public function extendPersonalizationTagsByAutomationSubjects(int $automationId): void {
    $registry = Email_Editor_Container::container()->get(
      Personalization_Tags_Registry::class
    );

    $availableSubjects = $this->getAutomationSubjects($automationId);
    $this->extendWooCommerceTagsForMailPoet($registry, $availableSubjects);

    $this->wp->applyFilters('mailpoet_automation_email_extend_personalization_tags', $registry, $availableSubjects);
  }

  public function initialize() {
    // Extend tags when WooCommerce Email Editor requests personalization tags for a specific post
    $this->wp->addAction('woocommerce_email_editor_personalization_tags_for_post', [$this, 'extendPersonalizationTagsForPost']);

    $this->wp->addFilter('woocommerce_email_editor_register_personalization_tags', function( Personalization_Tags_Registry $registry ): Personalization_Tags_Registry {
      // Subscriber Personalization Tags
      $registry->register(new Personalization_Tag(
        __('First Name', 'mailpoet'),
        'mailpoet/subscriber-firstname',
        __('Subscriber', 'mailpoet'),
        [$this->subscriber, 'getFirstName'],
        ['default' => __('subscriber', 'mailpoet')],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE]
      ));
      $registry->register(new Personalization_Tag(
        __('Last Name', 'mailpoet'),
        'mailpoet/subscriber-lastname',
        __('Subscriber', 'mailpoet'),
        [$this->subscriber, 'getLastName'],
        ['default' => __('subscriber', 'mailpoet')],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE]
      ));
      $registry->register(new Personalization_Tag(
        __('Email', 'mailpoet'),
        'mailpoet/subscriber-email',
        __('Subscriber', 'mailpoet'),
        [$this->subscriber, 'getEmail'],
        [],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE]
      ));
      $registry->register(new Personalization_Tag(
        __('Activation Link', 'mailpoet'),
        'mailpoet/subscriber-activation-link',
        __('Subscriber', 'mailpoet'),
        [$this->subscriber, 'getActivationLink'],
        [],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE]
      ));

      // Site Personalization Tags
      $registry->register(new Personalization_Tag(
        __('Site Title', 'mailpoet'),
        'mailpoet/site-title',
        __('Site', 'mailpoet'),
        [$this->site, 'getTitle'],
        [],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE]
      ));
      $registry->register(new Personalization_Tag(
        __('Site Description', 'mailpoet'),
        'mailpoet/site-description',
        __('Site', 'mailpoet'),
        [$this->site, 'getDescription'],
        [],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE]
      ));
      $registry->register(new Personalization_Tag(
        __('Homepage URL', 'mailpoet'),
        'mailpoet/site-homepage-url',
        __('Site', 'mailpoet'),
        [$this->site, 'getHomepageURL'],
        [],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE]
      ));

      // Links registration
      $registry->register(new Personalization_Tag(
        __('Unsubscribe URL', 'mailpoet'),
        'mailpoet/subscription-unsubscribe-url',
        __('Link', 'mailpoet'),
        [$this->link, 'getSubscriptionUnsubscribeUrl'],
        [],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE]
      ));
      $registry->register(new Personalization_Tag(
        __('Manage subscription URL', 'mailpoet'),
        'mailpoet/subscription-manage-url',
        __('Link', 'mailpoet'),
        [$this->link, 'getSubscriptionManageUrl'],
        [],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE]
      ));
      $registry->register(new Personalization_Tag(
        __('View in browser URL', 'mailpoet'),
        'mailpoet/newsletter-view-in-browser-url',
        __('Link', 'mailpoet'),
        [$this->link, 'getNewsletterViewInBrowserUrl'],
        [],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE]
      ));
      return $registry;
    });

    // Convert links to shortcodes before sending the email
    // This is a temporary solution so that we are able to integrate the new personalization tags
    // It is needed until we have a proper solution for the personalization tags in the MailPoet Link tracking system
    $this->wp->addFilter(
      'mailpoet_sending_newsletter_render_after_pre_process',
      [$this, 'convertLinksToShortcodes']
    );
  }

  public function convertLinksToShortcodes(array $emailContent): array {
    if (!isset($emailContent['html'])) {
      return $emailContent;
    }
    $emailContent['html'] = $this->linksToShortcodesConvertor->convertLinkTagsToShortcodes($emailContent['html']);
    return $emailContent;
  }

  /**
   * Extend WooCommerce personalization tags to also work with MailPoet email post type.
   * This allows WooCommerce Order and Customer tags to be used in MailPoet automation emails
   * when the appropriate subjects (order, customer) are available.
   */
  public function extendWooCommerceTagsForMailPoet(Personalization_Tags_Registry $registry, array $availableSubjects): Personalization_Tags_Registry {
    $tags = $registry->get_all();

    foreach ($tags as $tag) {
      $postTypes = $tag->get_post_types();

      // If this is a WooCommerce tag (Order, Customer, Site, Store) and doesn't already support mailpoet_email
      if (!empty($postTypes) && !in_array(EmailEditor::MAILPOET_EMAIL_POST_TYPE, $postTypes, true)) {
        // Check if we should extend this tag based on its category and available subjects
        $category = $tag->get_category();
        $shouldExtend = $this->shouldExtendTagCategory($category, $availableSubjects);

        if ($shouldExtend) {
          // Add mailpoet_email to the list of supported post types
          $postTypes[] = EmailEditor::MAILPOET_EMAIL_POST_TYPE;

          $registry->unregister($tag);
          // Re-register the tag with extended post types
          $registry->register(new Personalization_Tag(
            $tag->get_name(),
            $tag->get_token(),
            $tag->get_category(),
            $tag->get_callback(),
            $tag->get_attributes(),
            $tag->get_value_to_insert(),
            $postTypes
          ));
        }
      }
    }

    return $registry;
  }

  /**
   * Get the category to subjects mapping for filtering tags.
   * This mapping defines which automation subjects are required for each tag category.
   *
   * @return array<string, string[]> Map of category names to required subject keys
   */
  private function getCategoryToSubjectsMapping(): array {
    $mapping = [
      'Order' => ['woocommerce:order'],
      'Customer' => ['woocommerce:customer'],
      'Store' => [], // Always available (no subjects required)
    ];

    /**
     * Filter the category to subjects mapping for personalization tag filtering.
     * This allows extensions (like MailPoet Premium) to add their own category mappings.
     *
     * @param array<string, string[]> $mapping Map of category names to required subject keys
     */
    $filtered = $this->wp->applyFilters('mailpoet_personalization_tags_category_subjects_mapping', $mapping);
    return is_array($filtered) ? $filtered : $mapping;
  }

  /**
   * Determine if a tag category should be extended to MailPoet emails.
   * This checks if the required subjects are available for the current automation.
   *
   * @param string $category The tag category (e.g., 'Order', 'Customer', 'Site')
   * @param string[] $availableSubjects Available subject keys
   * @return bool Whether to extend tags in this category
   */
  private function shouldExtendTagCategory(string $category, array $availableSubjects): bool {
    $categoryToSubjects = $this->getCategoryToSubjectsMapping();

    // Unknown categories should not be extended
    if (!array_key_exists($category, $categoryToSubjects)) {
      return false;
    }

    $requiredSubjects = $categoryToSubjects[$category];

    // If no subjects required (e.g., Store), always extend
    if (empty($requiredSubjects)) {
      return true;
    }

    // Check if at least one required subject is available
    foreach ($requiredSubjects as $required) {
      if (in_array($required, $availableSubjects, true)) {
        return true;
      }
    }

    return false;
  }

  /**
   * Get subject keys available in an automation based on its triggers.
   *
   * @param int $automationId The automation ID
   * @return string[] Array of subject keys
   */
  private function getAutomationSubjects(int $automationId): array {
    $automation = $this->automationStorage->getAutomation($automationId);
    if (!$automation) {
      return [];
    }

    $subjects = [];
    foreach ($automation->getTriggers() as $triggerStep) {
      $trigger = $this->registry->getTrigger($triggerStep->getKey());
      if ($trigger) {
        $subjectKeys = $trigger->getSubjectKeys();
        $subjects = array_merge($subjects, $subjectKeys);
      }
    }

    return array_unique($subjects);
  }
}
