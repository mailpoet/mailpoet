<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Config;

use MailPoet\Captcha\CaptchaConstants;
use MailPoet\Captcha\CaptchaRenderer;
use MailPoet\Cron\CronTrigger;
use MailPoet\Cron\Workers\AuthorizedSendingEmailsCheck;
use MailPoet\Cron\Workers\BackfillEngagementData;
use MailPoet\Cron\Workers\InactiveSubscribers;
use MailPoet\Cron\Workers\Mixpanel;
use MailPoet\Cron\Workers\NewsletterTemplateThumbnails;
use MailPoet\Cron\Workers\StatsNotifications\Worker;
use MailPoet\Cron\Workers\SubscriberLinkTokens;
use MailPoet\Cron\Workers\SubscribersLastEngagement;
use MailPoet\Cron\Workers\UnsubscribeTokens;
use MailPoet\Doctrine\WPDB\Connection;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\NewsletterTemplateEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\StatisticsFormEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\UserFlagEntity;
use MailPoet\Mailer\MailerLog;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Referrals\ReferralDetector;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Segments\WP;
use MailPoet\Services\Bridge;
use MailPoet\Settings\Pages;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\UserFlagsRepository;
use MailPoet\Subscribers\NewSubscriberNotificationMailer;
use MailPoet\Subscribers\Source;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class Populator {
  public $prefix;
  public $templates;
  /** @var SettingsController */
  private $settings;
  /** @var WPFunctions */
  private $wp;
  /** @var CaptchaRenderer */
  private $captchaRenderer;
  /** @var ReferralDetector */
  private $referralDetector;
  const TEMPLATES_NAMESPACE = '\MailPoet\Config\PopulatorData\Templates\\';
  /** @var WP */
  private $wpSegment;
  /** @var EntityManager */
  private $entityManager;
  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;
  /** @var SegmentsRepository */
  private $segmentsRepository;

  public function __construct(
    SettingsController $settings,
    WPFunctions $wp,
    CaptchaRenderer $captchaRenderer,
    ReferralDetector $referralDetector,
    EntityManager $entityManager,
    WP $wpSegment,
    ScheduledTasksRepository $scheduledTasksRepository,
    SegmentsRepository $segmentsRepository
  ) {
    $this->settings = $settings;
    $this->wp = $wp;
    $this->captchaRenderer = $captchaRenderer;
    $this->wpSegment = $wpSegment;
    $this->referralDetector = $referralDetector;
    $this->prefix = Env::$dbPrefix;
    $this->templates = [
      'WelcomeBlank1Column',
      'WelcomeBlank12Column',
      'GiftWelcome',
      'Minimal',
      'Phone',
      'Sunglasses',
      'RealEstate',
      'AppWelcome',
      'FoodBox',
      'Poet',
      'PostNotificationsBlank1Column',
      'ModularStyleStories',
      'RssSimpleNews',
      'NotSoMedium',
      'WideStoryLayout',
      'IndustryConference',
      'ScienceWeekly',
      'NewspaperTraditional',
      'ClearNews',
      'DogFood',
      'KidsClothing',
      'RockBand',
      'WineCity',
      'Fitness',
      'Motor',
      'Avocado',
      'BookStoreWithCoupon',
      'FlowersWithCoupon',
      'NewsletterBlank1Column',
      'NewsletterBlank12Column',
      'NewsletterBlank121Column',
      'NewsletterBlank13Column',
      'SimpleText',
      'TakeAHike',
      'NewsDay',
      'WorldCup',
      'FestivalEvent',
      'RetroComputingMagazine',
      'Shoes',
      'Music',
      'Hotels',
      'PieceOfCake',
      'BuddhistTemple',
      'Mosque',
      'Synagogue',
      'Faith',
      'College',
      'RenewableEnergy',
      'PrimarySchool',
      'ComputerRepair',
      'YogaStudio',
      'Retro',
      'Charity',
      'CityLocalNews',
      'Coffee',
      'Vlogger',
      'Birds',
      'Engineering',
      'BrandingAgencyNews',
      'WordPressTheme',
      'Drone',
      'FashionBlog',
      'FashionStore',
      'FashionBlogA',
      'Photography',
      'JazzClub',
      'Guitarist',
      'HealthyFoodBlog',
      'Software',
      'LifestyleBlogA',
      'FashionShop',
      'LifestyleBlogB',
      'Painter',
      'FarmersMarket',
      'ConfirmInterestBeforeDeactivation',
      'ConfirmInterestOrUnsubscribe',
    ];
    $this->entityManager = $entityManager;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->segmentsRepository = $segmentsRepository;
  }

  public function up() {
    $localizer = new Localizer();
    $localizer->forceLoadWebsiteLocaleText();

    $this->populateNewsletterOptionFields();
    $this->populateNewsletterTemplates();

    $this->createDefaultSegment();
    $this->createDefaultSettings();
    $this->createDefaultUsersFlags();
    $this->createMailPoetPage();
    $this->createSourceForSubscribers();
    $this->scheduleInitialInactiveSubscribersCheck();
    $this->scheduleAuthorizedSendingEmailsCheck();

    $this->scheduleUnsubscribeTokens();
    $this->scheduleSubscriberLinkTokens();
    $this->detectReferral();
    $this->scheduleSubscriberLastEngagementDetection();
    $this->scheduleNewsletterTemplateThumbnails();
    $this->scheduleBackfillEngagementData();
    $this->scheduleMixpanel();
  }

  private function createMailPoetPage() {
    $page = Pages::getMailPoetPage(Pages::PAGE_SUBSCRIPTIONS);
    if ($page === null) {
      $mailpoetPageId = Pages::createMailPoetPage(Pages::PAGE_SUBSCRIPTIONS);
    } else {
      $mailpoetPageId = (int)$page->ID;
    }

    $subscription = $this->settings->get('subscription.pages', []);
    if (empty($subscription)) {
      $this->settings->set('subscription.pages', [
        'unsubscribe' => $mailpoetPageId,
        'manage' => $mailpoetPageId,
        'confirmation' => $mailpoetPageId,
        'confirm_unsubscribe' => $mailpoetPageId,
      ]);
    } else {
      // For existing installations
      $confirmUnsubPageSetting = empty($subscription['confirm_unsubscribe'])
        ? $mailpoetPageId : $subscription['confirm_unsubscribe'];

      $this->settings->set('subscription.pages', array_merge($subscription, [
        'confirm_unsubscribe' => $confirmUnsubPageSetting,
      ]));
    }

    $captchaPage = Pages::getMailPoetPage(Pages::PAGE_CAPTCHA);
    if ($captchaPage === null) {
      $captchaPageId = Pages::createMailPoetPage(Pages::PAGE_CAPTCHA);
    } else {
      $captchaPageId = $captchaPage->ID;
    }

    $this->settings->set('subscription.pages.captcha', $captchaPageId);
  }

  private function createDefaultSettings() {
    $settingsDbVersion = $this->settings->get('db_version');
    $currentUser = $this->wp->wpGetCurrentUser();

    // set cron trigger option to default method
    if (!$this->settings->get(CronTrigger::SETTING_NAME)) {
      $this->settings->set(CronTrigger::SETTING_NAME, [
        'method' => CronTrigger::DEFAULT_METHOD,
      ]);
    }

    // set default sender info based on current user
    $currentUserName = $currentUser->display_name ?: ''; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    // parse current user name if an email is used
    $senderName = explode('@', $currentUserName);
    $senderName = reset($senderName);
    // If current user is not set, default to admin email
    $senderAddress = $currentUser->user_email ?: $this->wp->getOption('admin_email'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $defaultSender = [
      'name' => $senderName,
      'address' => $senderAddress ?: '',
    ];
    $savedSender = $this->settings->get('sender', []);

    /**
     * Set default from name & address
     * In some cases ( like when the plugin is getting activated other than from WP Admin ) user data may not
     * still be set at this stage, so setting the defaults for `sender` is postponed
     */
    if (empty($savedSender) || empty($savedSender['address'])) {
      $this->settings->set('sender', $defaultSender);
    }

    // enable signup confirmation by default
    if (!$this->settings->get('signup_confirmation')) {
      $this->settings->set('signup_confirmation', [
        'enabled' => true,
      ]);
    }

    // set installation date
    if (!$this->settings->get('installed_at')) {
      $this->settings->set('installed_at', date("Y-m-d H:i:s"));
    }

    // set captcha settings
    $captcha = $this->settings->get('captcha');
    $reCaptcha = $this->settings->get('re_captcha');
    if (empty($captcha)) {
      $captchaType = CaptchaConstants::TYPE_DISABLED;
      if (!empty($reCaptcha['enabled'])) {
        $captchaType = CaptchaConstants::TYPE_RECAPTCHA;
      } elseif ($this->captchaRenderer->isSupported()) {
        $captchaType = CaptchaConstants::TYPE_BUILTIN;
      }
      $this->settings->set('captcha', [
        'type' => $captchaType,
        'recaptcha_site_token' => !empty($reCaptcha['site_token']) ? $reCaptcha['site_token'] : '',
        'recaptcha_secret_token' => !empty($reCaptcha['secret_token']) ? $reCaptcha['secret_token'] : '',
      ]);
    }

    $subscriberEmailNotification = $this->settings->get(NewSubscriberNotificationMailer::SETTINGS_KEY);
    if (empty($subscriberEmailNotification)) {
      $sender = $this->settings->get('sender', []);
      $this->settings->set('subscriber_email_notification', [
        'enabled' => true,
        'automated' => true,
        'address' => isset($sender['address']) ? $sender['address'] : null,
      ]);
    }

    $statsNotifications = $this->settings->get(Worker::SETTINGS_KEY);
    if (empty($statsNotifications)) {
      $sender = $this->settings->get('sender', []);
      $this->settings->set(Worker::SETTINGS_KEY, [
        'enabled' => true,
        'address' => isset($sender['address']) ? $sender['address'] : null,
      ]);
    }

    $woocommerceOptinOnCheckout = $this->settings->get('woocommerce.optin_on_checkout');
    $legacyLabelText = _x('Yes, I would like to be added to your mailing list', "default email opt-in message displayed on checkout page for ecommerce websites", 'mailpoet');
    $currentLabelText = _x('I would like to receive exclusive emails with discounts and product information', "default email opt-in message displayed on checkout page for ecommerce websites", 'mailpoet');
    if (empty($woocommerceOptinOnCheckout)) {
      $this->settings->set('woocommerce.optin_on_checkout', [
        'enabled' => empty($settingsDbVersion), // enable on new installs only
        'message' => $currentLabelText,
        'position' => Hooks::DEFAULT_OPTIN_POSITION,
      ]);
    } elseif (isset($woocommerceOptinOnCheckout['message']) && $woocommerceOptinOnCheckout['message'] === $legacyLabelText) {
      $this->settings->set('woocommerce.optin_on_checkout.message', $currentLabelText);
    }
    // reset mailer log
    MailerLog::resetMailerLog();
  }

  private function createDefaultUsersFlags() {
    $prefix = 'user_seen_editor_tutorial';
    $prefixLength = strlen($prefix);
    foreach ($this->settings->getAll() as $name => $value) {
      if (substr($name, 0, $prefixLength) === $prefix) {
        $userId = substr($name, $prefixLength);
        $this->createOrUpdateUserFlag($userId, 'editor_tutorial_seen', $value);
        $this->settings->delete($name);
      }
    }
  }

  private function createOrUpdateUserFlag($userId, $name, $value) {
    $userFlagsRepository = \MailPoet\DI\ContainerWrapper::getInstance(WP_DEBUG)->get(UserFlagsRepository::class);
    $flag = $userFlagsRepository->findOneBy([
      'userId' => $userId,
      'name' => $name,
    ]);

    if (!$flag) {
      $flag = new UserFlagEntity();
      $flag->setUserId($userId);
      $flag->setName($name);
      $userFlagsRepository->persist($flag);
    }
    $flag->setValue($value);
    $userFlagsRepository->flush();
  }

  private function createDefaultSegment() {
    // WP Users segment
    $this->segmentsRepository->getWPUsersSegment();
    // WooCommerce customers segment
    $this->segmentsRepository->getWooCommerceSegment();

    // Synchronize WP Users
    $this->wpSegment->synchronizeUsers();

    // Default segment
    $defaultSegment = $this->segmentsRepository->findOneBy(
      ['type' => 'default'],
      ['id' => 'ASC']
    );

    if (!$defaultSegment instanceof SegmentEntity) {
      $defaultSegment = new SegmentEntity(
        __('Newsletter mailing list', 'mailpoet'),
        SegmentEntity::TYPE_DEFAULT,
        __('This list is automatically created when you install MailPoet.', 'mailpoet')
      );
      $this->segmentsRepository->persist($defaultSegment);
      $this->segmentsRepository->flush();
    }

    return $defaultSegment;
  }

  private function populateNewsletterOptionFields() {
    $optionFields = [
      [
        'name' => 'isScheduled',
        'newsletter_type' => 'standard',
      ],
      [
        'name' => 'scheduledAt',
        'newsletter_type' => 'standard',
      ],
      [
        'name' => 'event',
        'newsletter_type' => 'welcome',
      ],
      [
        'name' => 'segment',
        'newsletter_type' => 'welcome',
      ],
      [
        'name' => 'role',
        'newsletter_type' => 'welcome',
      ],
      [
        'name' => 'afterTimeNumber',
        'newsletter_type' => 'welcome',
      ],
      [
        'name' => 'afterTimeType',
        'newsletter_type' => 'welcome',
      ],
      [
        'name' => 'intervalType',
        'newsletter_type' => 'notification',
      ],
      [
        'name' => 'timeOfDay',
        'newsletter_type' => 'notification',
      ],
      [
        'name' => 'weekDay',
        'newsletter_type' => 'notification',
      ],
      [
        'name' => 'monthDay',
        'newsletter_type' => 'notification',
      ],
      [
        'name' => 'nthWeekDay',
        'newsletter_type' => 'notification',
      ],
      [
        'name' => 'schedule',
        'newsletter_type' => 'notification',
      ],
      [
        'name' => 'group',
        'newsletter_type' => NewsletterEntity::TYPE_AUTOMATIC,
      ],
      [
        'name' => 'group',
        'newsletter_type' => NewsletterEntity::TYPE_AUTOMATION,
      ],
      [
        'name' => 'group',
        'newsletter_type' => NewsletterEntity::TYPE_AUTOMATION_TRANSACTIONAL,
      ],
      [
        'name' => 'event',
        'newsletter_type' => NewsletterEntity::TYPE_AUTOMATIC,
      ],
      [
        'name' => 'event',
        'newsletter_type' => NewsletterEntity::TYPE_AUTOMATION,
      ],
      [
        'name' => 'event',
        'newsletter_type' => NewsletterEntity::TYPE_AUTOMATION_TRANSACTIONAL,
      ],
      [
        'name' => 'sendTo',
        'newsletter_type' => NewsletterEntity::TYPE_AUTOMATIC,
      ],
      [
        'name' => 'segment',
        'newsletter_type' => NewsletterEntity::TYPE_AUTOMATIC,
      ],
      [
        'name' => 'afterTimeNumber',
        'newsletter_type' => NewsletterEntity::TYPE_AUTOMATIC,
      ],
      [
        'name' => 'afterTimeType',
        'newsletter_type' => NewsletterEntity::TYPE_AUTOMATIC,
      ],
      [
        'name' => 'meta',
        'newsletter_type' => NewsletterEntity::TYPE_AUTOMATIC,
      ],
      [
        'name' => 'afterTimeNumber',
        'newsletter_type' => NewsletterEntity::TYPE_RE_ENGAGEMENT,
      ],
      [
        'name' => 'afterTimeType',
        'newsletter_type' => NewsletterEntity::TYPE_RE_ENGAGEMENT,
      ],
      [
        'name' => 'automationId',
        'newsletter_type' => NewsletterEntity::TYPE_AUTOMATION,
      ],
      [
        'name' => 'automationStepId',
        'newsletter_type' => NewsletterEntity::TYPE_AUTOMATION,
      ],
      [
        'name' => NewsletterOptionFieldEntity::NAME_FILTER_SEGMENT_ID,
        'newsletter_type' => NewsletterEntity::TYPE_STANDARD,
      ],
      [
        'name' => NewsletterOptionFieldEntity::NAME_FILTER_SEGMENT_ID,
        'newsletter_type' => NewsletterEntity::TYPE_RE_ENGAGEMENT,
      ],
      [
        'name' => NewsletterOptionFieldEntity::NAME_FILTER_SEGMENT_ID,
        'newsletter_type' => NewsletterEntity::TYPE_NOTIFICATION,
      ],
    ];

    // 1. Load all existing option fields from the database.
    $tableName = $this->entityManager->getClassMetadata(NewsletterOptionFieldEntity::class)->getTableName();
    $connection = $this->entityManager->getConnection();
    $existingOptionFields = $connection->createQueryBuilder()
      ->select('f.name, f.newsletter_type')
      ->from($tableName, 'f')
      ->executeQuery()
      ->fetchAllAssociative();

    // 2. Insert new option fields using a single query (good for first installs).
    $inserts = array_udiff(
      $optionFields,
      $existingOptionFields,
      fn($a, $b) => [$a['name'], $a['newsletter_type']] <=> [$b['name'], $b['newsletter_type']]
    );
    if ($inserts) {
      $placeholders = implode(',', array_fill(0, count($inserts), '(?, ?)'));
      $connection->executeStatement(
        "INSERT INTO $tableName (name, newsletter_type) VALUES $placeholders",
        array_merge(
          ...array_map(
            fn($of) => [$of['name'], $of['newsletter_type']],
            $inserts
          )
        )
      );
    }
  }

  private function populateNewsletterTemplates(): void {
    // 1. Load templates from the file system.
    $templates = [];
    foreach ($this->templates as $template) {
      $template = self::TEMPLATES_NAMESPACE . $template;
      $template = new $template(Env::$assetsUrl);
      $templates[] = $template->get();
    }

    // 2. Load existing corresponding (readonly) templates from the database.
    $tableName = $this->entityManager->getClassMetadata(NewsletterTemplateEntity::class)->getTableName();
    $connection = $this->entityManager->getConnection();
    $existingTemplates = $connection->createQueryBuilder()
      ->select('t.name, t.categories, t.readonly, t.thumbnail, t.body')
      ->from($tableName, 't')
      ->where('t.readonly = 1')
      ->executeQuery()
      ->fetchAllAssociativeIndexed();

    // 3. Compare the existing and file system templates.
    $inserts = [];
    $updates = [];
    foreach ($templates as $template) {
      $existing = $existingTemplates[$template['name']] ?? null;
      if (
        $existing
        && $existing['categories'] === $template['categories']
        && $existing['body'] === $template['body']
        && $existing['thumbnail'] === $template['thumbnail']
      ) {
        continue;
      }

      if ($existing) {
        $updates[] = $template;
      } else {
        $inserts[] = $template;
      }
    }

    // 4. Update existing templates.
    foreach ($updates as $template) {
      $connection->update($tableName, $template, ['name' => $template['name']]);
    }

    // 5. Insert new templates using a single query (good for first installs).
    if ($inserts) {
      $placeholders = implode(',', array_fill(0, count($inserts), '(?, ?, ?, ?, ?)'));
      $connection->executeStatement(
        "INSERT INTO $tableName (name, categories, readonly, thumbnail, body) VALUES $placeholders",
        array_merge(
          ...array_map(
            fn($t) => [$t['name'], $t['categories'], $t['readonly'], $t['thumbnail'], $t['body']],
            $inserts
          )
        )
      );
    }

    // 6. Remove duplicates.
    // SQLite doesn't support JOIN in DELETE queries, we need to use a subquery.
    // MySQL doesn't support DELETE with subqueries reading from the same table.
    if (Connection::isSQLite()) {
      $connection->executeStatement("
        DELETE FROM $tableName WHERE id IN (
          SELECT t1.id
          FROM $tableName t1
          JOIN $tableName t2 ON t1.id < t2.id AND t1.name = t2.name
          WHERE t1.readonly = 1
          AND t2.readonly = 1
       )
      ");
    } else {
      $connection->executeStatement("
        DELETE t1
        FROM $tableName t1, $tableName t2
        WHERE t1.id < t2.id AND t1.name = t2.name
        AND t1.readonly = 1
        AND t2.readonly = 1
      ");
    }
  }

  private function createSourceForSubscribers() {
    $statisticsFormTable = $this->entityManager->getClassMetadata(StatisticsFormEntity::class)->getTableName();
    $subscriberTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();

    // Temporarily skip the queries in WP Playground.
    // UPDATE with JOIN is not yet supported by the SQLite integration.
    if (Connection::isSQLite()) {
      return;
    }

    $this->entityManager->getConnection()->executeStatement(
      ' UPDATE LOW_PRIORITY `' . $subscriberTable . '` subscriber ' .
      ' JOIN `' . $statisticsFormTable . '` stats ON stats.subscriber_id=subscriber.id ' .
      " SET `source` = '" . Source::FORM . "'" .
      " WHERE `source` = '" . Source::UNKNOWN . "'"
    );

    $this->entityManager->getConnection()->executeStatement(
      'UPDATE LOW_PRIORITY `' . $subscriberTable . '`' .
      " SET `source` = '" . Source::WORDPRESS_USER . "'" .
      " WHERE `source` = '" . Source::UNKNOWN . "'" .
      ' AND `wp_user_id` IS NOT NULL'
    );

    $this->entityManager->getConnection()->executeStatement(
      'UPDATE LOW_PRIORITY `' . $subscriberTable . '`' .
      " SET `source` = '" . Source::WOOCOMMERCE_USER . "'" .
      " WHERE `source` = '" . Source::UNKNOWN . "'" .
      ' AND `is_woocommerce_user` = 1'
    );
  }

  private function scheduleInitialInactiveSubscribersCheck() {
    $this->scheduleTask(
      InactiveSubscribers::TASK_TYPE,
      Carbon::now()->millisecond(0)->addHour()
    );
  }

  private function scheduleAuthorizedSendingEmailsCheck() {
    if (!Bridge::isMPSendingServiceEnabled()) {
      return;
    }
    $this->scheduleTask(
      AuthorizedSendingEmailsCheck::TASK_TYPE,
      Carbon::now()->millisecond(0)
    );
  }

  private function scheduleUnsubscribeTokens() {
    $this->scheduleTask(
      UnsubscribeTokens::TASK_TYPE,
      Carbon::now()->millisecond(0)
    );
  }

  private function scheduleSubscriberLinkTokens() {
    $this->scheduleTask(
      SubscriberLinkTokens::TASK_TYPE,
      Carbon::now()->millisecond(0)
    );
  }

  private function scheduleMixpanel() {
    $this->scheduleTask(Mixpanel::TASK_TYPE, Carbon::now()->millisecond(0));
  }

  private function scheduleTask($type, $datetime, $priority = null) {
    $task = $this->scheduledTasksRepository->findOneBy(
      [
        'type' => $type,
        'status' => [ScheduledTaskEntity::STATUS_SCHEDULED, null],
      ]
    );

    if ($task) {
      return true;
    }

    $task = new ScheduledTaskEntity();
    $task->setType($type);
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $task->setScheduledAt($datetime);

    if ($priority !== null) {
      $task->setPriority($priority);
    }

    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();
  }

  private function detectReferral() {
    $this->referralDetector->detect();
  }

  private function scheduleSubscriberLastEngagementDetection() {
    if (version_compare((string)$this->settings->get('db_version', '3.72.1'), '3.72.0', '>')) {
      return;
    }
    $this->scheduleTask(
      SubscribersLastEngagement::TASK_TYPE,
      Carbon::now()->millisecond(0)
    );
  }

  private function scheduleNewsletterTemplateThumbnails() {
    $this->scheduleTask(
      NewsletterTemplateThumbnails::TASK_TYPE,
      Carbon::now()->millisecond(0),
      ScheduledTaskEntity::PRIORITY_LOW
    );
  }

  private function scheduleBackfillEngagementData(): void {
    $existingTask = $this->scheduledTasksRepository->findOneBy(
      [
        'type' => BackfillEngagementData::TASK_TYPE,
      ]
    );
    if ($existingTask) {
      return;
    }
    $this->scheduleTask(
      BackfillEngagementData::TASK_TYPE,
      Carbon::now()->millisecond(0)
    );
  }
}
