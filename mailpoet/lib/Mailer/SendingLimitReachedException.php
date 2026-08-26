<?php declare(strict_types = 1);

namespace MailPoet\Mailer;

use MailPoet\RuntimeException;

/**
 * Thrown when the configured sending frequency limit is reached. An expected
 * sending state, not an error — sending resumes once the interval passes.
 */
class SendingLimitReachedException extends RuntimeException {
}
