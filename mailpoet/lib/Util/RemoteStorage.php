<?php declare(strict_types = 1);

namespace MailPoet\Util;

class RemoteStorage {
  /**
   * True when the path goes through a PHP stream wrapper other than file://,
   * such as vip:// or s3://, so it is not a plain path on the local disk.
   */
  public static function isRemotePath(string $path): bool {
    $scheme = strstr($path, '://', true);
    if ($scheme === false || $scheme === 'file') {
      return false;
    }
    return in_array($scheme, stream_get_wrappers(), true);
  }
}
