<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\Date;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\Link;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\LinksToShortcodesConvertor;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\Newsletter;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\OrderReviewUrl;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\Site;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\Subscriber;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\DBAL\Exception\InvalidFieldNameException;
use MailPoetVendor\Doctrine\DBAL\Exception\TableNotFoundException;

class PersonalizationTagManager {
  private Subscriber $subscriber;
  private Site $site;
  private Link $link;
  private Newsletter $newsletter;
  private Date $date;
  private OrderReviewUrl $orderReviewUrl;
  private WPFunctions $wp;
  private LinksToShortcodesConvertor $linksToShortcodesConvertor;
  private AutomationStorage $automationStorage;
  private Registry $registry;
  private NewslettersRepository $newslettersRepository;
  private CustomFieldsRepository $customFieldsRepository;

  public function __construct(
    Subscriber $subscriber,
    Site $site,
    Link $link,
    Newsletter $newsletter,
    Date $date,
    OrderReviewUrl $orderReviewUrl,
    WPFunctions $wp,
    LinksToShortcodesConvertor $linksToShortcodesConvertor,
    AutomationStorage $automationStorage,
    Registry $registry,
    NewslettersRepository $newslettersRepository,
    CustomFieldsRepository $customFieldsRepository
  ) {
    $this->subscriber = $subscriber;
    $this->site = $site;
    $this->link = $link;
    $this->newsletter = $newsletter;
    $this->date = $date;
    $this->orderReviewUrl = $orderReviewUrl;
    $this->wp = $wp;
    $this->linksToShortcodesConvertor = $linksToShortcodesConvertor;
    $this->automationStorage = $automationStorage;
    $this->registry = $registry;
    $this->newslettersRepository = $newslettersRepository;
    $this->customFieldsRepository = $customFieldsRepository;
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
    $this->extendPersonalizationTagsBySubjects($this->getAutomationSubjects($automationId));
  }

  /**
   * @param string[] $availableSubjects
   */
  public function extendPersonalizationTagsBySubjects(array $availableSubjects): void {
    $registry = Email_Editor_Container::container()->get(
      Personalization_Tags_Registry::class
    );

    $this->extendWooCommerceTagsForMailPoet($registry, $availableSubjects);

    $this->wp->applyFilters('mailpoet_automation_email_extend_personalization_tags', $registry, $availableSubjects);
  }

  public function initialize() {
    // Extend tags when WooCommerce Email Editor requests personalization tags for a specific post
    $this->wp->addAction('woocommerce_email_editor_personalization_tags_for_post', [$this, 'extendPersonalizationTagsForPost']);
    $this->wp->addAction('mailpoet_automation_email_extend_personalization_tags_for_sending', [$this, 'extendPersonalizationTagsBySubjects']);

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
      $registry->register(new Personalization_Tag(
        __('WordPress User Display Name', 'mailpoet'),
        'mailpoet/subscriber-displayname',
        __('Subscriber', 'mailpoet'),
        [$this->subscriber, 'getDisplayName'],
        ['default' => __('member', 'mailpoet')],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE]
      ));
      $registry->register(new Personalization_Tag(
        __('Total Number of Subscribers', 'mailpoet'),
        'mailpoet/subscriber-count',
        __('Subscriber', 'mailpoet'),
        [$this->subscriber, 'getCount'],
        [],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE]
      ));
      $this->registerSubscriberCustomFieldTags($registry);

      // Newsletter Personalization Tags
      $registry->register(new Personalization_Tag(
        __('Newsletter Subject', 'mailpoet'),
        'mailpoet/newsletter-subject',
        __('Newsletter', 'mailpoet'),
        [$this->newsletter, 'getSubject'],
        [],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE]
      ));

      // Date Personalization Tags
      $registry->register(new Personalization_Tag(
        __('Current day of the month number', 'mailpoet'),
        'mailpoet/date-day',
        __('Date', 'mailpoet'),
        [$this->date, 'getDay'],
        [],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE]
      ));
      $registry->register(new Personalization_Tag(
        __('Current day of the month in ordinal form, i.e. 2nd, 3rd, 4th, etc.', 'mailpoet'),
        'mailpoet/date-day-ordinal',
        __('Date', 'mailpoet'),
        [$this->date, 'getDayOrdinal'],
        [],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE]
      ));
      $registry->register(new Personalization_Tag(
        __('Full name of current day', 'mailpoet'),
        'mailpoet/date-day-name',
        __('Date', 'mailpoet'),
        [$this->date, 'getDayName'],
        [],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE]
      ));
      $registry->register(new Personalization_Tag(
        __('Current month number', 'mailpoet'),
        'mailpoet/date-month',
        __('Date', 'mailpoet'),
        [$this->date, 'getMonth'],
        [],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE]
      ));
      $registry->register(new Personalization_Tag(
        __('Full name of current month', 'mailpoet'),
        'mailpoet/date-month-name',
        __('Date', 'mailpoet'),
        [$this->date, 'getMonthName'],
        [],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE]
      ));
      $registry->register(new Personalization_Tag(
        __('Year', 'mailpoet'),
        'mailpoet/date-year',
        __('Date', 'mailpoet'),
        [$this->date, 'getYear'],
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
    $this->wp->addFilter(
      'mailpoet_automation_email_personalize_html_after',
      [$this, 'restorePersonalizedLinkHrefs'],
      10,
      2
    );
    $this->wp->addFilter(
      'mailpoet_automation_email_personalize_text_after',
      [$this, 'restorePersonalizedLinkUrls'],
      10,
      2
    );
  }

  private function registerSubscriberCustomFieldTags(Personalization_Tags_Registry $registry): void {
    try {
      $customFields = $this->customFieldsRepository->findAllActive();
    } catch (InvalidFieldNameException | TableNotFoundException $e) {
      // The custom_fields schema may be mid-migration during a plugin update (e.g. the deleted_at
      // column added in 5.33.1). Skip custom-field tags for this request rather than fataling; they
      // register on the next request once the migration completes.
      return;
    }
    foreach ($customFields as $customField) {
      $customFieldId = (int)$customField->getId();
      $registry->register(new Personalization_Tag(
        $customField->getName(),
        'mailpoet/subscriber-cf-' . $customFieldId,
        __('Subscriber', 'mailpoet'),
        function (array $context, array $args = []) use ($customFieldId): string {
          return $this->subscriber->getCustomField($customFieldId, $context, $args);
        },
        [],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE]
      ));
    }
  }

  public function convertLinksToShortcodes(array $emailContent): array {
    if (!isset($emailContent['html'])) {
      return $emailContent;
    }
    $emailContent['html'] = $this->linksToShortcodesConvertor->convertLinkTagsToShortcodes(
      $emailContent['html'],
      $this->getPreTrackingUrlTokens()
    );
    return $emailContent;
  }

  /**
   * @param array<string, mixed> $context
   */
  public function restorePersonalizedLinkHrefs(string $html, array $context = []): string {
    return $this->linksToShortcodesConvertor->restorePersonalizedLinkHrefs($html, $this->getPersonalizedUrlTokens($context));
  }

  /**
   * @param array<string, mixed> $context
   */
  public function restorePersonalizedLinkUrls(string $content, array $context = []): string {
    return $this->linksToShortcodesConvertor->restorePersonalizedLinkUrls($content, $this->getPersonalizedUrlTokens($context));
  }

  /**
   * @param array<string, mixed> $context
   * @return array<string, string>
   */
  private function getPersonalizedUrlTokens(array $context): array {
    return [
      '[woocommerce/order-review-url]' => $this->orderReviewUrl->getUrl($context),
    ];
  }

  /**
   * @return array<string, string>
   */
  private function getPreTrackingUrlTokens(): array {
    return [
      '[mailpoet/site-homepage-url]' => $this->site->getHomepageURL([]),
    ];
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

    $this->registerOrderReviewUrlTag($registry, $availableSubjects);

    return $registry;
  }

  /**
   * @param string[] $availableSubjects
   */
  private function registerOrderReviewUrlTag(Personalization_Tags_Registry $registry, array $availableSubjects): void {
    if (!$this->shouldExtendTagCategory('Order', $availableSubjects)) {
      return;
    }

    if (!$this->orderReviewUrl->isSupported()) {
      return;
    }

    if ($registry->get_by_token('[woocommerce/order-review-url]')) {
      return;
    }

    $registry->register(new Personalization_Tag(
      __('Order Review URL', 'mailpoet'),
      'woocommerce/order-review-url',
      __('Order', 'mailpoet'),
      [$this->orderReviewUrl, 'getUrl'],
      [],
      null,
      [EmailEditor::MAILPOET_EMAIL_POST_TYPE]
    ));
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
    if (!is_array($filtered)) {
      return $mapping;
    }
    $normalized = [];
    foreach ($filtered as $category => $subjects) {
      if (!is_string($category) || !is_array($subjects)) {
        continue;
      }
      $normalized[$category] = array_values(array_filter($subjects, 'is_string'));
    }
    return $normalized;
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
