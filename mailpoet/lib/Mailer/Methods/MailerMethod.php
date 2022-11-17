<?php declare(strict_types = 1);

namespace MailPoet\Mailer\Methods;

interface MailerMethod {
  public function send(array $newsletter, array $subscriber, array $extraParams = []): array;
}
