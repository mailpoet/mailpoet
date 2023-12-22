<?php declare(strict_types = 1);
// phpcs:ignoreFile - We want to allow multiple classes etc.

// Dummy WP classes
if (!class_exists(\WP_Theme_JSON::class)) {
  class WP_Theme_JSON {
    public function get_data() {
      return [];
    }
  }
}
