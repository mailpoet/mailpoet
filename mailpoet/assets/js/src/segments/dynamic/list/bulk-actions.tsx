import { __ } from '@wordpress/i18n';
import { SelectControl } from '@woocommerce/components';
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

  return (
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

        const action = value[0]?.value as DynamicSegmentAction;
        if (!action) {
          return;
        }
        onClick(allSelected, action);
      }}
    />
  );
}
