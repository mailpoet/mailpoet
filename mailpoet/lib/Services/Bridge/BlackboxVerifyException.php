<?php declare(strict_types = 1);

namespace MailPoet\Services\Bridge;

use MailPoet\RuntimeException;

/**
 * Thrown when a Blackbox verify request does not yield a usable verdict. The code
 * carries the HTTP status the endpoint responded with, or 0 when there was no
 * response to read a status from (connection error, malformed payload). Callers
 * should treat any instance of this exception as "no signal" and fail open.
 */
class BlackboxVerifyException extends RuntimeException {
}
