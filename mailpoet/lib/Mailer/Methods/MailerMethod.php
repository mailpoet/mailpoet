<?php

namespace MailPoet\Mailer\Methods;

interface MailerMethod {
  public function send($newsletter, $subscriber, $extraParams = []): array;
}
