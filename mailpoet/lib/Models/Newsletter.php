<?php

namespace MailPoet\Models;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Options\NewsletterOptionFieldsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Util\Helpers;
use MailPoet\Util\Security;

/**
 * @property int $id
 * @property int $parentId
 * @property string $type
 * @property object|array|bool $queue
 * @property string $hash
 * @property string $senderName
 * @property string $senderAddress
 * @property string $replyToName
 * @property string $replyToAddress
 * @property string $status
 * @property string|object $meta
 * @property array $options
 * @property bool|array $statistics
 * @property string $sentAt
 * @property string $deletedAt
 * @property int $totalSent
 * @property int $totalScheduled
 * @property array $segments
 * @property string $subject
 * @property string $preheader
 * @property string|array|null $body
 * @property string|null $schedule
 * @property bool|null $isScheduled
 * @property string|null $scheduledAt
 * @property string $gaCampaign
 * @property string $event
 * @property string $unsubscribeToken
 */

class Newsletter extends Model {
  public static $_table = MP_NEWSLETTERS_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration
  const TYPE_AUTOMATIC = NewsletterEntity::TYPE_AUTOMATIC;
  const TYPE_AUTOMATION = NewsletterEntity::TYPE_AUTOMATION;
  const TYPE_STANDARD = NewsletterEntity::TYPE_STANDARD;
  const TYPE_WELCOME = NewsletterEntity::TYPE_WELCOME;
  const TYPE_NOTIFICATION = NewsletterEntity::TYPE_NOTIFICATION;
  const TYPE_NOTIFICATION_HISTORY = NewsletterEntity::TYPE_NOTIFICATION_HISTORY;
  const TYPE_WC_TRANSACTIONAL_EMAIL = NewsletterEntity::TYPE_WC_TRANSACTIONAL_EMAIL;
  const TYPE_RE_ENGAGEMENT = NewsletterEntity::TYPE_RE_ENGAGEMENT;
  // standard newsletters
  const STATUS_DRAFT = NewsletterEntity::STATUS_DRAFT;
  const STATUS_SCHEDULED = NewsletterEntity::STATUS_SCHEDULED;
  const STATUS_SENDING = NewsletterEntity::STATUS_SENDING;
  const STATUS_SENT = NewsletterEntity::STATUS_SENT;
  // automatic newsletters status
  const STATUS_ACTIVE = NewsletterEntity::STATUS_ACTIVE;

  public function __construct() {
    parent::__construct();
    $this->addValidations('type', [
      'required' => __('Please specify a type.', 'mailpoet'),
    ]);
  }

  public function queue() {
    return $this->hasOne(__NAMESPACE__ . '\SendingQueue', 'newsletter_id', 'id');
  }

  public function children() {
    return $this->hasMany(
      __NAMESPACE__ . '\Newsletter',
      'parent_id',
      'id'
    );
  }

  public function parent() {
    return $this->hasOne(
      __NAMESPACE__ . '\Newsletter',
      'id',
      'parent_id'
    );
  }

  public function segments() {
    return $this->hasManyThrough(
      __NAMESPACE__ . '\Segment',
      __NAMESPACE__ . '\NewsletterSegment',
      'newsletter_id',
      'segment_id'
    );
  }

  public function segmentRelations() {
    return $this->hasMany(
      __NAMESPACE__ . '\NewsletterSegment',
      'newsletter_id',
      'id'
    );
  }

  public function options() {
    return $this->hasManyThrough(
      __NAMESPACE__ . '\NewsletterOptionField',
      __NAMESPACE__ . '\NewsletterOption',
      'newsletter_id',
      'option_field_id'
    )->select_expr(MP_NEWSLETTER_OPTION_TABLE . '.value');
  }

  public function save() {
    if (is_string($this->deletedAt) && strlen(trim($this->deletedAt)) === 0) {
      $this->set_expr('deleted_at', 'NULL');
    }

    if (isset($this->body) && ($this->body !== false)) {
      $this->body = $this->getBodyString();
      $this->set(
        'body',
        $this->body
      );
    }

    $this->set('hash',
      ($this->hash)
      ? $this->hash
      : Security::generateHash()
    );
    return parent::save();
  }

  public function trash() {
    $this->save();
    trigger_error('Calling Newsletter::trash() is deprecated and will be removed. Use \MailPoet\Newsletter\NewslettersRepository instead.', E_USER_DEPRECATED);
    ContainerWrapper::getInstance()->get(NewslettersRepository::class)->bulkTrash([$this->id]);
    return $this;
  }

  public function restore() {
    $this->save();
    trigger_error('Calling Newsletter::restore() is deprecated and will be removed. Use \MailPoet\Newsletter\NewslettersRepository instead.', E_USER_DEPRECATED);
    ContainerWrapper::getInstance()->get(NewslettersRepository::class)->bulkRestore([$this->id]);
    return $this;
  }

  public function delete() {
    trigger_error('Calling Newsletter::delete() is deprecated and will be removed. Use \MailPoet\Newsletter\NewslettersRepository instead.', E_USER_DEPRECATED);
    ContainerWrapper::getInstance()->get(NewslettersRepository::class)->bulkDelete([$this->id]);
    return null;
  }

  public function setStatus($status = null) {
    if ($status === self::STATUS_ACTIVE) {
      if (!$this->body || empty(json_decode($this->getBodyString()))) {
        $this->setError(
          Helpers::replaceLinkTags(
            __('This is an empty email without any content and it cannot be sent. Please update [link]the email[/link].'),
            'admin.php?page=mailpoet-newsletter-editor&id=' . $this->id
          )
        );
        return $this;
      }
    }
    if (
      in_array($status, [
        self::STATUS_DRAFT,
        self::STATUS_SCHEDULED,
        self::STATUS_SENDING,
        self::STATUS_SENT,
        self::STATUS_ACTIVE,
      ])
    ) {
      $this->set('status', $status);
      $this->save();
    }

    $typesWithActivation = [self::TYPE_NOTIFICATION, self::TYPE_WELCOME, self::TYPE_AUTOMATIC];

    if (($status === self::STATUS_DRAFT) && in_array($this->type, $typesWithActivation)) {
      ScheduledTask::pauseAllByNewsletter($this);
    }
    if (($status === self::STATUS_ACTIVE) && in_array($this->type, $typesWithActivation)) {
      ScheduledTask::setScheduledAllByNewsletter($this);
    }
    return $this;
  }

  /**
   * @deprecated This method can be removed after 2022-11-11. Make sure it is removed together with
   * \MailPoet\Models\NewsletterOption and \MailPoet\Models\NewsletterOptionField.
   */
  public function duplicate($data = []) {
    self::deprecationError(__METHOD__);

    $newsletterData = $this->asArray();

    // remove id so that it creates a new record
    unset($newsletterData['id']);

    // merge data with newsletter data (allows override)
    $data['unsubscribe_token'] = Security::generateUnsubscribeToken(self::class);
    $data = array_merge($newsletterData, $data);

    $duplicate = self::create();
    $duplicate->hydrate($data);

    // reset timestamps
    $duplicate->set_expr('created_at', 'NOW()');
    $duplicate->set_expr('updated_at', 'NOW()');
    $duplicate->set_expr('deleted_at', 'NULL');

    // reset status
    $duplicate->set('status', self::STATUS_DRAFT);

    // reset hash
    $duplicate->set('hash', null);

    // reset sent at date
    $duplicate->set('sent_at', null);

    $duplicate->save();

    if ($duplicate->getErrors() === false) {
      // create relationships between duplicate and segments
      $segments = $this->segments()->findMany();

      if (!empty($segments)) {
        foreach ($segments as $segment) {
          $relation = NewsletterSegment::create();
          $relation->segmentId = $segment->id;
          $relation->newsletterId = $duplicate->id;
          $relation->save();
        }
      }

      // duplicate options
      $options = NewsletterOption::where('newsletter_id', $this->id)
        ->findMany();

      $ignoredOptionFieldIds = Helpers::flattenArray(
        NewsletterOptionField::whereIn('name', ['isScheduled', 'scheduledAt'])
          ->select('id')
          ->findArray()
      );

      if (!empty($options)) {
        foreach ($options as $option) {
          if (in_array($option->optionFieldId, $ignoredOptionFieldIds)) {
            continue;
          }
          $relation = NewsletterOption::create();
          $relation->newsletterId = $duplicate->id;
          $relation->optionFieldId = $option->optionFieldId;
          $relation->value = $option->value;
          $relation->save();
        }
      }
    }

    return $duplicate;
  }

  public function createNotificationHistory() {
    $newsletterData = $this->asArray();

    // remove id so that it creates a new record
    unset($newsletterData['id']);

    $data = array_merge(
      $newsletterData,
      [
        'parent_id' => $this->id,
        'type' => self::TYPE_NOTIFICATION_HISTORY,
        'status' => self::STATUS_SENDING,
        'unsubscribe_token' => Security::generateUnsubscribeToken(self::class),
      ]
    );

    $notificationHistory = self::create();
    $notificationHistory->hydrate($data);

    // reset timestamps
    $notificationHistory->set_expr('created_at', 'NOW()');
    $notificationHistory->set_expr('updated_at', 'NOW()');
    $notificationHistory->set_expr('deleted_at', 'NULL');

    // reset hash
    $notificationHistory->set('hash', null);

    $notificationHistory->save();

    if ($notificationHistory->getErrors() === false) {
      // create relationships between notification history and segments
      $segments = $this->segments()->findMany();

      if (!empty($segments)) {
        foreach ($segments as $segment) {
          $relation = NewsletterSegment::create();
          $relation->segmentId = $segment->id;
          $relation->newsletterId = $notificationHistory->id;
          $relation->save();
        }
      }
    }

    return $notificationHistory;
  }

  public function asArray() {
    $model = parent::asArray();

    if (isset($model['body'])) {
      $model['body'] = json_decode($model['body'], true);
    }
    return $model;
  }

  public function withSegments($inclDeleted = false) {
    $this->segments = $this->segments()->findArray();
    if ($inclDeleted) {
      $this->withDeletedSegments();
    }
    return $this;
  }

  public function withDeletedSegments() {
    if (!empty($this->segments)) {
      $segmentIds = array_column($this->segments, 'id');
      $links = $this->segmentRelations()
        ->whereNotIn('segment_id', $segmentIds)->findArray();
      $deletedSegments = [];

      foreach ($links as $link) {
        $deletedSegments[] = [
          'id' => $link['segment_id'],
          'name' => __('Deleted list', 'mailpoet'),
        ];
      }
      $this->segments = array_merge($this->segments, $deletedSegments);
    }

    return $this;
  }

  public function getQueue($columns = '*') {
    return SendingTask::getByNewsletterId($this->id);
  }

  public function getBodyString(): string {
    if (is_array($this->body)) {
      return (string)json_encode($this->body);
    }
    if ($this->body === null) {
      return '';
    }
    return $this->body;
  }

  public function withSendingQueue() {
    $queue = $this->getQueue();
    if ($queue === false) {
      $this->queue = false;
    } else {
      $this->queue = $queue->asArray();
    }
    return $this;
  }

  public function withOptions() {
    $options = $this->options()->findArray();
    if (empty($options)) {
      $this->options = [];
    } else {
      $this->options = array_column($options, 'value', 'name');
    }
    return $this;
  }

  public static function filterWithOptions($orm, $type) {
    $orm = $orm->select(MP_NEWSLETTERS_TABLE . '.*');
    $optionFieldsRepository = ContainerWrapper::getInstance()->get(NewsletterOptionFieldsRepository::class);
    $optionFieldsEntities = $optionFieldsRepository->findAll();
    foreach ($optionFieldsEntities as $optionField) {
      if ($optionField->getNewsletterType() !== $type) {
        continue;
      }
      $orm = $orm->select_expr(
        'IFNULL(GROUP_CONCAT(CASE WHEN ' .
        MP_NEWSLETTER_OPTION_FIELDS_TABLE . '.id=' . $optionField->getId() . ' THEN ' .
        MP_NEWSLETTER_OPTION_TABLE . '.value END), NULL) as "' . $optionField->getName() . '"');
    }
    $orm = $orm
      ->left_outer_join(
        MP_NEWSLETTER_OPTION_TABLE,
        [
          MP_NEWSLETTERS_TABLE . '.id',
          '=',
          MP_NEWSLETTER_OPTION_TABLE . '.newsletter_id',
        ]
      )
      ->left_outer_join(
        MP_NEWSLETTER_OPTION_FIELDS_TABLE,
        [
          MP_NEWSLETTER_OPTION_FIELDS_TABLE . '.id',
          '=',
          MP_NEWSLETTER_OPTION_TABLE . '.option_field_id',
        ]
      )
      ->group_by(MP_NEWSLETTERS_TABLE . '.id');
    return $orm;
  }

  public static function filterStatus($orm, $status = false) {
    if (
      in_array($status, [
      self::STATUS_DRAFT,
      self::STATUS_SCHEDULED,
      self::STATUS_SENDING,
      self::STATUS_SENT,
      self::STATUS_ACTIVE,
      ])
    ) {
      $orm->where('status', $status);
    }
    return $orm;
  }

  /**
   * @deprecated This method can be removed after 2022-11-11. Make sure it is removed together with
   * \MailPoet\Models\NewsletterOption and \MailPoet\Models\NewsletterOptionField.
   */
  public static function filterType($orm, $type = false, $group = false) {
    self::deprecationError(__METHOD__);

    if (
      in_array($type, [
      self::TYPE_STANDARD,
      self::TYPE_WELCOME,
      self::TYPE_AUTOMATIC,
      self::TYPE_NOTIFICATION,
      self::TYPE_NOTIFICATION_HISTORY,
      ])
    ) {
      if ($type === self::TYPE_AUTOMATIC && $group) {
        $orm = $orm->join(
          NewsletterOptionField::$_table,
          [
            'option_fields.newsletter_type', '=', self::$_table . '.type',
          ],
          'option_fields'
        )
        ->join(
          NewsletterOption::$_table,
          [
            'options.newsletter_id', '=', self::$_table . '.id',
          ],
          'options'
        )
        ->whereRaw('`options`.`option_field_id` = `option_fields`.`id`')
        ->where('options.value', $group);
      }
      $orm = $orm->where(self::$_table . '.type', $type);
    }
    return $orm;
  }

  public static function createOrUpdate($data = []) {
    $data['unsubscribe_token'] = Security::generateUnsubscribeToken(self::class);
    return parent::_createOrUpdate($data, false, function($data) {
      $settings = SettingsController::getInstance();
      // set default sender based on settings
      if (empty($data['sender'])) {
        $sender = $settings->get('sender', []);
        $data['sender_name'] = (
          !empty($sender['name'])
          ? $sender['name']
          : ''
        );
        $data['sender_address'] = (
          !empty($sender['address'])
          ? $sender['address']
          : ''
        );
      }

      // set default reply_to based on settings
      if (empty($data['reply_to'])) {
        $replyTo = $settings->get('reply_to', []);
        $data['reply_to_name'] = (
          !empty($replyTo['name'])
          ? $replyTo['name']
          : ''
        );
        $data['reply_to_address'] = (
          !empty($replyTo['address'])
          ? $replyTo['address']
          : ''
        );
      }

      return $data;
    });
  }

  /**
   * @deprecated This method can be removed after 2022-11-11. Make sure it is removed together with
   * \MailPoet\Models\NewsletterOption and \MailPoet\Models\NewsletterOptionField.
   */
  public static function getWelcomeNotificationsForSegments($segments) {
    self::deprecationError(__METHOD__);

    return NewsletterOption::tableAlias('options')
      ->select('options.newsletter_id')
      ->select('options.value', 'segment_id')
      ->join(
        self::$_table,
        'newsletters.id = options.newsletter_id',
        'newsletters'
      )
      ->join(
        MP_NEWSLETTER_OPTION_FIELDS_TABLE,
        'option_fields.id = options.option_field_id',
        'option_fields'
      )
      ->whereNull('newsletters.deleted_at')
      ->where('newsletters.type', 'welcome')
      ->where('option_fields.name', 'segment')
      ->whereIn('options.value', $segments)
      ->findMany();
  }

  public static function getByHash($hash) {
    return parent::where('hash', $hash)
      ->findOne();
  }

  public function getMeta() {
    if (!$this->meta) return;

    return (Helpers::isJson($this->meta) && is_string($this->meta)) ? json_decode($this->meta, true) : $this->meta;
  }

  public static function findOneWithOptions($id) {
    $newsletter = self::findOne($id);
    if (!$newsletter instanceof self) {
      return false;
    }
    return self::filter('filterWithOptions', $newsletter->type)->findOne($id);
  }

  private static function deprecationError($methodName) {
    trigger_error(
      'Calling ' . esc_html($methodName) . ' is deprecated and will be removed. Use \MailPoet\Newsletter\NewslettersRepository and \MailPoet\Entities\NewsletterEntity instead.',
      E_USER_DEPRECATED
    );
  }
}
