import { useMemo } from 'react';
import { Dropdown } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Step } from './types';
import { Chip } from '../chip';
import { FiltersList } from '../filters';
import { storeName } from '../../store';

type Props = {
  step: Step;
};

export function StepFilters({ step }: Props): JSX.Element | null {
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
      popoverProps={{ offset: 6 }}
      renderToggle={({ onToggle, isOpen }) => (
        <Chip
          variant={errors ? 'danger' : 'default'}
          size="small"
          onClick={onToggle}
          ariaExpanded={isOpen}
        >
          {__(`Filters: ${filterCount}`, 'mailpoet')}
        </Chip>
      )}
      renderContent={() => (
        <div className="mailpoet-automation-editor-step-filters">
          <span className="mailpoet-automation-editor-step-filters-title">
            {__('Filters', 'mailpoet')}
          </span>
          <FiltersList editable={false} />
        </div>
      )}
    />
  );
}
