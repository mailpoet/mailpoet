import { useState } from 'react';
import { __, _n, sprintf } from '@wordpress/i18n';
import { SelectControl } from '@woocommerce/components';
import { Button, Modal } from '@wordpress/components';
import { dispatch, select } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { MailPoet } from '../../../mailpoet';
import { DynamicSegment } from '../types';
import { storeName } from '../store';

async function bulkAction(action: string, segments: DynamicSegment[]) {
  let successMessage = '';
  let errorMessage = '';
  switch (action) {
    case 'trash':
      successMessage = sprintf(
        /* translators: %d - number of segments */
        _n(
          'Segment moved to trash.',
          '%d segments moved to trash.',
          segments.length,
          'mailpoet',
        ),
        segments.length,
      );
      errorMessage = __('Error moving segment to trash.', 'mailpoet');
      break;
    case 'delete':
      successMessage = sprintf(
        /* translators: %d - number of segments */
        _n(
          'Segment permanently deleted.',
          '%d segments permanently deleted.',
          segments.length,
          'mailpoet',
        ),
        segments.length,
      );
      errorMessage = __('Error deleting segment.', 'mailpoet');
      break;
    case 'restore':
      successMessage = sprintf(
        /* translators: %d - number of segments */
        _n(
          'Segment restored.',
          '%d segments restored.',
          segments.length,
          'mailpoet',
        ),
        segments.length,
      );
      errorMessage = __('Error restoring segment.', 'mailpoet');
      break;
    default:
      break;
  }
  void MailPoet.Ajax.post({
    api_version: 'v1',
    endpoint: 'dynamic_segments',
    action: 'bulk_action',
    data: {
      action,
      listing: {
        selection: segments.map((segment) => segment.id),
      },
    },
  })
    .then(() => {
      void dispatch(noticesStore).createSuccessNotice(successMessage);
      void dispatch(storeName).loadDynamicSegments();
    })
    .fail(() => {
      void dispatch(noticesStore).createErrorNotice(errorMessage, {
        explicitDismiss: true,
      });
    });
}

type BulkActionsProps = {
  tab: {
    name: string;
  };
};
export function BulkActions({ tab }: BulkActionsProps): JSX.Element {
  const [currentAction, setCurrentAction] = useState<string>('');
  const dynamicSegments = select(storeName).getDynamicSegments();
  const allSelected = dynamicSegments
    ? dynamicSegments.filter((segment) => segment.selected)
    : [];
  const bulkActions =
    tab.name !== 'trash'
      ? [
          {
            label: __('Trash', 'mailpoet'),
            value: 'trash',
          },
        ]
      : [
          {
            label: __('Restore', 'mailpoet'),
            value: 'restore',
          },
          {
            label: __('Delete permanently', 'mailpoet'),
            value: 'delete',
          },
        ];

  let title = '';
  let message: string | JSX.Element = '';
  let confirmText = '';
  const lastSelected =
    allSelected.length > 0
      ? `"${allSelected[allSelected.length - 1].name}"`
      : '';
  const firstSelected =
    allSelected.length > 1
      ? allSelected
          .slice(0, -1)
          .map((segment) => `"${segment.name}"`)
          .join(', ')
      : lastSelected;

  switch (currentAction) {
    case 'trash':
      title = _n(
        'Trash selected segment',
        'Trash selected segments',
        allSelected.length,
        'mailpoet',
      );
      message = sprintf(
        _n(
          'Are you sure you want to trash the selected segment %s?',
          'Are you sure you want to trash the selected segments %s and %s?',
          allSelected.length,
          'mailpoet',
        ),
        firstSelected,
        lastSelected,
      );
      confirmText = __('Trash', 'mailpoet');
      break;
    case 'restore':
      title = _n(
        'Restore selected segment',
        'Restore selected segments',
        allSelected.length,
        'mailpoet',
      );
      message = sprintf(
        _n(
          'Are you sure you want to restore the selected segment %s?',
          'Are you sure you want to restore segments %s and %s?',
          allSelected.length,
          'mailpoet',
        ),
        firstSelected,
        lastSelected,
      );
      confirmText = __('Restore', 'mailpoet');
      break;
    case 'delete':
      title = _n(
        'Delete selected segment permanently',
        'Delete selected segments permanently',
        allSelected.length,
        'mailpoet',
      );
      message = (
        <>
          {sprintf(
            _n(
              'Are you sure you want to delete the selected segment %s permanently?',
              'Are you sure you want to delete the selected segments %s and %s permanently?',
              allSelected.length,
              'mailpoet',
            ),
            firstSelected,
            lastSelected,
          )}{' '}
          <strong>{__('This action can not be reversed.', 'mailpoet')}</strong>
        </>
      );
      confirmText = __('Delete permanently', 'mailpoet');
      break;
    default:
      break;
  }

  return (
    <>
      {currentAction !== '' && (
        <Modal
          title={title}
          onRequestClose={() => {
            setCurrentAction('');
          }}
        >
          <p>{message}</p>
          <Button
            variant="primary"
            onClick={() => {
              void bulkAction(currentAction, allSelected);
              setCurrentAction('');
            }}
          >
            {confirmText}
          </Button>
          <Button
            variant="tertiary"
            onClick={() => {
              setCurrentAction('');
            }}
          >
            {__('Cancel', 'mailpoet')}
          </Button>
        </Modal>
      )}
      <SelectControl
        multiple={false}
        className="mailpoet-segments-listing-group"
        label={__('Bulk Actions', 'mailpoet')}
        value={tab.name}
        options={bulkActions}
        onChange={(value) => {
          if (allSelected.length === 0) {
            return;
          }

          const action = value[0]?.value as string;
          if (!action) {
            return;
          }

          setCurrentAction(action);
        }}
      />
    </>
  );
}
