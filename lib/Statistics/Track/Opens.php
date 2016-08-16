<?php
namespace MailPoet\Statistics\Track;

use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\Subscriber;

if(!defined('ABSPATH')) exit;

class Opens {
  public $data;
  public $return_image;

  function __construct($data, $return_image = true) {
    $this->data = $data;
    $this->return_image = $return_image;
  }

  function track($data = false) {
    $data = ($data) ? $data : $this->data;
    $newsletter = $this->getNewsletter($data['newsletter']);
    $queue = $this->getQueue($data['queue']);
    // verify if queue belongs to the newsletter
    if($newsletter && $queue) {
      $queue = ($queue['newsletter_id'] === $newsletter['id']) ?
        $queue :
        false;
    }
    $subscriber = $this->getSubscriber($data['subscriber']);
    // verify if subscriber belongs to the queue
    if($queue && $subscriber) {
      if(empty($queue['']))
      $subscriber = (in_array($subscriber['id'], $queue['subscribers']['processed'])) ?
        $subscriber :
        false;
    }
    if(!$subscriber || !$newsletter || !$queue) {
      return false;
    }
    $statistics = StatisticsOpens::where('subscriber_id', $data['subscriber'])
      ->where('newsletter_id', $data['newsletter'])
      ->where('queue_id', $data['queue'])
      ->findOne();
    if(!$statistics) {
      $statistics = StatisticsOpens::create();
      $statistics->newsletter_id = $data['newsletter'];
      $statistics->subscriber_id = $data['subscriber'];
      $statistics->queue_id = $data['queue'];
      $statistics->save();
    }
    if($this->return_image) {
      $this->returnImage();
    }
    return true;
  }

  function getNewsletter($newsletter_id) {
    $newsletter = Newsletter::findOne($newsletter_id);
    return ($newsletter) ? $newsletter->asArray() : $newsletter;
  }

  function getSubscriber($subscriber_id) {
    $subscriber = Subscriber::findOne($subscriber_id);
    return ($subscriber) ? $subscriber->asArray() : $subscriber;
  }

  function getQueue($queue_id) {
    $queue = SendingQueue::findOne($queue_id);
    return ($queue) ? $queue->asArray() : $queue;
  }

  function returnImage() {
    // return 1x1 pixel transparent gif image
    header('Content-Type: image/gif');
    echo "\x47\x49\x46\x38\x37\x61\x1\x0\x1\x0\x80\x0\x0\xfc\x6a\x6c\x0\x0\x0\x2c\x0\x0\x0\x0\x1\x0\x1\x0\x0\x2\x2\x44\x1\x0\x3b";
    exit;
  }
}