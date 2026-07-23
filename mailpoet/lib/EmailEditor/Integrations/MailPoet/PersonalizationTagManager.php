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
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\Newsletter;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\OrderReviewUrl;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\PersonalizationTagLinkNormalizer;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\Site;
use MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags\Subscriber;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\DBAL\Exception\InvalidFieldNameException;
use MailPoetVendor\Doctrine\DBAL\Exception\TableNotFoundException;

class PersonalizationTagManager {
  /**
   * URL tokens whose value is the same for every recipient, so they can be
   * resolved once per queue before link tracking hashes hrefs. Resolving this
   * early gives these links UTM params and readable URLs in stats.
   *
   * A token that is not listed here still works: it is stored as the token
   * and resolved per recipient by PersonalizationTagLinkResolver. A token
   * whose value depends on the recipient or the order (activation link,
   * order URLs) must not be listed, because its callback would run here
   * without any recipient context and its result would be baked into the
   * email for everyone.
   */
  private const PRE_TRACKING_URL_TOKENS = [
    '[mailpoet/site-homepage-url]',
    '[woocommerce/site-homepage-url]',
    '[woocommerce/store-url]',
    '[woocommerce/my-account-url]',
  ];

  private Subscriber $subscriber;
  private Site $site;
  private Link $link;
  private Newsletter $newsletter;
  private Date $date;
  private OrderReviewUrl $orderReviewUrl;
  private WPFunctions $wp;
  private PersonalizationTagLinkNormalizer $linkNormalizer;
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
    PersonalizationTagLinkNormalizer $linkNormalizer,
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
    $this->linkNormalizer = $linkNormalizer;
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
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE],
        Personalization_Tag::VALUE_TYPE_TEXT
      ));
      $registry->register(new Personalization_Tag(
        __('Last Name', 'mailpoet'),
        'mailpoet/subscriber-lastname',
        __('Subscriber', 'mailpoet'),
        [$this->subscriber, 'getLastName'],
        ['default' => __('subscriber', 'mailpoet')],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE],
        Personalization_Tag::VALUE_TYPE_TEXT
      ));
      $registry->register(new Personalization_Tag(
        __('Email', 'mailpoet'),
        'mailpoet/subscriber-email',
        __('Subscriber', 'mailpoet'),
        [$this->subscriber, 'getEmail'],
        [],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE],
        Personalization_Tag::VALUE_TYPE_TEXT
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
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE],
        Personalization_Tag::VALUE_TYPE_TEXT
      ));
      $registry->register(new Personalization_Tag(
        __('Total Number of Subscribers', 'mailpoet'),
        'mailpoet/subscriber-count',
        __('Subscriber', 'mailpoet'),
        [$this->subscriber, 'getCount'],
        [],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE],
        Personalization_Tag::VALUE_TYPE_TEXT
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
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE],
        Personalization_Tag::VALUE_TYPE_TEXT
      ));

      // Date Personalization Tags
      $registry->register(new Personalization_Tag(
        __('Current day of the month number', 'mailpoet'),
        'mailpoet/date-day',
        __('Date', 'mailpoet'),
        [$this->date, 'getDay'],
        [],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE],
        Personalization_Tag::VALUE_TYPE_TEXT
      ));
      $registry->register(new Personalization_Tag(
        __('Current day of the month in ordinal form, i.e. 2nd, 3rd, 4th, etc.', 'mailpoet'),
        'mailpoet/date-day-ordinal',
        __('Date', 'mailpoet'),
        [$this->date, 'getDayOrdinal'],
        [],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE],
        Personalization_Tag::VALUE_TYPE_TEXT
      ));
      $registry->register(new Personalization_Tag(
        __('Full name of current day', 'mailpoet'),
        'mailpoet/date-day-name',
        __('Date', 'mailpoet'),
        [$this->date, 'getDayName'],
        [],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE],
        Personalization_Tag::VALUE_TYPE_TEXT
      ));
      $registry->register(new Personalization_Tag(
        __('Current month number', 'mailpoet'),
        'mailpoet/date-month',
        __('Date', 'mailpoet'),
        [$this->date, 'getMonth'],
        [],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE],
        Personalization_Tag::VALUE_TYPE_TEXT
      ));
      $registry->register(new Personalization_Tag(
        __('Full name of current month', 'mailpoet'),
        'mailpoet/date-month-name',
        __('Date', 'mailpoet'),
        [$this->date, 'getMonthName'],
        [],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE],
        Personalization_Tag::VALUE_TYPE_TEXT
      ));
      $registry->register(new Personalization_Tag(
        __('Year', 'mailpoet'),
        'mailpoet/date-year',
        __('Date', 'mailpoet'),
        [$this->date, 'getYear'],
        [],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE],
        Personalization_Tag::VALUE_TYPE_TEXT
      ));

      // Site Personalization Tags
      $registry->register(new Personalization_Tag(
        __('Site Title', 'mailpoet'),
        'mailpoet/site-title',
        __('Site', 'mailpoet'),
        [$this->site, 'getTitle'],
        [],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE],
        Personalization_Tag::VALUE_TYPE_TEXT
      ));
      $registry->register(new Personalization_Tag(
        __('Site Description', 'mailpoet'),
        'mailpoet/site-description',
        __('Site', 'mailpoet'),
        [$this->site, 'getDescription'],
        [],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE],
        Personalization_Tag::VALUE_TYPE_TEXT
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
      $registry->register(new Personalization_Tag(
        __('Tracking opt-out URL', 'mailpoet'),
        'mailpoet/subscription-tracking-opt-out-url',
        __('Link', 'mailpoet'),
        [$this->link, 'getSubscriptionTrackingOptOutUrl'],
        [],
        null,
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE]
      ));
      return $registry;
    });

    // Runs after rendering and before link tracking hashes the hrefs.
    $this->wp->addFilter(
      'mailpoet_sending_newsletter_render_after_pre_process',
      [$this, 'normalizeTrackedLinks']
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
        [EmailEditor::MAILPOET_EMAIL_POST_TYPE],
        Personalization_Tag::VALUE_TYPE_TEXT
      ));
    }
  }

  /**
   * @param array<string, string> $emailContent rendered email parts keyed by "html" and "text"
   * @return array<string, string>
   */
  public function normalizeTrackedLinks(array $emailContent): array {
    $registry = Email_Editor_Container::container()->get(Personalization_Tags_Registry::class);
    return $this->linkNormalizer->normalize($emailContent, $this->getPreTrackingUrlTokens($registry));
  }

  /**
   * Display names of all registered personalization tags, keyed by token.
   *
   * Extends the registry with the tags of every known automation subject first, so
   * subject-dependent tags (order, customer, ...) are included on requests that have
   * no automation run, such as admin pages. Premium uses this to label links stored
   * as tag tokens in the campaign stats.
   *
   * @return array<string, string>
   */
  public function getTokenDisplayNames(): array {
    $subjects = array_merge([], ...array_values($this->getCategoryToSubjectsMapping()));
    $this->extendPersonalizationTagsBySubjects(array_unique($subjects));

    $names = [];
    $registry = Email_Editor_Container::container()->get(Personalization_Tags_Registry::class);
    foreach ($registry->get_all() as $tag) {
      $names[$tag->get_token()] = $tag->get_name();
    }
    return $names;
  }

  /**
   * @return array<string, string>
   */
  private function getPreTrackingUrlTokens(Personalization_Tags_Registry $registry): array {
    $tokens = [];
    foreach (self::PRE_TRACKING_URL_TOKENS as $token) {
      $tag = $registry->get_by_token($token);
      if (!$tag) {
        continue;
      }
      try {
        $resolved = $tag->execute_callback([]);
      } catch (\Throwable $e) {
        // A broken tag callback must not block newsletter pre-processing
        continue;
      }
      // A tag that produced no URL is left out, so the link falls back to
      // symbolic tracking and click-time resolution like any other token.
      if ($resolved !== '') {
        $tokens[$token] = $resolved;
      }
    }
    return $tokens;
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
            $postTypes,
            $tag->get_value_type()
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
