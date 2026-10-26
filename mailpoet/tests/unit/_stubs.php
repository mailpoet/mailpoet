<?php declare(strict_types = 1);
// phpcs:ignoreFile - We want to allow multiple classes etc.

// Dummy WP classes
if (!class_exists(\WP_Theme_JSON::class)) {
  class WP_Theme_JSON {
    public function get_data() {
      return [];
    }
    public function get_settings() {
      return [];
    }
  }
}

// Dummy WooCommerce Bookings classes.
if (!class_exists(\WC_Booking::class)) {
  class WC_Booking {
    public function get_id() {
      return 0;
    }

    public function get_status() {
      return '';
    }

    public function get_date_created() {
      return 0;
    }
  }
}

if (!class_exists(\WC_Booking_Data_Store::class)) {
  class WC_Booking_Data_Store {
    public static $testBookings = [];

    public static function get_bookings_for_user($customerId) {
      return self::$testBookings;
    }
  }
}
