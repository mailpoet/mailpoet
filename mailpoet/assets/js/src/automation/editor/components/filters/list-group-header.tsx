import { __ } from '@wordpress/i18n';
import { RadioControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { createInterpolateElement } from '@wordpress/element';
import { FilterGroup } from '../automation/types';
import { storeName } from '../../store';

type Props = {
  editable: boolean;
  group: FilterGroup;
  onOperatorChange: (
    stepId: string,
    groupId: string,
    operator: 'and' | 'or',
  ) => void;
};

export function ListGroupHeader({
  editable,
  group,
  onOperatorChange,
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
          {__(
            'The automation would only be started if the following trigger conditions are met:',
            'mailpoet',
          )}
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
    group.operator === 'and'
      ? __(
          'The automation would only be started if <operator>all of</operator> the following trigger conditions are met:',
          'mailpoet',
        )
      : __(
          'The automation would only be started if <operator>any of</operator> the following trigger conditions are met:',
          'mailpoet',
        ),
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
