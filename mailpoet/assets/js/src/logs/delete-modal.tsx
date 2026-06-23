import { useCallback, useState } from 'react';
import { Modal, Notice } from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';

import { Button } from 'common';
import { deleteLogs } from './api';
import type { LogsFilter } from './url-state';

type Props = {
  count: number;
  filter: LogsFilter;
  search: string | undefined;
  isUnrestricted: boolean;
  onClose: () => void;
  onDeleted: (deleted: number) => void;
};

function getErrorMessage(error: unknown): string {
  if (
    typeof error === 'object' &&
    error !== null &&
    'message' in error &&
    typeof error.message === 'string'
  ) {
    return error.message;
  }
  return __('Something went wrong. Please try again.', 'mailpoet');
}

export function DeleteLogsModal({
  count,
  filter,
  search,
  isUnrestricted,
  onClose,
  onDeleted,
}: Props): JSX.Element {
  const [isDeleting, setIsDeleting] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  const formattedCount = count.toLocaleString();
  const message = isUnrestricted
    ? sprintf(
        _n(
          'This permanently deletes the only log. This cannot be undone.',
          'This permanently deletes all %s logs. This cannot be undone.',
          count,
          'mailpoet',
        ),
        formattedCount,
      )
    : sprintf(
        _n(
          'This permanently deletes %s log matching the current filters. This cannot be undone.',
          'This permanently deletes %s logs matching the current filters. This cannot be undone.',
          count,
          'mailpoet',
        ),
        formattedCount,
      );

  const handleDelete = useCallback(async (): Promise<void> => {
    setIsDeleting(true);
    setErrorMessage(null);
    try {
      const deleted = await deleteLogs(filter, search, isUnrestricted);
      onDeleted(deleted);
      onClose();
    } catch (error) {
      setErrorMessage(getErrorMessage(error));
      setIsDeleting(false);
    }
  }, [filter, search, isUnrestricted, onClose, onDeleted]);

  return (
    <Modal
      className="mailpoet-logs-delete-modal"
      title={__('Delete logs', 'mailpoet')}
      onRequestClose={onClose}
      isDismissible={!isDeleting}
    >
      {errorMessage && (
        <Notice status="error" isDismissible={false}>
          {errorMessage}
        </Notice>
      )}

      <p>{message}</p>

      <div className="mailpoet-logs-delete-modal__actions">
        <Button
          dimension="small"
          variant="secondary"
          onClick={onClose}
          isDisabled={isDeleting}
        >
          {__('Cancel', 'mailpoet')}
        </Button>
        <Button
          dimension="small"
          variant="destructive"
          onClick={handleDelete}
          isDisabled={isDeleting}
          withSpinner={isDeleting}
        >
          {sprintf(
            _n('Delete %s log', 'Delete %s logs', count, 'mailpoet'),
            formattedCount,
          )}
        </Button>
      </div>
    </Modal>
  );
}
