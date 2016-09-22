<?php
namespace MailPoet\Subscription;
use \MailPoet\Models\Subscriber;
use \MailPoet\Models\StatisticsForms;
use \MailPoet\Models\Form as FormModel;
use \MailPoet\Util\Url;

class Form {
  static function onSubmit() {
    $reserved_keywords = array(
      'token',
      'endpoint',
      'method',
      'mailpoet_redirect'
    );
    $data = array_diff_key($_POST, array_flip($reserved_keywords));

    $form_id = (isset($data['form_id']) ? (int)$data['form_id'] : false);
    $form = FormModel::findOne($form_id);
    unset($data['form_id']);

    $segment_ids = (!empty($data['segments'])
      ? (array)$data['segments']
      : array()
    );
    unset($data['segments']);

    if(empty($segment_ids)) {
      Url::redirectBack(array(
        'mailpoet_error' => $form_id,
        'mailpoet_success' => null
      ));
    }

    $subscriber = Subscriber::subscribe($data, $segment_ids);
    $errors = $subscriber->getErrors();
     if($errors !== false) {
      Url::redirectBack(array(
        'mailpoet_error' => $form_id,
        'mailpoet_success' => null
      ));
    } else {
      $meta = array();

      if($form !== false) {
        // record form statistics
        StatisticsForms::record($form->id, $subscriber->id);

        $form = $form->asArray();

        if($form['settings']['on_success'] === 'page') {
          // redirect to a page on a success, pass the page url in the meta
          $meta['redirect_url'] = get_permalink($form['settings']['success_page']);
        } else if($form['settings']['on_success'] === 'url') {
          $meta['redirect_url'] = $form['settings']['success_url'];
        }
      }

      if(isset($meta['redirect_url'])) {
        Url::redirectTo($meta['redirect_url']);
      } else {
        Url::redirectBack(array(
          'mailpoet_success' => $form['id'],
          'mailpoet_error' => null
        ));
      }
    }
  }
}