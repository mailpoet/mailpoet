<?php declare(strict_types = 1);
// phpcs:ignoreFile - We want to allow multiple classes etc.

// PHPMailer ships inside WordPress core, which the unit suite does not load. With all three
// classes present, PHPMailerLoader::load() has nothing to require from WordPress.
if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
  require_once __DIR__ . '/_stubs/PHPMailer.php';
}

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
