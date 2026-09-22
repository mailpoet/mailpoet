import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { Checkbox } from 'common/form/checkbox/checkbox';
import { Grid } from 'common/grid';

import {
  AnyValueTypes,
  EmailActionTypes,
  FilterProps,
  FormItem,
  Segment,
  SegmentConnectTypes,
} from '../../types';
import { storeName } from '../../store';

function getGroupOperator(
  segment: Segment,
  filter: FormItem,
): SegmentConnectTypes {
  const groupId = filter.group_id ?? 0;
  const groupOperator = segment.filters?.find(
    (item) => (item.group_id ?? 0) === groupId,
  )?.group_operator;
  return groupOperator ?? segment.filters_connect ?? SegmentConnectTypes.AND;
}

const actionsUsingNoneOperator: string[] = [
  EmailActionTypes.OPENED,
  EmailActionTypes.MACHINE_OPENED,
  EmailActionTypes.CLICKED,
];

/**
 * Mirrors FilterDataMapper::withOnlyTrackable(): the option only takes effect
 * where the server keeps it.
 */
export function isOnlyTrackableActive(
  segment: Segment,
  filter: FormItem,
): boolean {
  if (filter.onlyTrackable !== true) {
    return false;
  }
  if (getGroupOperator(segment, filter) === SegmentConnectTypes.NONE) {
    return false;
  }
  if (!actionsUsingNoneOperator.includes(filter.action)) {
    return true;
  }
  return 'operator' in filter && filter.operator === AnyValueTypes.NONE;
}

export function OnlyTrackableField({ filterIndex }: FilterProps): JSX.Element {
  const segment: Segment = useSelect(
    (select) => select(storeName).getSegment(),
    [],
  );
  const filter: FormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );
  const { updateSegmentFilter } = useDispatch(storeName);

  // Inside a "none of" group the filter's result is excluded, so narrowing it
  // would add untracked subscribers back in. The server drops the option there too.
  if (getGroupOperator(segment, filter) === SegmentConnectTypes.NONE) {
    return null;
  }

  return (
    <Grid.CenteredRow>
      <Checkbox
        checked={filter.onlyTrackable === true}
        automationId="segment-only-trackable"
        onCheck={(isChecked) => {
          void updateSegmentFilter({ onlyTrackable: isChecked }, filterIndex);
        }}
      >
        {__('Only subscribers we can track', 'mailpoet')}
      </Checkbox>
    </Grid.CenteredRow>
  );
}
