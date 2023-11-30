import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';
import { select } from '@wordpress/data';
import { storeName } from '../store';
import { DynamicSegment, DynamicSegmentAction } from '../types';

type BulkActionsProps = {
  tab: {
    name: string;
  };
  onClick: (selected: DynamicSegment[], action: DynamicSegmentAction) => void;
};
export function BulkActions({ tab, onClick }: BulkActionsProps): JSX.Element {
  const dynamicSegments = select(storeName).getDynamicSegments();
  const allSelected = dynamicSegments
    ? dynamicSegments.filter((segment) => segment.selected)
    : [];
  const bulkActions =
    tab.name !== 'trash'
      ? [
          {
            value: '0',
            label: __('Bulk Actions', 'mailpoet'),
          },
          {
            label: __('Trash', 'mailpoet'),
            value: 'trash',
          },
        ]
      : [
          {
            value: '0',
            label: __('Bulk Actions', 'mailpoet'),
          },
          {
            label: __('Restore', 'mailpoet'),
            value: 'restore',
          },
          {
            label: __('Delete permanently', 'mailpoet'),
            value: 'delete',
          },
        ];

  return (
    <SelectControl
      multiple={false}
      hideLabelFromVision
      className="mailpoet-segments-listing-group"
      label={__('Bulk Actions', 'mailpoet')}
      options={bulkActions}
      value="0"
      onChange={(action) => {
        if (allSelected.length === 0 || action === '0') {
          return;
        }
        onClick(allSelected, action as DynamicSegmentAction);
      }}
    />
  );
}
