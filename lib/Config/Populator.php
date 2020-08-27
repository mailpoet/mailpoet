<?php

namespace MailPoet\Config;

use MailPoet\Cron\CronTrigger;
use MailPoet\Cron\Workers\AuthorizedSendingEmailsCheck;
use MailPoet\Cron\Workers\Beamer;
use MailPoet\Cron\Workers\InactiveSubscribers;
use MailPoet\Cron\Workers\StatsNotifications\Worker;
use MailPoet\Cron\Workers\SubscriberLinkTokens;
use MailPoet\Cron\Workers\UnsubscribeTokens;
use MailPoet\Entities\FormEntity;
use MailPoet\Entities\UserFlagEntity;
use MailPoet\Features\FeaturesController;
use MailPoet\Form\FormFactory;
use MailPoet\Form\FormsRepository;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\Form;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsForms;
use MailPoet\Models\Subscriber;
use MailPoet\Referrals\ReferralDetector;
use MailPoet\Segments\WP;
use MailPoet\Services\Bridge;
use MailPoet\Settings\Pages;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\UserFlagsRepository;
use MailPoet\Subscribers\NewSubscriberNotificationMailer;
use MailPoet\Subscribers\Source;
use MailPoet\Subscription\Captcha;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

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
  /** @var FeaturesController */
  private $flagsController;
  /** @var FormFactory */
  private $formFactory;
  /** @var FormsRepository */
  private $formsRepository;

  public function __construct(
    SettingsController $settings,
    WPFunctions $wp,
    Captcha $captcha,
    ReferralDetector $referralDetector,
    FeaturesController $flagsController,
    FormsRepository $formsRepository,
    FormFactory $formFactory
  ) {
    $this->settings = $settings;
    $this->wp = $wp;
    $this->captcha = $captcha;
    $this->referralDetector = $referralDetector;
    $this->formFactory = $formFactory;
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
    ];
    $this->flagsController = $flagsController;
    $this->formsRepository = $formsRepository;
  }

  public function up() {
    $localizer = new Localizer();
    $localizer->forceLoadWebsiteLocaleText();

    array_map([$this, 'populate'], $this->models);

    $defaultSegment = $this->createDefaultSegment();
    $this->createDefaultForm($defaultSegment);
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

    $this->scheduleUnsubscribeTokens();
    $this->scheduleSubscriberLinkTokens();
    $this->detectReferral();
    $this->updateFormsSuccessMessages();
    $this->moveGoogleAnalyticsFromPremium();
    $this->addPlacementStatusToForms();
    $this->migrateFormPlacement();
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
    } elseif (
      (empty($subscription['captcha']) || $subscription['captcha'] !== $mailpoetPageId)
      || (empty($subscription['confirm_unsubscribe']) || $subscription['confirm_unsubscribe'] !== $mailpoetPageId)
    ) {
      // For existing installations
      $this->settings->set('subscription.pages', array_merge($subscription, [
        'captcha' => $mailpoetPageId,
        'confirm_unsubscribe' => $mailpoetPageId,
      ]));
    }
  }

  private function createDefaultSettings() {
    $currentUser = $this->wp->wpGetCurrentUser();
    $settingsDbVersion = $this->settings->fetch('db_version');

    // set cron trigger option to default method
    if (!$this->settings->fetch(CronTrigger::SETTING_NAME)) {
      $this->settings->set(CronTrigger::SETTING_NAME, [
        'method' => CronTrigger::DEFAULT_METHOD,
      ]);
    }

    // set default sender info based on current user
    $sender = [
      'name' => $currentUser->display_name, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      'address' => $currentUser->user_email, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    ];

    // set default from name & address
    if (!$this->settings->fetch('sender')) {
      $this->settings->set('sender', $sender);
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
    $legacyLabelText = $this->wp->_x('Yes, I would like to be added to your mailing list', "default email opt-in message displayed on checkout page for ecommerce websites");
    $currentLabelText = $this->wp->_x('I would like to receive exclusive emails with discounts and product information', "default email opt-in message displayed on checkout page for ecommerce websites");
    if (empty($woocommerceOptinOnCheckout)) {
      $this->settings->set('woocommerce.optin_on_checkout', [
        'enabled' => empty($settingsDbVersion), // enable on new installs only
        'message' => $currentLabelText,
      ]);
    } elseif (isset($woocommerceOptinOnCheckout['message']) && $woocommerceOptinOnCheckout['message'] === $legacyLabelText ) {
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
    WP::synchronizeUsers();

    // Default segment
    $defaultSegment = Segment::where('type', 'default')->orderByAsc('id')->limit(1)->findOne();
    if (!$defaultSegment instanceof Segment) {
      $defaultSegment = Segment::create();
      $newList = [
        'name' => $this->wp->__('My First List', 'mailpoet'),
        'description' =>
          $this->wp->__('This list is automatically created when you install MailPoet.', 'mailpoet'),
      ];
      if ($this->flagsController->isSupported(FeaturesController::NEW_DEFAULT_LIST_NAME)) {
        $newList['name'] = $this->wp->__('Newsletter mailing list', 'mailpoet');
      }
      $defaultSegment->hydrate($newList);
      $defaultSegment->save();
    }
    return $defaultSegment;
  }

  private function createDefaultForm(Segment $defaultSegment) {
    $this->formFactory->ensureDefaultFormExists((int)$defaultSegment->id());
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
        'newsletter_type' => Newsletter::TYPE_AUTOMATIC,
      ],
      [
        'name' => 'event',
        'newsletter_type' => Newsletter::TYPE_AUTOMATIC,
      ],
      [
        'name' => 'sendTo',
        'newsletter_type' => Newsletter::TYPE_AUTOMATIC,
      ],
      [
        'name' => 'segment',
        'newsletter_type' => Newsletter::TYPE_AUTOMATIC,
      ],
      [
        'name' => 'afterTimeNumber',
        'newsletter_type' => Newsletter::TYPE_AUTOMATIC,
      ],
      [
        'name' => 'afterTimeType',
        'newsletter_type' => Newsletter::TYPE_AUTOMATIC,
      ],
      [
        'name' => 'meta',
        'newsletter_type' => Newsletter::TYPE_AUTOMATIC,
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

  private function rowExists($table, $columns) {
    global $wpdb;

    $conditions = array_map(function($key) {
      return $key . '=%s';
    }, array_keys($columns));

    return $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $table WHERE " . implode(' AND ', $conditions),
      array_values($columns)
    )) > 0;
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
      $conditions[] = "`t1`.`$field` = `t2`.`$field`";
      $conditions[] = "`t1`.`$field` = %s";
      $values[] = $value;
    }

    $conditions = implode(' AND ', $conditions);

    $sql = "DELETE FROM `$table` WHERE $conditions";
    return $wpdb->query(
      $wpdb->prepare(
        "DELETE t1 FROM $table t1, $table t2 WHERE t1.id < t2.id AND $conditions",
        $values
      )
    );
  }

  private function createSourceForSubscribers() {
    Subscriber::rawExecute(
      ' UPDATE LOW_PRIORITY `' . Subscriber::$_table . '` subscriber ' .
      ' JOIN `' . StatisticsForms::$_table . '` stats ON stats.subscriber_id=subscriber.id ' .
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
    if (version_compare($this->settings->get('db_version', '3.26.1'), '3.26.0', '>')) {
      return false;
    }
    $tables = [ScheduledTask::$_table, SendingQueue::$_table];
    foreach ($tables as $table) {
      $query = "UPDATE `%s` SET meta = NULL WHERE meta = 'null'";
      $wpdb->query(sprintf($query, $table));
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
    if (version_compare($this->settings->get('db_version', '3.42.1'), '3.42.0', '>')) {
      return false;
    }
    $query = "UPDATE `%s` SET last_subscribed_at = GREATEST(COALESCE(confirmed_at, 0), COALESCE(created_at, 0)) WHERE status != '%s' AND last_subscribed_at IS NULL;";
    $wpdb->query(sprintf(
      $query,
      Subscriber::$_table,
      Subscriber::STATUS_UNCONFIRMED
    ));
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

  private function scheduleTask($type, $datetime) {
    $task = ScheduledTask::where('type', $type)
      ->whereRaw('status = ? OR status IS NULL', [ScheduledTask::STATUS_SCHEDULED])
      ->findOne();
    if ($task) {
      return true;
    }
    $task = ScheduledTask::create();
    $task->type = $type;
    $task->status = ScheduledTask::STATUS_SCHEDULED;
    $task->scheduledAt = $datetime;
    $task->save();
  }

  /**
   * Remove this comment when this private function is actually used
   * @phpcsSuppress SlevomatCodingStandard.Classes.UnusedPrivateElements
   */
  private function updateFormsSuccessMessages() {
    if (version_compare($this->settings->get('db_version', '3.23.2'), '3.23.1', '>')) {
      return;
    }
    Form::updateSuccessMessages();
  }

  private function enableStatsNotificationsForAutomatedEmails() {
    if (version_compare($this->settings->get('db_version', '3.31.2'), '3.31.1', '>')) {
      return;
    }
    $settings = $this->settings->get(Worker::SETTINGS_KEY);
    $settings['automated'] = true;
    $this->settings->set(Worker::SETTINGS_KEY, $settings);
  }

  private function updateSentUnsubscribeLinksToInstantUnsubscribeLinks() {
    if (version_compare($this->settings->get('db_version', '3.46.14'), '3.46.13', '>')) {
      return;
    }
    $query = "UPDATE `%s` SET `url` = '%s' WHERE `url` = '%s';";
    global $wpdb;
    $wpdb->query(sprintf(
      $query,
      NewsletterLink::$_table,
      NewsletterLink::INSTANT_UNSUBSCRIBE_LINK_SHORT_CODE,
      NewsletterLink::UNSUBSCRIBE_LINK_SHORT_CODE
    ));
  }

  private function addPlacementStatusToForms() {
    if (version_compare($this->settings->get('db_version', '3.49.0'), '3.48.1', '>')) {
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
    if (version_compare($this->settings->get('db_version', '3.49.1'), '3.49.0', '>')) {
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
      $form->setSettings($settings);
    }
    $this->formsRepository->flush();
  }

  private function moveGoogleAnalyticsFromPremium() {
    global $wpdb;
    if (version_compare($this->settings->get('db_version', '3.38.2'), '3.38.1', '>')) {
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
      $query = "
        UPDATE
          `%s` as n
        JOIN %s as ped ON n.id=ped.newsletter_id
          SET n.ga_campaign = ped.ga_campaign
      ";
      $wpdb->query(
        sprintf(
          $query,
          Newsletter::$_table,
          $premiumTableName
        )
      );
    }
    return true;
  }

  private function detectReferral() {
    $this->referralDetector->detect();
  }
}
