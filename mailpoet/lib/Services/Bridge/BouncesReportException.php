<?php declare(strict_types = 1);

namespace MailPoet\Services\Bridge;

use MailPoet\RuntimeException;

/**
 * Thrown when a bounces report request does not yield a usable report. The code
 * carries the HTTP status the endpoint responded with, or 0 when there was no
 * response to read a status from (connection error, malformed payload), so
 * callers can tell a permanent rejection from a transient failure.
 */
class BouncesReportException extends RuntimeException {
}
