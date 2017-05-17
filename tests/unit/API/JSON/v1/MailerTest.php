<?php

use MailPoet\API\JSON\v1\Mailer;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\Mailer\MailerLog;

class MailerEndpointTest extends MailPoetTest {
  function testItResumesSending() {
    // create mailer log with a "paused" status
    $mailer_log = array('status' => MailerLog::STATUS_PAUSED);
    MailerLog::updateMailerLog($mailer_log);
    $mailer_log = MailerLog::getMailerLog();
    expect($mailer_log['status'])->equals(MailerLog::STATUS_PAUSED);
    // resumeSending() method should clear the mailer log's status
    $mailer_endpoint = new Mailer();
    $response = $mailer_endpoint->resumeSending();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    $mailer_log = MailerLog::getMailerLog();
    expect($mailer_log['status'])->null();
  }
}