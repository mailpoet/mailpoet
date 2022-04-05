<?php declare(strict_types=1);

namespace MailPoet\Mailer\Methods;

interface MailerMethod {
  public function send($newsletter, $subscriber, $extraParams = []): array;
}
