# Type: Changed

# Description

Deleting a WordPress user no longer deletes the linked MailPoet subscriber; the subscriber is unlinked from the WP user and kept on any other lists (use the mailpoet_delete_subscriber_on_wp_user_delete filter to restore the previous hard-delete behavior)
