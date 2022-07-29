<?php

namespace MailPoet\Config;

use MailPoet\Cron\CronTrigger;
use MailPoet\Cron\Workers\AuthorizedSendingEmailsCheck;
use MailPoet\Cron\Workers\Beamer;
use MailPoet\Cron\Workers\InactiveSubscribers;
use MailPoet\Cron\Workers\NewsletterTemplateThumbnails;
use MailPoet\Cron\Workers\StatsNotifications\Worker;
use MailPoet\Cron\Workers\SubscriberLinkTokens;
use MailPoet\Cron\Workers\SubscribersLastEngagement;
use MailPoet\Cron\Workers\UnsubscribeTokens;
use MailPoet\Entities\FormEntity;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\NewsletterTemplateEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsFormEntity;
use MailPoet\Entities\UserFlagEntity;
use MailPoet\Form\FormsRepository;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Referrals\ReferralDetector;
use MailPoet\Segments\WP;
use MailPoet\Services\Bridge;
use MailPoet\Settings\Pages;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Settings\UserFlagsRepository;
use MailPoet\Subscribers\NewSubscriberNotificationMailer;
use MailPoet\Subscribers\Source;
use MailPoet\Subscription\Captcha;
use MailPoet\Util\Helpers;
use MailPoet\Util\Notices\ChangedTrackingNotice;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class Populator {
  public $prefix;
  public $models;
  public $templates;
  /** @var SettingsController */
  private $settings;
  /** @var WPFunctions */
  private $wp;
  /** @var Captcha */
  private $captcha;
  /** @var ReferralDetector  */
  private $referralDetector;
  const TEMPLATES_NAMESPACE = '\MailPoet\Config\PopulatorData\Templates\\';
  /** @var FormsRepository */
  private $formsRepository;
  /** @var WP */
  private $wpSegment;
  /** @var EntityManager */
  private $entityManager;

  public function __construct(
    SettingsController $settings,
    WPFunctions $wp,
    Captcha $captcha,
    ReferralDetector $referralDetector,
    FormsRepository $formsRepository,
    EntityManager $entityManager,
    WP $wpSegment
  ) {
    $this->settings = $settings;
    $this->wp = $wp;
    $this->captcha = $captcha;
    $this->wpSegment = $wpSegment;
    $this->referralDetector = $referralDetector;
    $this->prefix = Env::$dbPrefix;
    $this->models = [
      'newsletter_option_fields',
      'newsletter_templates',
    ];
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
    $this->formsRepository = $formsRepository;
    $this->entityManager = $entityManager;
  }

  public function up() {
    $localizer = new Localizer();
    $localizer->forceLoadWebsiteLocaleText();

    array_map([$this, 'populate'], $this->models);

    $this->createDefaultSegment();
    $this->createDefaultSettings();
    $this->createDefaultUsersFlags();
    $this->createMailPoetPage();
    $this->createSourceForSubscribers();
    $this->updateMetaFields();
    $this->scheduleInitialInactiveSubscribersCheck();
    $this->scheduleAuthorizedSendingEmailsCheck();
    $this->scheduleBeamer();
    $this->updateLastSubscribedAt();
    $this->enableStatsNotificationsForAutomatedEmails();
    $this->updateSentUnsubscribeLinksToInstantUnsubscribeLinks();
    $this->pauseTasksForPausedNewsletters();

    $this->scheduleUnsubscribeTokens();
    $this->scheduleSubscriberLinkTokens();
    $this->detectReferral();
    $this->moveGoogleAnalyticsFromPremium();
    $this->addPlacementStatusToForms();
    $this->migrateFormPlacement();
    $this->scheduleSubscriberLastEngagementDetection();
    $this->moveNewsletterTemplatesThumbnailData();
    $this->scheduleNewsletterTemplateThumbnails();
    $this->updateToUnifiedTrackingSettings();
  }

  private function createMailPoetPage() {
    $page = Pages::getDefaultMailPoetPage();
    if ($page === null) {
      $mailpoetPageId = Pages::createMailPoetPage();
    } else {
      $mailpoetPageId = (int)$page->ID;
    }

    $subscription = $this->settings->get('subscription.pages', []);
    if (empty($subscription)) {
      $this->settings->set('subscription.pages', [
        'unsubscribe' => $mailpoetPageId,
        'manage' => $mailpoetPageId,
        'confirmation' => $mailpoetPageId,
        'captcha' => $mailpoetPageId,
        'confirm_unsubscribe' => $mailpoetPageId,
      ]);
    } else {
      // For existing installations
      $captchaPageSetting = (empty($subscription['captcha']) || $subscription['captcha'] !== $mailpoetPageId)
        ? $mailpoetPageId : $subscription['captcha'];
      $confirmUnsubPageSetting = empty($subscription['confirm_unsubscribe'])
        ? $mailpoetPageId : $subscription['confirm_unsubscribe'];

      $this->settings->set('subscription.pages', array_merge($subscription, [
        'captcha' => $captchaPageSetting,
        'confirm_unsubscribe' => $confirmUnsubPageSetting,
      ]));
    }
  }

  private function createDefaultSettings() {
    $settingsDbVersion = $this->settings->fetch('db_version');
    $currentUser = $this->wp->wpGetCurrentUser();

    // set cron trigger option to default method
    if (!$this->settings->fetch(CronTrigger::SETTING_NAME)) {
      $this->settings->set(CronTrigger::SETTING_NAME, [
        'method' => CronTrigger::DEFAULT_METHOD,
      ]);
    }

    // set default sender info based on current user
    $defaultSender = [
      'name' => $currentUser->display_name ?: '', // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      'address' => $currentUser->user_email ?: '', // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    ];
    $savedSender = $this->settings->fetch('sender', []);

    /**
     * Set default from name & address
     * In some cases ( like when the plugin is getting activated other than from WP Admin ) user data may not
     * still be set at this stage, so setting the defaults for `sender` is postponed
     */
    if (empty($savedSender) || empty($savedSender['address'])) {
      $this->settings->set('sender', $defaultSender);
    }

    // enable signup confirmation by default
    if (!$this->settings->fetch('signup_confirmation')) {
      $this->settings->set('signup_confirmation', [
        'enabled' => true,
      ]);
    }

    // set installation date
    if (!$this->settings->fetch('installed_at')) {
      $this->settings->set('installed_at', date("Y-m-d H:i:s"));
    }

    // set captcha settings
    $captcha = $this->settings->fetch('captcha');
    $reCaptcha = $this->settings->fetch('re_captcha');
    if (empty($captcha)) {
      $captchaType = Captcha::TYPE_DISABLED;
      if (!empty($reCaptcha['enabled'])) {
        $captchaType = Captcha::TYPE_RECAPTCHA;
      } elseif ($this->captcha->isSupported()) {
        $captchaType = Captcha::TYPE_BUILTIN;
      }
      $this->settings->set('captcha', [
        'type' => $captchaType,
        'recaptcha_site_token' => !empty($reCaptcha['site_token']) ? $reCaptcha['site_token'] : '',
        'recaptcha_secret_token' => !empty($reCaptcha['secret_token']) ? $reCaptcha['secret_token'] : '',
      ]);
    }

    $subscriberEmailNotification = $this->settings->fetch(NewSubscriberNotificationMailer::SETTINGS_KEY);
    if (empty($subscriberEmailNotification)) {
      $sender = $this->settings->fetch('sender', []);
      $this->settings->set('subscriber_email_notification', [
        'enabled' => true,
        'automated' => true,
        'address' => isset($sender['address']) ? $sender['address'] : null,
      ]);
    }

    $statsNotifications = $this->settings->fetch(Worker::SETTINGS_KEY);
    if (empty($statsNotifications)) {
      $sender = $this->settings->fetch('sender', []);
      $this->settings->set(Worker::SETTINGS_KEY, [
        'enabled' => true,
        'address' => isset($sender['address']) ? $sender['address'] : null,
      ]);
    }

    $woocommerceOptinOnCheckout = $this->settings->fetch('woocommerce.optin_on_checkout');
    $legacyLabelText = $this->wp->_x('Yes, I would like to be added to your mailing list', "default email opt-in message displayed on checkout page for ecommerce websites", 'mailpoet');
    $currentLabelText = $this->wp->_x('I would like to receive exclusive emails with discounts and product information', "default email opt-in message displayed on checkout page for ecommerce websites", 'mailpoet');
    if (empty($woocommerceOptinOnCheckout)) {
      $this->settings->set('woocommerce.optin_on_checkout', [
        'enabled' => empty($settingsDbVersion), // enable on new installs only
        'message' => $currentLabelText,
      ]);
    } elseif (isset($woocommerceOptinOnCheckout['message']) && $woocommerceOptinOnCheckout['message'] === $legacyLabelText) {
      $this->settings->set('woocommerce.optin_on_checkout.message', $currentLabelText);
    }
    // reset mailer log
    MailerLog::resetMailerLog();
  }

  private function createDefaultUsersFlags() {
    $lastAnnouncementSeen = $this->settings->fetch('last_announcement_seen');
    if (!empty($lastAnnouncementSeen)) {
      foreach ($lastAnnouncementSeen as $userId => $value) {
        $this->createOrUpdateUserFlag($userId, 'last_announcement_seen', $value);
      }
      $this->settings->delete('last_announcement_seen');
    }

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
    Segment::getWPSegment();
    // WooCommerce customers segment
    Segment::getWooCommerceSegment();

    // Synchronize WP Users
    $this->wpSegment->synchronizeUsers();

    // Default segment
    $defaultSegment = Segment::where('type', 'default')->orderByAsc('id')->limit(1)->findOne();
    if (!$defaultSegment instanceof Segment) {
      $defaultSegment = Segment::create();
      $newList = [
        'name' => $this->wp->__('Newsletter mailing list', 'mailpoet'),
        'description' =>
          $this->wp->__('This list is automatically created when you install MailPoet.', 'mailpoet'),
      ];
      $defaultSegment->hydrate($newList);
      $defaultSegment->save();
    }
    return $defaultSegment;
  }

  protected function newsletterOptionFields() {
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
        'name' => 'event',
        'newsletter_type' => NewsletterEntity::TYPE_AUTOMATIC,
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
        'name' => 'workflowId',
        'newsletter_type' => NewsletterEntity::TYPE_AUTOMATION,
      ],
    ];

    return [
      'rows' => $optionFields,
      'identification_columns' => [
        'name',
        'newsletter_type',
      ],
    ];
  }

  protected function newsletterTemplates() {
    $templates = [];
    foreach ($this->templates as $template) {
      $template = self::TEMPLATES_NAMESPACE . $template;
      $template = new $template(Env::$assetsUrl);
      $templates[] = $template->get();
    }
    return [
      'rows' => $templates,
      'identification_columns' => [
        'name',
      ],
      'remove_duplicates' => true,
    ];
  }

  protected function populate($model) {
    $modelMethod = Helpers::underscoreToCamelCase($model);
    $table = $this->prefix . $model;
    $dataDescriptor = $this->$modelMethod();
    $rows = $dataDescriptor['rows'];
    $identificationColumns = array_fill_keys(
      $dataDescriptor['identification_columns'],
      ''
    );
    $removeDuplicates =
      isset($dataDescriptor['remove_duplicates']) && $dataDescriptor['remove_duplicates'];

    foreach ($rows as $row) {
      $existenceComparisonFields = array_intersect_key(
        $row,
        $identificationColumns
      );

      if (!$this->rowExists($table, $existenceComparisonFields)) {
        $this->insertRow($table, $row);
      } else {
        if ($removeDuplicates) {
          $this->removeDuplicates($table, $row, $existenceComparisonFields);
        }
        $this->updateRow($table, $row, $existenceComparisonFields);
      }
    }
  }

  private function rowExists(string $tableName, array $columns): bool {
    global $wpdb;

    $conditions = array_map(function($key, $value) {
      return esc_sql($key) . "='" . esc_sql($value) . "'";
    }, array_keys($columns), $columns);

    $table = esc_sql($tableName);
    // $conditions is escaped
    // phpcs:ignore WordPressDotOrg.sniffs.DirectDB.UnescapedDBParameter
    return $wpdb->get_var(
      "SELECT COUNT(*) FROM $table WHERE " . implode(' AND ', $conditions)
    ) > 0;
  }

  private function insertRow($table, $row) {
    global $wpdb;

    return $wpdb->insert(
      $table,
      $row
    );
  }

  private function updateRow($table, $row, $where) {
    global $wpdb;

    return $wpdb->update(
      $table,
      $row,
      $where
    );
  }

  private function removeDuplicates($table, $row, $where) {
    global $wpdb;

    $conditions = ['1=1'];
    $values = [];
    foreach ($where as $field => $value) {
      $conditions[] = "`t1`.`" . esc_sql($field) . "` = `t2`.`" . esc_sql($field) . "`";
      $conditions[] = "`t1`.`" . esc_sql($field) . "` = %s";
      $values[] = $value;
    }

    $conditions = implode(' AND ', $conditions);

    $table = esc_sql($table);
    return $wpdb->query(
      $wpdb->prepare(
        "DELETE t1 FROM $table t1, $table t2 WHERE t1.id < t2.id AND $conditions",
        $values
      )
    );
  }

  private function createSourceForSubscribers() {
    $statisticsFormTable = $this->entityManager->getClassMetadata(StatisticsFormEntity::class)->getTableName();
    Subscriber::rawExecute(
      ' UPDATE LOW_PRIORITY `' . Subscriber::$_table . '` subscriber ' .
      ' JOIN `' . $statisticsFormTable . '` stats ON stats.subscriber_id=subscriber.id ' .
      ' SET `source` = "' . Source::FORM . '"' .
      ' WHERE `source` = "' . Source::UNKNOWN . '"'
    );
    Subscriber::rawExecute(
      'UPDATE LOW_PRIORITY `' . Subscriber::$_table . '`' .
      ' SET `source` = "' . Source::WORDPRESS_USER . '"' .
      ' WHERE `source` = "' . Source::UNKNOWN . '"' .
      ' AND `wp_user_id` IS NOT NULL'
    );
    Subscriber::rawExecute(
      'UPDATE LOW_PRIORITY `' . Subscriber::$_table . '`' .
      ' SET `source` = "' . Source::WOOCOMMERCE_USER . '"' .
      ' WHERE `source` = "' . Source::UNKNOWN . '"' .
      ' AND `is_woocommerce_user` = 1'
    );
  }

  private function updateMetaFields() {
    global $wpdb;
    // perform once for versions below or equal to 3.26.0
    if (version_compare((string)$this->settings->get('db_version', '3.26.1'), '3.26.0', '>')) {
      return false;
    }
    $tables = [ScheduledTask::$_table, SendingQueue::$_table];
    foreach ($tables as $table) {
      $wpdb->query("UPDATE `" . esc_sql($table) . "` SET meta = NULL WHERE meta = 'null'");
    }
    return true;
  }

  private function scheduleInitialInactiveSubscribersCheck() {
    $this->scheduleTask(
      InactiveSubscribers::TASK_TYPE,
      Carbon::createFromTimestamp($this->wp->currentTime('timestamp'))->addHour()
    );
  }

  private function scheduleAuthorizedSendingEmailsCheck() {
    if (!Bridge::isMPSendingServiceEnabled()) {
      return;
    }
    $this->scheduleTask(
      AuthorizedSendingEmailsCheck::TASK_TYPE,
      Carbon::createFromTimestamp($this->wp->currentTime('timestamp'))
    );
  }

  private function scheduleBeamer() {
    if (!$this->settings->get('last_announcement_date')) {
      $this->scheduleTask(
        Beamer::TASK_TYPE,
        Carbon::createFromTimestamp($this->wp->currentTime('timestamp'))
      );
    }
  }

  private function updateLastSubscribedAt() {
    global $wpdb;
    // perform once for versions below or equal to 3.42.0
    if (version_compare((string)$this->settings->get('db_version', '3.42.1'), '3.42.0', '>')) {
      return false;
    }
    $table = esc_sql(Subscriber::$_table);
    $query = $wpdb->prepare(
      "UPDATE `{$table}` SET last_subscribed_at = GREATEST(COALESCE(confirmed_at, 0), COALESCE(created_at, 0)) WHERE status != %s AND last_subscribed_at IS NULL;",
      Subscriber::STATUS_UNCONFIRMED
    );
    $wpdb->query($query);
    return true;
  }

  private function scheduleUnsubscribeTokens() {
    $this->scheduleTask(
      UnsubscribeTokens::TASK_TYPE,
      Carbon::createFromTimestamp($this->wp->currentTime('timestamp'))
    );
  }

  private function scheduleSubscriberLinkTokens() {
    $this->scheduleTask(
      SubscriberLinkTokens::TASK_TYPE,
      Carbon::createFromTimestamp($this->wp->currentTime('timestamp'))
    );
  }

  private function scheduleTask($type, $datetime, $priority = null) {
    $task = ScheduledTask::where('type', $type)
      ->whereRaw('(status = ? OR status IS NULL)', [ScheduledTask::STATUS_SCHEDULED])
      ->findOne();
    if ($task) {
      return true;
    }
    $task = ScheduledTask::create();
    $task->type = $type;
    if ($priority !== null) {
      $task->priority = $priority;
    }
    $task->status = ScheduledTask::STATUS_SCHEDULED;
    $task->scheduledAt = $datetime;
    $task->save();
  }

  private function enableStatsNotificationsForAutomatedEmails() {
    if (version_compare((string)$this->settings->get('db_version', '3.31.2'), '3.31.1', '>')) {
      return;
    }
    $settings = $this->settings->get(Worker::SETTINGS_KEY);
    $settings['automated'] = true;
    $this->settings->set(Worker::SETTINGS_KEY, $settings);
  }

  private function updateSentUnsubscribeLinksToInstantUnsubscribeLinks() {
    if (version_compare((string)$this->settings->get('db_version', '3.46.14'), '3.46.13', '>')) {
      return;
    }
    global $wpdb;
    $table = esc_sql($this->entityManager->getClassMetadata(NewsletterLinkEntity::class)->getTableName());
    $wpdb->query($wpdb->prepare(
      "UPDATE `$table` SET `url` = %s WHERE `url` = %s;",
      NewsletterLinkEntity::INSTANT_UNSUBSCRIBE_LINK_SHORT_CODE,
      NewsletterLinkEntity::UNSUBSCRIBE_LINK_SHORT_CODE
    ));
  }

  private function pauseTasksForPausedNewsletters() {
    if (version_compare((string)$this->settings->get('db_version', '3.60.5'), '3.60.4', '>')) {
      return;
    }

    $scheduledTaskTable = $this->entityManager->getClassMetadata(ScheduledTaskEntity::class)->getTableName();
    $sendingQueueTable = $this->entityManager->getClassMetadata(SendingQueueEntity::class)->getTableName();
    $newsletterTable = $this->entityManager->getClassMetadata(NewsletterEntity::class)->getTableName();

    $query = "
      UPDATE $scheduledTaskTable as t
        JOIN $sendingQueueTable as q ON t.id = q.task_id
        JOIN $newsletterTable as n ON n.id = q.newsletter_id
        SET t.status = :tStatusPaused
        WHERE
          t.status = :tStatusScheduled
          AND n.status = :nStatusDraft
    ";
    $this->entityManager->getConnection()->executeStatement(
      $query,
      [
        'tStatusPaused' => ScheduledTaskEntity::STATUS_PAUSED,
        'tStatusScheduled' => ScheduledTaskEntity::STATUS_SCHEDULED,
        'nStatusDraft' => NewsletterEntity::STATUS_DRAFT,
      ]
    );
  }

  private function addPlacementStatusToForms() {
    if (version_compare((string)$this->settings->get('db_version', '3.49.0'), '3.48.1', '>')) {
      return;
    }
    $forms = $this->formsRepository->findAll();
    foreach ($forms as $form) {
      $settings = $form->getSettings();
      if (
        (isset($settings['place_form_bellow_all_posts']) && $settings['place_form_bellow_all_posts'] === '1')
        || (isset($settings['place_form_bellow_all_pages']) && $settings['place_form_bellow_all_pages'] === '1')
      ) {
        $settings['form_placement_bellow_posts_enabled'] = '1';
      } else {
        $settings['form_placement_bellow_posts_enabled'] = '';
      }
      if (
        (isset($settings['place_popup_form_on_all_posts']) && $settings['place_popup_form_on_all_posts'] === '1')
        || (isset($settings['place_popup_form_on_all_pages']) && $settings['place_popup_form_on_all_pages'] === '1')
      ) {
        $settings['form_placement_popup_enabled'] = '1';
      } else {
        $settings['form_placement_popup_enabled'] = '';
      }
      if (
        (isset($settings['place_fixed_bar_form_on_all_posts']) && $settings['place_fixed_bar_form_on_all_posts'] === '1')
        || (isset($settings['place_fixed_bar_form_on_all_pages']) && $settings['place_fixed_bar_form_on_all_pages'] === '1')
      ) {
        $settings['form_placement_fixed_bar_enabled'] = '1';
      } else {
        $settings['form_placement_fixed_bar_enabled'] = '';
      }
      if (
        (isset($settings['place_slide_in_form_on_all_posts']) && $settings['place_slide_in_form_on_all_posts'] === '1')
        || (isset($settings['place_slide_in_form_on_all_pages']) && $settings['place_slide_in_form_on_all_pages'] === '1')
      ) {
        $settings['form_placement_slide_in_enabled'] = '1';
      } else {
        $settings['form_placement_slide_in_enabled'] = '';
      }
      $form->setSettings($settings);
    }
    $this->formsRepository->flush();
  }

  private function migrateFormPlacement() {
    if (version_compare((string)$this->settings->get('db_version', '3.50.0'), '3.49.1', '>')) {
      return;
    }
    $forms = $this->formsRepository->findAll();
    foreach ($forms as $form) {
      $settings = $form->getSettings();
      if (!is_array($settings)) continue;
      $settings['form_placement'] = [
        FormEntity::DISPLAY_TYPE_POPUP => [
          'enabled' => $settings['form_placement_popup_enabled'],
          'delay' => $settings['popup_form_delay'] ?? 0,
          'styles' => $settings['popup_styles'] ?? [],
          'posts' => [
            'all' => $settings['place_popup_form_on_all_posts'] ?? '',
          ],
          'pages' => [
            'all' => $settings['place_popup_form_on_all_pages'] ?? '',
          ],
        ],
        FormEntity::DISPLAY_TYPE_FIXED_BAR => [
          'enabled' => $settings['form_placement_fixed_bar_enabled'],
          'delay' => $settings['fixed_bar_form_delay'] ?? 0,
          'styles' => $settings['fixed_bar_styles'] ?? [],
          'position' => $settings['fixed_bar_form_position'] ?? 'top',
          'posts' => [
            'all' => $settings['place_fixed_bar_form_on_all_posts'] ?? '',
          ],
          'pages' => [
            'all' => $settings['place_fixed_bar_form_on_all_pages'] ?? '',
          ],
        ],
        FormEntity::DISPLAY_TYPE_BELOW_POST => [
          'enabled' => $settings['form_placement_bellow_posts_enabled'],
          'styles' => $settings['below_post_styles'] ?? [],
          'posts' => [
            'all' => $settings['place_form_bellow_all_posts'] ?? '',
          ],
          'pages' => [
            'all' => $settings['place_form_bellow_all_pages'] ?? '',
          ],
        ],
        FormEntity::DISPLAY_TYPE_SLIDE_IN => [
          'enabled' => $settings['form_placement_slide_in_enabled'],
          'delay' => $settings['slide_in_form_delay'] ?? 0,
          'position' => $settings['slide_in_form_position'] ?? 'right',
          'styles' => $settings['slide_in_styles'] ?? [],
          'posts' => [
            'all' => $settings['place_slide_in_form_on_all_posts'] ?? '',
          ],
          'pages' => [
            'all' => $settings['place_slide_in_form_on_all_pages'] ?? '',
          ],
        ],
        FormEntity::DISPLAY_TYPE_OTHERS => [
          'styles' => $settings['other_styles'] ?? [],
        ],
      ];
      if (isset($settings['form_placement_slide_in_enabled'])) unset($settings['form_placement_slide_in_enabled']);
      if (isset($settings['form_placement_fixed_bar_enabled'])) unset($settings['form_placement_fixed_bar_enabled']);
      if (isset($settings['form_placement_popup_enabled'])) unset($settings['form_placement_popup_enabled']);
      if (isset($settings['form_placement_bellow_posts_enabled'])) unset($settings['form_placement_bellow_posts_enabled']);
      if (isset($settings['place_form_bellow_all_pages'])) unset($settings['place_form_bellow_all_pages']);
      if (isset($settings['place_form_bellow_all_posts'])) unset($settings['place_form_bellow_all_posts']);
      if (isset($settings['place_popup_form_on_all_pages'])) unset($settings['place_popup_form_on_all_pages']);
      if (isset($settings['place_popup_form_on_all_posts'])) unset($settings['place_popup_form_on_all_posts']);
      if (isset($settings['popup_form_delay'])) unset($settings['popup_form_delay']);
      if (isset($settings['place_fixed_bar_form_on_all_pages'])) unset($settings['place_fixed_bar_form_on_all_pages']);
      if (isset($settings['place_fixed_bar_form_on_all_posts'])) unset($settings['place_fixed_bar_form_on_all_posts']);
      if (isset($settings['fixed_bar_form_delay'])) unset($settings['fixed_bar_form_delay']);
      if (isset($settings['fixed_bar_form_position'])) unset($settings['fixed_bar_form_position']);
      if (isset($settings['place_slide_in_form_on_all_pages'])) unset($settings['place_slide_in_form_on_all_pages']);
      if (isset($settings['place_slide_in_form_on_all_posts'])) unset($settings['place_slide_in_form_on_all_posts']);
      if (isset($settings['slide_in_form_delay'])) unset($settings['slide_in_form_delay']);
      if (isset($settings['slide_in_form_position'])) unset($settings['slide_in_form_position']);
      if (isset($settings['other_styles'])) unset($settings['other_styles']);
      if (isset($settings['slide_in_styles'])) unset($settings['slide_in_styles']);
      if (isset($settings['below_post_styles'])) unset($settings['below_post_styles']);
      if (isset($settings['fixed_bar_styles'])) unset($settings['fixed_bar_styles']);
      if (isset($settings['popup_styles'])) unset($settings['popup_styles']);
      $form->setSettings($settings);
    }
    $this->formsRepository->flush();
  }

  private function moveGoogleAnalyticsFromPremium() {
    global $wpdb;
    if (version_compare((string)$this->settings->get('db_version', '3.38.2'), '3.38.1', '>')) {
      return;
    }
    $premiumTableName = $wpdb->prefix . 'mailpoet_premium_newsletter_extra_data';
    $premiumTableExists = (int)$wpdb->get_var(
      $wpdb->prepare(
        "SELECT COUNT(1) FROM information_schema.tables WHERE table_schema=%s AND table_name=%s;",
        $wpdb->dbname,
        $premiumTableName
      )
    );
    if ($premiumTableExists) {
      $table = esc_sql(Newsletter::$_table);
      $query = "
        UPDATE
          `{$table}` as n
        JOIN `$premiumTableName` as ped ON n.id=ped.newsletter_id
          SET n.ga_campaign = ped.ga_campaign
      ";
      $wpdb->query($query);
    }
    return true;
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
      Carbon::createFromTimestamp($this->wp->currentTime('timestamp'))
    );
  }

  private function scheduleNewsletterTemplateThumbnails() {
    $this->scheduleTask(
      NewsletterTemplateThumbnails::TASK_TYPE,
      Carbon::createFromTimestamp($this->wp->currentTime('timestamp')),
      ScheduledTaskEntity::PRIORITY_LOW
    );
  }

  private function moveNewsletterTemplatesThumbnailData() {
    if (version_compare((string)$this->settings->get('db_version', '3.73.3'), '3.73.2', '>')) {
      return;
    }
    $newsletterTemplatesTable = $this->entityManager->getClassMetadata(NewsletterTemplateEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeQuery("
      UPDATE " . $newsletterTemplatesTable . "
      SET thumbnail_data = thumbnail, thumbnail = NULL
      WHERE thumbnail LIKE 'data:image%';"
    );
  }

  private function updateToUnifiedTrackingSettings() {
    if (version_compare((string)$this->settings->get('db_version', '3.74.3'), '3.74.2', '>')) {
      return;
    }
    $emailTracking = $this->settings->get('tracking.enabled', true);
    $wooTrackingCookie = $this->settings->get('woocommerce.accept_cookie_revenue_tracking.enabled');
    if ($wooTrackingCookie === null) { // No setting for WooCommerce Cookie Tracking - WooCommerce was not active
      $trackingLevel = $emailTracking ? TrackingConfig::LEVEL_FULL : TrackingConfig::LEVEL_BASIC;
    } elseif ($wooTrackingCookie) { // WooCommerce Cookie Tracking enabled
      $trackingLevel = TrackingConfig::LEVEL_FULL;
      // Cookie was enabled but tracking disabled and we are switching to full.
      // So we activate an admin notice to let the user know that we activated tracking
      if (!$emailTracking) {
        $this->wp->setTransient(ChangedTrackingNotice::OPTION_NAME, true);
      }
    } else { // WooCommerce Tracking Cookie Disabled
      $trackingLevel = $emailTracking ? TrackingConfig::LEVEL_PARTIAL : TrackingConfig::LEVEL_BASIC;
    }
    $this->settings->set('tracking.level', $trackingLevel);
  }
}
