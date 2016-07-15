<?php
namespace MailPoet\Models;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class Newsletter extends Model {
  public static $_table = MP_NEWSLETTERS_TABLE;

  const TYPE_STANDARD = 'standard';
  const TYPE_WELCOME = 'welcome';
  const TYPE_NOTIFICATION = 'notification';
  const TYPE_NOTIFICATION_HISTORY = 'notification_history';

  // standard newsletters
  const STATUS_DRAFT = 'draft';
  const STATUS_SCHEDULED = 'scheduled';
  const STATUS_SENDING = 'sending';
  const STATUS_SENT = 'sent';
  // automatic newsletters status
  const STATUS_ACTIVE = 'active';

  function __construct() {
    parent::__construct();

    $this->addValidations('type', array(
      'required' => __('Please specify a type')
    ));
  }

  function save() {
    if(is_string($this->deleted_at) && strlen(trim($this->deleted_at)) === 0) {
      $this->set_expr('deleted_at', 'NULL');
    }

    $this->set('body',
      is_array($this->body)
      ? json_encode($this->body)
      : $this->body
    );
    return parent::save();
  }

  function setStatus($status = null) {
    if(in_array($status, array(
      self::STATUS_DRAFT,
      self::STATUS_SCHEDULED,
      self::STATUS_SENDING,
      self::STATUS_SENT,
      self::STATUS_ACTIVE
    ))) {
      $this->set('status', $status);
      $this->save();
    }
    return $this;
  }

  function duplicate($data = array()) {
    // get current newsletter's data as an array
    $newsletter_data = $this->asArray();

    // remove id so that it creates a new record
    unset($newsletter_data['id']);

    // merge data with newsletter data (allows override)
    $data = array_merge($newsletter_data, $data);

    $duplicate = self::create();
    $duplicate->hydrate($data);

    // reset timestamps
    $duplicate->set_expr('created_at', 'NOW()');
    $duplicate->set_expr('updated_at', 'NOW()');
    $duplicate->set_expr('deleted_at', 'NULL');

    // reset status
    $duplicate->set('status', self::STATUS_DRAFT);

    $duplicate->save();

    if($duplicate->getErrors() === false) {
      // create relationships between duplicate and segments
      $segments = $this->segments()->findArray();

      if(!empty($segments)) {
        foreach($segments as $segment) {
          $relation = NewsletterSegment::create();
          $relation->segment_id = $segment['id'];
          $relation->newsletter_id = $duplicate->id;
          $relation->save();
        }
      }

      // duplicate options
      $options = NewsletterOption::where('newsletter_id', $this->id)
        ->findArray();

      if(!empty($options)) {
        foreach($options as $option) {
          $relation = NewsletterOption::create();
          $relation->newsletter_id = $duplicate->id;
          $relation->option_field_id = $option['option_field_id'];
          $relation->value = $option['value'];
          $relation->save();
        }
      }
    }

    return $duplicate;
  }

  function createNotificationHistory() {
    // get current newsletter's data as an array
    $newsletter_data = $this->asArray();

    // remove id so that it creates a new record
    unset($newsletter_data['id']);

    // update newsletter columns
    $data = array_merge(
      $newsletter_data,
      array(
        'parent_id' => $this->id,
        'type' => self::TYPE_NOTIFICATION_HISTORY,
        'status' => self::STATUS_SENDING
    ));

    $notification_history = self::create();
    $notification_history->hydrate($data);

    $notification_history->save();

    if($notification_history->getErrors() === false) {
      // create relationships between notification history and segments
      $segments = $this->segments()->findArray();

      if(!empty($segments)) {
        foreach($segments as $segment) {
          $relation = NewsletterSegment::create();
          $relation->segment_id = $segment['id'];
          $relation->newsletter_id = $notification_history->id;
          $relation->save();
        }
      }
    }

    return $notification_history;
  }

  function asArray() {
    $model = parent::asArray();

    if(isset($model['body'])) {
      $model['body'] = json_decode($model['body'], true);
    }
    return $model;
  }

  function delete() {
    // delete all relations to segments
    NewsletterSegment::where('newsletter_id', $this->id)->deleteMany();

    return parent::delete();
  }

  function segments() {
    return $this->has_many_through(
      __NAMESPACE__.'\Segment',
      __NAMESPACE__.'\NewsletterSegment',
      'newsletter_id',
      'segment_id'
    );
  }

  function withSegments() {
    $this->segments = $this->segments()->findArray();
    return $this;
  }

  function options() {
    return $this->has_many_through(
      __NAMESPACE__.'\NewsletterOptionField',
      __NAMESPACE__.'\NewsletterOption',
      'newsletter_id',
      'option_field_id'
    )->select_expr(MP_NEWSLETTER_OPTION_TABLE.'.value');
  }

  function getQueue() {
    return SendingQueue::where('newsletter_id', $this->id)
      ->orderByDesc('updated_at')
      ->findOne();
  }

  function withSendingQueue() {
    $queue = $this->getQueue();
    if($queue === false) {
      $this->queue = false;
    } else {
      $this->queue = $queue->asArray();
    }
    return $this;
  }

  function withOptions() {
    $options = $this->options()->findArray();
    if(empty($options)) {
      $this->options = array();
    } else {
      $this->options = Helpers::arrayColumn($options, 'value', 'name');
    }
    return $this;
  }

  function withTotalSent() {
    // total of subscribers who received the email
    $this->total_sent = (int)SendingQueue::where('newsletter_id', $this->id)
      ->where('status', SendingQueue::STATUS_COMPLETED)
      ->sum('count_processed');
    return $this;
  }

  function withStatistics() {
    $statistics = $this->getStatistics();
    if($statistics === false) {
      $this->statistics = false;
    } else {
      $this->statistics = $statistics->asArray();
    }

    return $this;
  }

  function getStatistics() {
    if($this->queue === false) {
      return false;
    }
    return SendingQueue::tableAlias('queues')
      ->selectExpr(
        'COUNT(DISTINCT(clicks.subscriber_id)) as clicked, ' .
        'COUNT(DISTINCT(opens.subscriber_id)) as opened, ' .
        'COUNT(DISTINCT(unsubscribes.subscriber_id)) as unsubscribed '
      )
      ->leftOuterJoin(
        MP_STATISTICS_CLICKS_TABLE,
        'queues.id = clicks.queue_id',
        'clicks'
      )
      ->leftOuterJoin(
        MP_STATISTICS_OPENS_TABLE,
        'queues.id = opens.queue_id',
        'opens'
      )
      ->leftOuterJoin(
        MP_STATISTICS_UNSUBSCRIBES_TABLE,
        'queues.id = unsubscribes.queue_id',
        'unsubscribes'
      )
      ->where('queues.id', $this->queue['id'])
      ->findOne();
  }

  static function search($orm, $search = '') {
    if(strlen(trim($search)) > 0) {
      $orm->whereLike('subject', '%' . $search . '%');
    }
    return $orm;
  }

  static function filters($data = array()) {
    $type = isset($data['tab']) ? $data['tab'] : null;

    // newsletter types without filters
    if(in_array($type, array(
      self::TYPE_NOTIFICATION_HISTORY
    ))) {
      return false;
    }

    $segments = Segment::orderByAsc('name')->findMany();
    $segment_list = array();
    $segment_list[] = array(
      'label' => __('All Lists'),
      'value' => ''
    );

    foreach($segments as $segment) {
      $newsletters = $segment->newsletters()
        ->filter('filterType', $type)
        ->filter('groupBy', $data);

      $newsletters_count = $newsletters->count();

      if($newsletters_count > 0) {
        $segment_list[] = array(
          'label' => sprintf('%s (%d)', $segment->name, $newsletters_count),
          'value' => $segment->id
        );
      }
    }

    $filters = array(
      'segment' => $segment_list
    );

    return $filters;
  }

  static function filterBy($orm, $data = array()) {
    $type = isset($data['tab']) ? $data['tab'] : null;

    if(!empty($data['filter'])) {
      foreach($data['filter'] as $key => $value) {
        if($key === 'segment') {
          $segment = Segment::findOne($value);
          if($segment !== false) {
            $orm = $segment->newsletters()->filter('filterType', $type);
          }
        }
      }
    }
    return $orm;
  }

  static function filterWithOptions($orm) {
    $orm = $orm->select(MP_NEWSLETTERS_TABLE.'.*');
    $optionFields = NewsletterOptionField::findArray();
    foreach($optionFields as $optionField) {
      $orm = $orm->select_expr(
        'IFNULL(GROUP_CONCAT(CASE WHEN ' .
        MP_NEWSLETTER_OPTION_FIELDS_TABLE . '.id=' . $optionField['id'] . ' THEN ' .
        MP_NEWSLETTER_OPTION_TABLE . '.value END), NULL) as "' . $optionField['name'].'"');
    }
    $orm = $orm
      ->left_outer_join(
        MP_NEWSLETTER_OPTION_TABLE,
        array(
          MP_NEWSLETTERS_TABLE.'.id',
          '=',
          MP_NEWSLETTER_OPTION_TABLE.'.newsletter_id'
        )
      )
      ->left_outer_join(
        MP_NEWSLETTER_OPTION_FIELDS_TABLE,
        array(
          MP_NEWSLETTER_OPTION_FIELDS_TABLE.'.id',
          '=',
          MP_NEWSLETTER_OPTION_TABLE.'.option_field_id'
        )
      )
      ->group_by(MP_NEWSLETTERS_TABLE.'.id');
    return $orm;
  }

  static function groups($data = array()) {
    $type = isset($data['tab']) ? $data['tab'] : null;

    // newsletter types without groups
    if(in_array($type, array(
      self::TYPE_NOTIFICATION_HISTORY
    ))) {
      return false;
    }

    $groups = array(
      array(
        'name' => 'all',
        'label' => __('All'),
        'count' => Newsletter::getPublished()
          ->filter('filterType', $type)
          ->count()
      )
    );

    switch($type) {
      case self::TYPE_STANDARD:
        $groups = array_merge($groups, array(
          array(
            'name' => self::STATUS_DRAFT,
            'label' => __('Draft'),
            'count' => Newsletter::getPublished()
              ->filter('filterType', $type)
              ->filter('filterStatus', self::STATUS_DRAFT)
              ->count()
          ),
          array(
            'name' => self::STATUS_SCHEDULED,
            'label' => __('Scheduled'),
            'count' => Newsletter::getPublished()
              ->filter('filterType', $type)
              ->filter('filterStatus', self::STATUS_SCHEDULED)
              ->count()
          ),
          array(
            'name' => self::STATUS_SENDING,
            'label' => __('Sending'),
            'count' => Newsletter::getPublished()
              ->filter('filterType', $type)
              ->filter('filterStatus', self::STATUS_SENDING)
              ->count()
          ),
          array(
            'name' => self::STATUS_SENT,
            'label' => __('Sent'),
            'count' => Newsletter::getPublished()
              ->filter('filterType', $type)
              ->filter('filterStatus', self::STATUS_SENT)
              ->count()
          )
        ));
        break;

      case self::TYPE_WELCOME:
      case self::TYPE_NOTIFICATION:
        $groups = array_merge($groups, array(
          array(
            'name' => self::STATUS_ACTIVE,
            'label' => __('Active'),
            'count' => Newsletter::getPublished()
              ->filter('filterType', $type)
              ->filter('filterStatus', self::STATUS_ACTIVE)
              ->count()
          ),
          array(
            'name' => self::STATUS_DRAFT,
            'label' => __('Not active'),
            'count' => Newsletter::getPublished()
              ->filter('filterType', $type)
              ->filter('filterStatus', self::STATUS_DRAFT)
              ->count()
          )
        ));
        break;
    }

    $groups[] = array(
      'name' => 'trash',
      'label' => __('Trash'),
      'count' => Newsletter::getTrashed()
        ->filter('filterType', $type)
        ->count()
    );

    return $groups;
  }

  static function groupBy($orm, $data = array()) {
    $group = (!empty($data['group'])) ? $data['group'] : 'all';

    switch($group) {
      case self::STATUS_DRAFT:
      case self::STATUS_SCHEDULED:
      case self::STATUS_SENDING:
      case self::STATUS_SENT:
      case self::STATUS_ACTIVE:
        $orm
          ->whereNull('deleted_at')
          ->filter('filterStatus', $group);
        break;

      case 'trash':
        $orm->whereNotNull('deleted_at');
        break;

      default:
        $orm->whereNull('deleted_at');
    }
    return $orm;
  }

  static function filterStatus($orm, $status = false) {
    if(in_array($status, array(
      self::STATUS_DRAFT,
      self::STATUS_SCHEDULED,
      self::STATUS_SENDING,
      self::STATUS_SENT,
      self::STATUS_ACTIVE
    ))) {
      $orm->where('status', $status);
    }
    return $orm;
  }

  static function filterType($orm, $type = false) {
    if(in_array($type, array(
      self::TYPE_STANDARD,
      self::TYPE_WELCOME,
      self::TYPE_NOTIFICATION,
      self::TYPE_NOTIFICATION_HISTORY
    ))) {
      $orm->where('type', $type);
    }
    return $orm;
  }

  static function listingQuery($data = array()) {
    return self::select(array(
        'id',
        'subject',
        'type',
        'status',
        'updated_at',
        'deleted_at'
      ))
      ->filter('filterType', $data['tab'])
      ->filter('filterBy', $data)
      ->filter('groupBy', $data)
      ->filter('search', $data['search']);
  }

  static function createOrUpdate($data = array()) {
    $newsletter = false;

    if(isset($data['id']) && (int)$data['id'] > 0) {
      $newsletter = self::findOne((int)$data['id']);
    }

    if($newsletter === false) {
      $newsletter = self::create();

      // set default sender based on settings
      if(empty($data['sender'])) {
        $sender = Setting::getValue('sender', array());
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
      if(empty($data['reply_to'])) {
        $reply_to = Setting::getValue('reply_to', array());
        $data['reply_to_name'] = (
          !empty($reply_to['name'])
          ? $reply_to['name']
          : ''
        );
        $data['reply_to_address'] = (
          !empty($reply_to['address'])
          ? $reply_to['address']
          : ''
        );
      }

      $newsletter->hydrate($data);
    } else {
      unset($data['id']);
      $newsletter->set($data);
    }

    $newsletter->save();
    return $newsletter;
  }

  static function getWelcomeNotificationsForSegments($segments) {
    return NewsletterOption::table_alias('options')
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
}