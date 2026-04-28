<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

class RandomCouponCodeGenerator {
  public function generate(): string {
    return implode('-', [
      $this->generateSegment(4),
      $this->generateSegment(6),
      $this->generateSegment(4),
    ]);
  }

  private function generateSegment(int $length): string {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $maxIndex = strlen($characters) - 1;
    $segment = '';

    for ($i = 0; $i < $length; $i++) {
      $segment .= $characters[random_int(0, $maxIndex)];
    }

    return $segment;
  }
}
