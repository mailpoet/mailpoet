<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class Newsletter extends Model {
  public static $_table = MP_NEWSLETTERS_TABLE;

  const TYPE_STANDARD = 'standard';
  const TYPE_WELCOME = 'welcome';
  const TYPE_NOTIFICATION = 'notification';

  function __construct() {
    parent::__construct();

    $this->addValidations('type', array(
      'required' => __('Please specify a type.')
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
        'count(DISTINCT(clicks.subscriber_id)) as clicked, ' .
        'count(DISTINCT(opens.subscriber_id)) as opened, ' .
        'count(DISTINCT(unsubscribes.subscriber_id)) as unsubscribed '
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
    $segments = Segment::orderByAsc('name')->findMany();
    $segment_list = array();
    $segment_list[] = array(
      'label' => __('All Lists'),
      'value' => ''
    );

    foreach($segments as $segment) {
      $newsletters = $segment->newsletters()
        ->where('type', $data['tab'])
        ->filter('groupBy', $data['group']);

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

  static function filterBy($orm, $filters = null) {
    if(!empty($filters)) {
      foreach($filters as $key => $value) {
        if($key === 'segment') {
          $segment = Segment::findOne($value);
          if($segment !== false) {
            $orm = $segment->newsletters();
          }
        }
      }
    }
    return $orm;
  }

  static function filterWithOptions($orm) {
    $orm = $orm->select(MP_NEWSLETTERS_TABLE.'.*');
    $optionFields = NewsletterOptionField::findArray();
    foreach ($optionFields as $optionField) {
      $orm = $orm->select_expr(
        'IFNULL(GROUP_CONCAT(CASE WHEN ' .
        MP_NEWSLETTER_OPTION_FIELDS_TABLE . '.id=' . $optionField['id'] . ' THEN ' .
        MP_NEWSLETTER_OPTION_TABLE . '.value END), NULL) as "' . $optionField['name'].'"');
    }
    $orm = $orm
      ->left_outer_join(
        MP_NEWSLETTER_OPTION_TABLE,
        array(MP_NEWSLETTERS_TABLE.'.id', '=',
          MP_NEWSLETTER_OPTION_TABLE.'.newsletter_id'))
      ->left_outer_join(
        MP_NEWSLETTER_OPTION_FIELDS_TABLE,
        array(MP_NEWSLETTER_OPTION_FIELDS_TABLE.'.id','=',
          MP_NEWSLETTER_OPTION_TABLE.'.option_field_id'))
      ->group_by(MP_NEWSLETTERS_TABLE.'.id');
    return $orm;
  }

  static function groups($data = array()) {
    return array(
      array(
        'name' => 'all',
        'label' => __('All'),
        'count' => Newsletter::getPublished()->where('type', $data['tab'])->count()
      ),
      array(
        'name' => 'trash',
        'label' => __('Trash'),
        'count' => Newsletter::getTrashed()->where('type', $data['tab'])->count()
      )
    );
  }

  static function listingQuery($data = array()) {
    return self::where('type', $data['tab'])
      ->filter('filterBy', $data['filter'])
      ->filter('groupBy', $data['group'])
      ->filter('search', $data['search']);
  }

  static function groupBy($orm, $group = null) {
    if($group === 'trash') {
      $orm->whereNotNull('deleted_at');
    } else {
      $orm->whereNull('deleted_at');
    }
    return $orm;
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