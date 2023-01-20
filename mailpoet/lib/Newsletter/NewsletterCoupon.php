<?php declare(strict_types = 1);

namespace MailPoet\Newsletter;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\Renderer\Blocks\Coupon;

class NewsletterCoupon {
  public function cleanupSensitiveData(NewsletterEntity $newsletter): NewsletterEntity {
    $body = $newsletter->getBody();
    if (!is_array($body) || empty($body['content'])) {
      return $newsletter;
    }
    $cleanBlocks = $this->cleanupCouponBlocks($body['content']['blocks']);
    $updatedBody = array_merge(
      $body,
      [
        'content' => array_merge(
          $body['content'],
          ['blocks' => $cleanBlocks]
        ),
      ]
    );

    $newsletter->setBody($updatedBody);
    return $newsletter;
  }

  private function cleanupCouponBlocks(array &$blocks): array {
    foreach ($blocks as &$block) {
      if (isset($block['blocks']) && !empty($block['blocks'])) {
        $this->cleanupCouponBlocks($block['blocks']);
      }

      if (isset($block['type']) && $block['type'] === Coupon::TYPE) {
        if (isset($block['code']))
          unset($block['code']);

        if(isset($block['couponId']))
          unset($block['couponId']);
      }
    }
    return $blocks;
  }
}
