import { __ } from '@wordpress/i18n';
import { RadioControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { createInterpolateElement } from '@wordpress/element';
import { FilterGroup } from '../automation/types';
import { FilterStrings } from './strings';
import { storeName } from '../../store';

type Props = {
  editable: boolean;
  group: FilterGroup;
  onOperatorChange: (
    stepId: string,
    groupId: string,
    operator: 'and' | 'or',
  ) => void;
  strings: FilterStrings;
};

export function ListGroupHeader({
  editable,
  group,
  onOperatorChange,
  strings,
}: Props): JSX.Element {
  const { step } = useSelect(
    (select) => ({
      step: select(storeName).getSelectedStep(),
    }),
    [],
  );

  if (editable || group.filters.length === 1) {
    return (
      <>
        <div className="mailpoet-automation-filters-list-group-description">
          {strings.groupDescription}
        </div>
        {group.filters.length > 1 && (
          <RadioControl
            className="mailpoet-automation-filters-list-group-operator"
            selected={group.operator}
            onChange={(value) =>
              onOperatorChange(step.id, group.id, value as 'and' | 'or')
            }
            options={[
              { label: __('All conditions', 'mailpoet'), value: 'and' },
              { label: __('Any condition', 'mailpoet'), value: 'or' },
            ]}
          />
        )}
      </>
    );
  }

  // read-only
  const description = createInterpolateElement(
    group.operator === 'and' ? strings.andDescription : strings.orDescription,
    {
      operator: (
        <span className="mailpoet-automation-filters-list-group-description-operator" />
      ),
    },
  );
  return (
    <div className="mailpoet-automation-filters-list-group-description">
      {description}
    </div>
  );
}
