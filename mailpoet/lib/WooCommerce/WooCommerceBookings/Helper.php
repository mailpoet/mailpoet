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
      // WordPress falls back the label to the status key when a post status registers
      // label => false (as Bookings does for was-in-cart), so humanize it in that case.
      $label = $object->label ?? '';
      if (!is_string($label) || $label === '' || $label === $cartStatus) {
        $label = ucwords(str_replace('-', ' ', $cartStatus));
      }
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

  /**
   * Counts a customer's bookings created within the last $seconds, excluding one booking id
   * (typically the booking that triggered the automation) and cancelled/in-cart bookings.
   *
   * Used to detect whether a customer has booked again, e.g. before sending a rebooking nudge.
   */
  public function countRecentCustomerBookings(int $customerId, int $seconds, int $excludeBookingId = 0): int {
    if ($customerId <= 0 || $seconds <= 0 || !class_exists(\WC_Booking_Data_Store::class)) {
      return 0;
    }

    $threshold = time() - $seconds;
    $ignoredStatuses = ['cancelled', 'was-in-cart', 'in-cart'];
    $count = 0;
    foreach (\WC_Booking_Data_Store::get_bookings_for_user($customerId) as $booking) {
      if (!$booking instanceof \WC_Booking || $booking->get_id() === $excludeBookingId) {
        continue;
      }
      if (in_array($booking->get_status(), $ignoredStatuses, true)) {
        continue;
      }
      $created = $booking->get_date_created();
      if ($created && $created->getTimestamp() >= $threshold) {
        $count++;
      }
    }

    return $count;
  }
}
