import { Dropdown } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Chip } from '../chip';
import { FiltersList } from '../filters';

type Props = {
  filterCount: number;
};

export function StepFilters({ filterCount }: Props): JSX.Element {
  return (
    <Dropdown
      popoverProps={{ offset: 6 }}
      renderToggle={({ onToggle, isOpen }) => (
        <Chip size="small" onClick={onToggle} ariaExpanded={isOpen}>
          {__(`Filters: ${filterCount}`, 'mailpoet')}
        </Chip>
      )}
      renderContent={() => (
        <div className="mailpoet-automation-editor-step-filters">
          <span className="mailpoet-automation-editor-step-filters-title">
            {__('Filters', 'mailpoet')}
          </span>
          <div className="mailpoet-automation-editor-step-filters-description">
            {__(
              'The automation would only be started if the following trigger conditions are met:',
              'mailpoet',
            )}
          </div>
          <FiltersList allowDelete={false} />
        </div>
      )}
    />
  );
}
