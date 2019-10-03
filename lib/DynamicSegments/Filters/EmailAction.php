<?php

namespace MailPoet\DynamicSegments\Filters;

use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\StatisticsNewsletters;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\Subscriber;

class EmailAction implements Filter {

  const SEGMENT_TYPE = 'email';

  const ACTION_OPENED = 'opened';
  const ACTION_NOT_OPENED = 'notOpened';
  const ACTION_CLICKED = 'clicked';
  const ACTION_NOT_CLICKED = 'notClicked';

  private static $allowed_actions = [
    EmailAction::ACTION_OPENED,
    EmailAction::ACTION_NOT_OPENED,
    EmailAction::ACTION_CLICKED,
    EmailAction::ACTION_NOT_CLICKED,
  ];

  /** @var int */
  private $newsletter_id;

  /** @var int */
  private $link_id;

  /** @var string */
  private $action;

  /** @var string */
  private $connect;

  /**
   * @param int $newsletter_id
   * @param int $link_id
   * @param string $action
   * @param string $connect
   */
  public function __construct($action, $newsletter_id, $link_id = null, $connect = null) {
    $this->newsletter_id = (int)$newsletter_id;
    if ($link_id) {
      $this->link_id = (int)$link_id;
    }
    $this->setAction($action);
    $this->connect = $connect;
  }

  private function setAction($action) {
    if (!in_array($action, EmailAction::$allowed_actions)) {
      throw new \InvalidArgumentException("Unknown action " . $action);
    }
    $this->action = $action;
  }

  function toSql(\ORM $orm) {
    if (($this->action === EmailAction::ACTION_CLICKED) || ($this->action === EmailAction::ACTION_NOT_CLICKED)) {
      $table = StatisticsClicks::$_table;
    } else {
      $table = StatisticsOpens::$_table;
    }

    if (($this->action === EmailAction::ACTION_NOT_CLICKED) || ($this->action === EmailAction::ACTION_NOT_OPENED)) {
      $orm->rawJoin(
        'INNER JOIN ' . StatisticsNewsletters::$_table,
        'statssent.subscriber_id = ' . Subscriber::$_table . '.id AND statssent.newsletter_id = ' . $this->newsletter_id,
        'statssent'
      );
      $orm->rawJoin(
        'LEFT JOIN ' . $table,
        $this->createNotStatsJoin(),
        'stats'
      );
      $orm->whereNull('stats.id');
    } else {
      $orm->rawJoin(
        'INNER JOIN ' . $table,
        'stats.subscriber_id = ' . Subscriber::$_table . '.id AND stats.newsletter_id = ' . $this->newsletter_id,
        'stats'
      );
    }
    if (($this->action === EmailAction::ACTION_CLICKED) && ($this->link_id)) {
      $orm->where('stats.link_id', $this->link_id);
    }
    return $orm;
  }

  private function createNotStatsJoin() {
    $clause = 'statssent.subscriber_id = stats.subscriber_id AND stats.newsletter_id = ' . $this->newsletter_id;
    if (($this->action === EmailAction::ACTION_NOT_CLICKED) && ($this->link_id)) {
      $clause .= ' AND stats.link_id = ' . $this->link_id;
    }
    return $clause;
  }

  function toArray() {
    return [
      'action' => $this->action,
      'newsletter_id' => $this->newsletter_id,
      'link_id' => $this->link_id,
      'connect' => $this->connect,
      'segmentType' => EmailAction::SEGMENT_TYPE,
    ];
  }
}
