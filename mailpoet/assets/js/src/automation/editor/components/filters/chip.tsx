import { useMemo } from 'react';
import { Dropdown } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { FiltersList } from './list';
import { FilterStrings } from './strings';
import { Step } from '../automation/types';
import { Chip } from '../chip';
import { storeName } from '../../store';

type Props = {
  step: Step;
  strings: FilterStrings;
};

export function FiltersChip({ step, strings }: Props): JSX.Element | null {
  const { errors } = useSelect(
    (select) => ({
      errors: select(storeName).getStepError(step.id),
    }),
    [],
  );

  const groups = step.filters?.groups;
  const filterCount = useMemo(
    () => (groups ?? []).reduce((sum, group) => sum + group.filters.length, 0),
    [groups],
  );

  if (filterCount === 0) {
    return null;
  }

  return (
    <Dropdown
      focusOnMount="container"
      popoverProps={{ offset: 6 }}
      renderToggle={({ onToggle, isOpen }) => (
        <Chip
          variant={errors ? 'danger' : 'default'}
          size="small"
          onClick={onToggle}
          ariaExpanded={isOpen}
        >
          {__(`${strings.label}: ${filterCount}`, 'mailpoet')}
        </Chip>
      )}
      renderContent={() => (
        <div className="mailpoet-automation-editor-step-filters">
          <span className="mailpoet-automation-editor-step-filters-title">
            {strings.title}
          </span>
          <FiltersList step={step} editable={false} strings={strings} />
        </div>
      )}
    />
  );
}
