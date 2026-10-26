<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce\WooCommerceBookings;

use MailPoet\WP\Functions;

class HelperTest extends \MailPoetUnitTest {
  protected function _after() {
    parent::_after();
    $this->setBookings([]);
  }

  public function testItCountsOnlyBookingsCreatedInsideTheWindow(): void {
    $now = time();
    $this->setBookings([
      $this->createBooking(1, 'paid', $now - 60),
      $this->createBooking(2, 'confirmed', $now - 7200),
      $this->createBooking(3, 'paid', 0),
    ]);

    verify($this->createHelper()->countRecentCustomerBookings(10, 3600))->equals(1);
  }

  public function testItSkipsIgnoredStatusesAndTheExcludedBooking(): void {
    $now = time();
    $this->setBookings([
      $this->createBooking(1, 'cancelled', $now - 60),
      $this->createBooking(2, 'in-cart', $now - 60),
      $this->createBooking(3, 'was-in-cart', $now - 60),
      $this->createBooking(4, 'paid', $now - 60),
      $this->createBooking(5, 'paid', $now - 60),
    ]);

    verify($this->createHelper()->countRecentCustomerBookings(10, 3600, 4))->equals(1);
  }

  private function createHelper(): Helper {
    return new Helper($this->createMock(Functions::class));
  }

  /** WC_Booking::get_date_created() returns an int Unix timestamp. */
  private function createBooking(int $id, string $status, int $dateCreated): \WC_Booking {
    $booking = $this->createMock(\WC_Booking::class);
    $booking->method('get_id')->willReturn($id);
    $booking->method('get_status')->willReturn($status);
    $booking->method('get_date_created')->willReturn($dateCreated);
    return $booking;
  }

  /** @param \WC_Booking[] $bookings */
  private function setBookings(array $bookings): void {
    $store = (string)\WC_Booking_Data_Store::class;
    $store::$testBookings = $bookings;
  }
}
