<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce\WooCommerceBookings;

use MailPoet\WP\Functions;

class Helper {

  private Functions $wp;

  public function __construct(
    Functions $wp
  ) {
    $this->wp = $wp;
  }

  public function isWooCommerceBookingsActive(): bool {
    return $this->wp->isPluginActive('woocommerce-bookings/woocommerce-bookings.php');
  }

  /**
   * Returns all booking statuses keyed by status with their labels.
   *
   * WooCommerce Bookings splits its statuses across several "contexts" and none of them
   * exposes every status, so we merge them. We also add the cart statuses, which Bookings
   * registers as post statuses but leaves out of the contexts above (in particular the
   * internal "was-in-cart" status that the abandoned booking automation relies on).
   *
   * @return array<string, string>
   */
  public function getBookingStatuses(): array {
    if (!function_exists('get_wc_booking_statuses')) {
      return [];
    }

    $statuses = [];
    foreach (['fully_booked', 'user', 'cancel', 'scheduled'] as $context) {
      foreach (get_wc_booking_statuses($context, true) as $status => $label) {
        $statuses[$status] = $label;
      }
    }

    foreach (['in-cart', 'was-in-cart'] as $cartStatus) {
      if (isset($statuses[$cartStatus])) {
        continue;
      }
      $object = $this->wp->getPostStatusObject($cartStatus);
      if (!$object) {
        continue;
      }
      $label = is_string($object->label ?? null) && $object->label !== '' ? $object->label : ucwords(str_replace('-', ' ', $cartStatus));
      $statuses[$cartStatus] = $label;
    }

    return $statuses;
  }

  /**
   * @param int $id
   * @return false|\WC_Booking
   */
  public function getBooking(int $id) {
    if (!function_exists('get_wc_booking')) {
      return false;
    }

    return get_wc_booking($id);
  }
}
