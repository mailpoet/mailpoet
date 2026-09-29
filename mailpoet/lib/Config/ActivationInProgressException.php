<?php declare(strict_types = 1);

namespace MailPoet\Config;

use MailPoet\InvalidStateException;

/**
 * Thrown by Activator when another request holds the activation lock. Initializer
 * treats only this exception as "try again shortly"; any other failure during
 * activation counts as a failed update.
 */
class ActivationInProgressException extends InvalidStateException {
}
