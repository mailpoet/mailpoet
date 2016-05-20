<?php
namespace MailPoet\Models;
use MailPoet\Mailer\Mailer;
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\Util\Helpers;
use MailPoet\Subscription;

if(!defined('ABSPATH')) exit;

class Subscriber extends Model {
  public static $_table = MP_SUBSCRIBERS_TABLE;

  const STATUS_SUBSCRIBED = 'subscribed';
  const STATUS_UNSUBSCRIBED = 'unsubscribed';
  const STATUS_UNCONFIRMED = 'unconfirmed';

  function __construct() {
    parent::__construct();

    $this->addValidations('email', array(
      'required' => __('You need to enter your email address.'),
      'isEmail' => __('Your email address is invalid.')
    ));
  }

  static function findOne($id = null) {
    if(is_int($id) || (string)(int)$id === $id) {
      return parent::findOne($id);
    } else {
      return parent::where('email', $id)->findOne();
    }
  }

  function segments() {
    return $this->has_many_through(
      __NAMESPACE__.'\Segment',
      __NAMESPACE__.'\SubscriberSegment',
      'subscriber_id',
      'segment_id'
    )
    ->where(MP_SUBSCRIBER_SEGMENT_TABLE.'.status', self::STATUS_SUBSCRIBED);
  }

  function delete() {
    // WP Users cannot be deleted
    if($this->wp_user_id !== null) {
      return false;
    } else {
      // delete all relations to segments
      SubscriberSegment::where('subscriber_id', $this->id)->deleteMany();

      return parent::delete();
    }
  }

  function trash() {
    // WP Users cannot be trashed
    if($this->wp_user_id !== null) {
      return false;
    } else {
      return parent::trash();
    }
  }

  function sendConfirmationEmail() {
    if($this->status === self::STATUS_UNCONFIRMED) {
      $signup_confirmation = Setting::getValue('signup_confirmation');

      $segments = $this->segments()->findMany();
      $segment_names = array_map(function($segment) {
        return $segment->name;
      }, $segments);

      $body = nl2br($signup_confirmation['body']);

      // replace list of segments shortcode
      $body = str_replace(
        '[lists_to_confirm]',
        '<strong>'.join(', ', $segment_names).'</strong>',
        $body
      );

      // replace activation link
      $body = str_replace(
        array(
          '[activation_link]',
          '[/activation_link]'
        ),
        array(
          '<a href="'.esc_attr(Subscription\Url::getConfirmationUrl($this)).'">',
          '</a>'
        ),
        $body
      );

      // build email data
      $email = array(
        'subject' => $signup_confirmation['subject'],
        'body' => array(
          'html' => $body,
          'text' => $body
        )
      );

      // convert subscriber to array
      $subscriber = $this->asArray();

      // set from
      $from = (
        !empty($signup_confirmation['from'])
        && !empty($signup_confirmation['from']['address'])
      ) ? $signup_confirmation['from']
        : false;

      // set reply to
      $reply_to = (
        !empty($signup_confirmation['reply_to'])
        && !empty($signup_confirmation['reply_to']['address'])
      ) ? $signup_confirmation['reply_to']
        : false;

      // send email
      try {
        $mailer = new Mailer(false, $from, $reply_to);
        return $mailer->send($email, $subscriber);
      } catch(\Exception $e) {
        $this->setError($e->getMessage());
        return false;
      }
    }
    return false;
  }

  static function generateToken($email = null) {
    if($email !== null) {
      return md5(AUTH_KEY.$email);
    }
    return false;
  }

  static function verifyToken($email, $token) {
    return (self::generateToken($email) === $token);
  }

  static function subscribe($subscriber_data = array(), $segment_ids = array()) {
    if(empty($subscriber_data) or empty($segment_ids)) {
      return false;
    }

    $signup_confirmation_enabled = (bool)Setting::getValue(
      'signup_confirmation.enabled'
    );

    $subscriber = self::createOrUpdate($subscriber_data);
    $errors = $subscriber->getErrors();

    if($errors === false && $subscriber->id > 0) {
      $subscriber = self::findOne($subscriber->id);

      // restore deleted subscriber
      if($subscriber->deleted_at !== NULL) {
        $subscriber->setExpr('deleted_at', 'NULL');
      }

      if($subscriber->status !== self::STATUS_SUBSCRIBED) {
        // auto subscribe when signup confirmation is disabled
        if($signup_confirmation_enabled === true) {
          $subscriber->set('status', self::STATUS_UNCONFIRMED);
        } else {
          $subscriber->set('status', self::STATUS_SUBSCRIBED);
        }
      }

      if($subscriber->save()) {
        // link subscriber to segments
        SubscriberSegment::addSubscriptions($subscriber, $segment_ids);

        // signup confirmation
        if($subscriber->status !== self::STATUS_SUBSCRIBED) {
          $subscriber->sendConfirmationEmail();
        }

        // welcome email
        Scheduler::scheduleSubscriberWelcomeNotification(
          $subscriber->id,
          $segment_ids
        );
      }
    }

    return $subscriber;
  }

  static function search($orm, $search = '') {
    if(strlen(trim($search) === 0)) {
      return $orm;
    }

    return $orm->where_raw(
      '(`email` LIKE ? OR `first_name` LIKE ? OR `last_name` LIKE ?)',
      array('%'.$search.'%', '%'.$search.'%', '%'.$search.'%')
    );
  }

  static function filters($orm, $group = 'all') {
    $segments = Segment::orderByAsc('name')->findMany();
    $segment_list = array();
    $segment_list[] = array(
      'label' => __('All segments'),
      'value' => ''
    );

    $subscribers_without_segment = self::filter('withoutSegments')->count();
    $subscribers_without_segment_label = sprintf(
      __('Subscribers without a segment (%s)'),
      number_format($subscribers_without_segment)
    );

    $segment_list[] = array(
      'label' => $subscribers_without_segment_label,
      'value' => 'none'
    );

    foreach($segments as $segment) {
      $subscribers_count = $segment->subscribers()
        ->filter('groupBy', $group)
        ->count();

      $label = sprintf(
        '%s (%s)',
        $segment->name,
        number_format($subscribers_count)
      );

      $segment_list[] = array(
        'label' => $label,
        'value' => $segment->id()
      );
    }

    $filters = array(
      'segment' => $segment_list
    );

    return $filters;
  }

  static function filterBy($orm, $filters = null) {
    if(empty($filters)) {
      return $orm;
    }
    foreach($filters as $key => $value) {
      if($key === 'segment') {
        if($value === 'none') {
          return self::filter('withoutSegments');
        } else {
          $segment = Segment::findOne($value);
          if($segment !== false) {
            return $segment->subscribers();
          }
        }
      }
    }
    return $orm;
  }

  static function groups() {
    return array(
      array(
        'name' => 'all',
        'label' => __('All'),
        'count' => self::getPublished()->count()
      ),
      array(
        'name' => self::STATUS_SUBSCRIBED,
        'label' => __('Subscribed'),
        'count' => self::filter(self::STATUS_SUBSCRIBED)->count()
      ),
      array(
        'name' => self::STATUS_UNCONFIRMED,
        'label' => __('Unconfirmed'),
        'count' => self::filter(self::STATUS_UNCONFIRMED)->count()
      ),
      array(
        'name' => self::STATUS_UNSUBSCRIBED,
        'label' => __('Unsubscribed'),
        'count' => self::filter(self::STATUS_UNSUBSCRIBED)->count()
      ),
      array(
        'name' => 'trash',
        'label' => __('Trash'),
        'count' => self::getTrashed()->count()
      )
    );
  }

  static function groupBy($orm, $group = null) {
    if($group === 'trash') {
      return $orm->whereNotNull('deleted_at');
    } else if($group === 'all') {
      return $orm->whereNull('deleted_at');
    } else {
      return $orm->filter($group);
    }
  }

  static function filterWithCustomFields($orm) {
    $orm = $orm->select(MP_SUBSCRIBERS_TABLE.'.*');
    $customFields = CustomField::findArray();
    foreach ($customFields as $customField) {
      $orm = $orm->select_expr(
        'IFNULL(GROUP_CONCAT(CASE WHEN ' .
        MP_CUSTOM_FIELDS_TABLE . '.id=' . $customField['id'] . ' THEN ' .
        MP_SUBSCRIBER_CUSTOM_FIELD_TABLE . '.value END), NULL) as "' . $customField['name'].'"');
    }
    $orm = $orm
      ->leftOuterJoin(
        MP_SUBSCRIBER_CUSTOM_FIELD_TABLE,
        array(MP_SUBSCRIBERS_TABLE.'.id', '=',
          MP_SUBSCRIBER_CUSTOM_FIELD_TABLE.'.subscriber_id'))
      ->leftOuterJoin(
        MP_CUSTOM_FIELDS_TABLE,
        array(MP_CUSTOM_FIELDS_TABLE.'.id','=',
          MP_SUBSCRIBER_CUSTOM_FIELD_TABLE.'.custom_field_id'))
      ->groupBy(MP_SUBSCRIBERS_TABLE.'.id');
    return $orm;
  }

  static function filterWithCustomFieldsForExport($orm) {
    $orm = $orm->select(MP_SUBSCRIBERS_TABLE.'.*');
    $customFields = CustomField::findArray();
    foreach ($customFields as $customField) {
      $orm = $orm->selectExpr(
        'MAX(CASE WHEN ' .
        MP_CUSTOM_FIELDS_TABLE . '.id=' . $customField['id'] . ' THEN ' .
        MP_SUBSCRIBER_CUSTOM_FIELD_TABLE . '.value END) as "' . $customField['id'].'"'
      );
    }
    $orm = $orm
      ->leftOuterJoin(
        MP_SUBSCRIBER_CUSTOM_FIELD_TABLE,
        array(MP_SUBSCRIBERS_TABLE.'.id', '=',
          MP_SUBSCRIBER_CUSTOM_FIELD_TABLE.'.subscriber_id'))
      ->leftOuterJoin(
        MP_CUSTOM_FIELDS_TABLE,
        array(MP_CUSTOM_FIELDS_TABLE.'.id','=',
          MP_SUBSCRIBER_CUSTOM_FIELD_TABLE.'.custom_field_id'));
    return $orm;
  }

  static function getSubscribedInSegments($segment_ids) {
    $subscribers = SubscriberSegment::table_alias('relation')
      ->whereIn('relation.segment_id', $segment_ids)
      ->where('relation.status', 'subscribed')
      ->join(
        MP_SUBSCRIBERS_TABLE,
        'subscribers.id = relation.subscriber_id',
        'subscribers'
      )
      ->where('subscribers.status', 'subscribed');
    return $subscribers;
  }

  function customFields() {
    return $this->hasManyThrough(
      __NAMESPACE__.'\CustomField',
      __NAMESPACE__.'\SubscriberCustomField',
      'subscriber_id',
      'custom_field_id'
    )->select_expr(MP_SUBSCRIBER_CUSTOM_FIELD_TABLE.'.value');
  }

  static function createOrUpdate($data = array()) {
    $subscriber = false;
    if(is_array($data) && !empty($data)) {
      $data = stripslashes_deep($data);
    }

    if(isset($data['id']) && (int)$data['id'] > 0) {
      $subscriber = self::findOne((int)$data['id']);
      unset($data['id']);
    }

    if($subscriber === false && !empty($data['email'])) {
      $subscriber = self::where('email', $data['email'])->findOne();
      if($subscriber !== false) {
        unset($data['email']);
      }
    }

    // segments
    $segment_ids = false;
    if(array_key_exists('segments', $data)) {
      $segment_ids = (array)$data['segments'];
      unset($data['segments']);
    }

    // custom fields
    $custom_fields = array();

    foreach($data as $key => $value) {
      if(strpos($key, 'cf_') === 0) {
        if(is_array($value)) {
          $value = array_filter($value);
          $value = reset($value);
        }
        $custom_fields[(int)substr($key, 3)] = $value;
        unset($data[$key]);
      }
    }

    $old_status = false;
    $new_status = false;

    if($subscriber === false) {
      $subscriber = self::create();
      $subscriber->hydrate($data);
    } else {
      $old_status = $subscriber->status;
      $subscriber->set($data);
      $new_status = $subscriber->status;
    }

    if($subscriber->save()) {
      if(!empty($custom_fields)) {
        foreach($custom_fields as $custom_field_id => $value) {
          $subscriber->setCustomField($custom_field_id, $value);
        }
      }

      // check for status change
      if(
          ($old_status === self::STATUS_SUBSCRIBED)
          &&
          ($new_status === self::STATUS_UNSUBSCRIBED)
      ) {
        // make sure we unsubscribe the user from all segments
        SubscriberSegment::removeSubscriptions($subscriber);
      } else {
        if($segment_ids !== false) {
          SubscriberSegment::setSubscriptions($subscriber, $segment_ids);
        }
      }
    }
    return $subscriber;
  }

  function withCustomFields() {
    $custom_fields = CustomField::select('id')->findArray();
    if(empty($custom_fields)) return $this;

    $custom_field_ids = Helpers::arrayColumn($custom_fields, 'id');
    $relations = SubscriberCustomField::select('custom_field_id')
      ->select('value')
      ->whereIn('custom_field_id', $custom_field_ids)
      ->where('subscriber_id', $this->id())
      ->findMany();
    foreach($relations as $relation) {
      $this->{'cf_'.$relation->custom_field_id} = $relation->value;
    }

    return $this;
  }

  function withSegments() {
    $this->segments = $this->segments()->findArray();
    return $this;
  }

  function withSubscriptions() {
    $this->subscriptions = SubscriberSegment::where('subscriber_id', $this->id())
      ->findArray();
    return $this;
  }

  function getCustomField($custom_field_id, $default = null) {
    $custom_field = SubscriberCustomField::select('value')
      ->where('custom_field_id', $custom_field_id)
      ->where('subscriber_id', $this->id())
      ->findOne();

    if($custom_field === false) {
      return $default;
    } else {
      return $custom_field->value;
    }
  }

  function setCustomField($custom_field_id, $value) {
    return SubscriberCustomField::createOrUpdate(array(
      'subscriber_id' => $this->id(),
      'custom_field_id' => $custom_field_id,
      'value' => $value
    ));
  }

  static function bulkMoveToList($orm, $data = array()) {
    $segment_id = (isset($data['segment_id']) ? (int)$data['segment_id'] : 0);
    $segment = Segment::findOne($segment_id);
    if($segment !== false) {
      $subscribers = $orm->findResultSet();
      foreach($subscribers as $subscriber) {
        // remove subscriber from all segments
        SubscriberSegment::where('subscriber_id', $subscriber->id)->deleteMany();

        // create relation with segment
        $association = SubscriberSegment::create();
        $association->subscriber_id = $subscriber->id;
        $association->segment_id = $segment->id;
        $association->save();
      }
      return array(
        'subscribers' => $subscribers->count(),
        'segment' => $segment->name
      );
    }
    return false;
  }

  static function bulkRemoveFromList($orm, $data = array()) {
    $segment_id = (isset($data['segment_id']) ? (int)$data['segment_id'] : 0);
    $segment = Segment::findOne($segment_id);

    if($segment !== false) {
      // delete relations with segment
      $subscribers = $orm->findResultSet();
      foreach($subscribers as $subscriber) {
        SubscriberSegment::where('subscriber_id', $subscriber->id)
          ->where('segment_id', $segment->id)
          ->deleteMany();
      }

      return array(
        'subscribers' => $subscribers->count(),
        'segment' => $segment->name
      );
    }
    return false;
  }

  static function bulkRemoveFromAllLists($orm) {
    $subscribers = $orm->findResultSet();
    $subscribers_count = 0;
    foreach($subscribers as $subscriber) {
      try {
        SubscriberSegment::removeSubscriptions($subscriber);
        $subscribers_count++;
      } catch(Exception $e) {
        continue;
      }
    }

    return $subscribers_count;
  }

  static function bulkSendConfirmationEmail($orm) {
    $subscribers = $orm
      ->where('status', self::STATUS_UNCONFIRMED)
      ->findMany();

    $emails_sent = 0;
    if(!empty($subscribers)) {
      foreach($subscribers as $subscriber) {
        if($subscriber->sendConfirmationEmail()) {
          $emails_sent++;
        }
      }
      return $emails_sent;
    }
    return false;
  }

  static function bulkAddToList($orm, $data = array()) {
    $segment_id = (isset($data['segment_id']) ? (int)$data['segment_id'] : 0);
    $segment = Segment::findOne($segment_id);

    if($segment !== false) {
      $subscribers_count = 0;
      $subscribers = $orm->findMany();
      foreach($subscribers as $subscriber) {
        try {
          SubscriberSegment::addSubscriptions($subscriber, array($segment->id));
          $subscribers_count++;
        } catch(Exception $e) {
          continue;
        }
      }
      return array(
        'subscribers' => $subscribers_count,
        'segment' => $segment->name
      );
    }
    return false;
  }

  static function bulkTrash($orm) {
    return parent::bulkAction($orm, function($ids) {
       parent::rawExecute(join(' ', array(
          'UPDATE `'.self::$_table.'`',
          'SET `deleted_at`=NOW()',
          'WHERE `id` IN ('.rtrim(str_repeat('?,', count($ids)), ',').')',
          'AND `wp_user_id` IS NULL'
        )),
        $ids
      );
    });
  }

  static function bulkDelete($orm) {
    $wp_users_segment = Segment::getWPUsers();

    return parent::bulkAction($orm, function($ids) use ($wp_users_segment) {
      // delete subscribers' relations to segments (except WP Users' segment)
      SubscriberSegment::whereIn('subscriber_id', $ids)
        ->whereNotEqual('segment_id', $wp_users_segment->id)
        ->deleteMany();

      // delete subscribers (except WP Users)
      Subscriber::whereIn('id', $ids)
        ->whereNull('wp_user_id')
        ->deleteMany();
    });
  }

  static function subscribed($orm) {
    return $orm
      ->whereNull('deleted_at')
      ->where('status', self::STATUS_SUBSCRIBED);
  }

  static function unsubscribed($orm) {
    return $orm
      ->whereNull('deleted_at')
      ->where('status', self::STATUS_UNSUBSCRIBED);
  }

  static function unconfirmed($orm) {
    return $orm
      ->whereNull('deleted_at')
      ->where('status', self::STATUS_UNCONFIRMED);
  }

  static function withoutSegments($orm) {
    return $orm->select(MP_SUBSCRIBERS_TABLE.'.*')
      ->leftOuterJoin(
        MP_SUBSCRIBER_SEGMENT_TABLE,
        array(
          MP_SUBSCRIBERS_TABLE.'.id',
          '=',
          MP_SUBSCRIBER_SEGMENT_TABLE.'.subscriber_id'
        )
      )
      ->whereNull(MP_SUBSCRIBER_SEGMENT_TABLE.'.subscriber_id');
  }

  static function createMultiple($columns, $values) {
    return self::rawExecute(
      'INSERT INTO `' . self::$_table . '` ' .
      '(' . implode(', ', $columns) . ') ' .
      'VALUES ' . rtrim(
        str_repeat(
          '(' . rtrim(str_repeat('?,', count($columns)), ',') . ')' . ', '
          , count($values)
        )
        , ', '),
      Helpers::flattenArray($values)
    );
  }

  static function updateMultiple($columns, $subscribers, $updated_at = false) {
    $ignoreColumnsOnUpdate = array(
      'email',
      'created_at'
    );
    $subscribers = array_map('array_values', $subscribers);
    $emailPosition = array_search('email', $columns);
    $sql =
      function ($type) use (
        $columns,
        $subscribers,
        $emailPosition,
        $ignoreColumnsOnUpdate
      ) {
        return array_filter(
          array_map(function ($columnPosition, $columnName) use (
            $type,
            $subscribers,
            $emailPosition,
            $ignoreColumnsOnUpdate
          ) {
            if(in_array($columnName, $ignoreColumnsOnUpdate)) return;
            $query = array_map(
              function ($subscriber) use ($type, $columnPosition, $emailPosition) {
                return ($type === 'values') ?
                  array(
                    $subscriber[$emailPosition],
                    $subscriber[$columnPosition]
                  ) :
                  'WHEN email = ? THEN ?';
              }, $subscribers);
            return ($type === 'values') ?
              Helpers::flattenArray($query) :
              $columnName . '= (CASE ' . implode(' ', $query) . ' END)';
          }, array_keys($columns), $columns)
        );
      };
    return self::rawExecute(
      'UPDATE `' . self::$_table . '` ' .
      'SET ' . implode(', ', $sql('statement')) . ' '.
      (($updated_at) ? ', updated_at = "' . $updated_at . '" ' : '') .
        'WHERE email IN ' .
        '(' . rtrim(str_repeat('?,', count($subscribers)), ',') . ')',
      array_merge(
        Helpers::flattenArray($sql('values')),
        Helpers::arrayColumn($subscribers, $emailPosition)
      )
    );
  }
}