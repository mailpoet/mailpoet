<?php declare(strict_types = 1);

namespace MailPoet\Mailer\Methods\Common;

/**
 * Reads the additional To, CC and BCC recipients of a WordPress email from a mailer's extra params.
 */
class ExtraRecipients {
  public const FIELDS = ['to', 'cc', 'bcc'];

  /**
   * @param array<string, mixed> $extraParams
   * @return array{to: array<int, array{address: string, name: string}>, cc: array<int, array{address: string, name: string}>, bcc: array<int, array{address: string, name: string}>}
   */
  public static function fromExtraParams(array $extraParams): array {
    $recipients = is_array($extraParams['recipients'] ?? null) ? $extraParams['recipients'] : [];
    $result = ['to' => [], 'cc' => [], 'bcc' => []];
    foreach (self::FIELDS as $field) {
      if (!is_array($recipients[$field] ?? null)) {
        continue;
      }
      foreach ($recipients[$field] as $recipient) {
        if (!is_array($recipient) || !is_string($recipient['address'] ?? null) || $recipient['address'] === '') {
          continue;
        }
        $result[$field][] = [
          'address' => $recipient['address'],
          'name' => is_string($recipient['name'] ?? null) ? $recipient['name'] : '',
        ];
      }
    }
    return $result;
  }

  /**
   * @param array<string, mixed> $extraParams
   * @return string[] Addresses in To, CC, BCC order.
   */
  public static function getAddresses(array $extraParams): array {
    $addresses = [];
    foreach (self::fromExtraParams($extraParams) as $recipients) {
      foreach ($recipients as $recipient) {
        $addresses[] = $recipient['address'];
      }
    }
    return $addresses;
  }

  /**
   * @param array<string, mixed> $extraParams
   */
  public static function findBlacklisted(BlacklistCheck $blacklist, array $extraParams): ?string {
    foreach (self::getAddresses($extraParams) as $address) {
      if ($blacklist->isBlacklisted($address)) {
        return $address;
      }
    }
    return null;
  }
}
