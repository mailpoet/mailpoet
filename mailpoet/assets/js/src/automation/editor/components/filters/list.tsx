import { Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { Icon, closeSmall } from '@wordpress/icons';
import { storeName } from '../../store';

export function FiltersList(): JSX.Element | null {
  const { step, fields } = useSelect(
    (select) => ({
      step: select(storeName).getSelectedStep(),
      fields: select(storeName).getRegistry().fields,
    }),
    [],
  );

  if (step.filters.length === 0) {
    return null;
  }

  return (
    <div className="mailpoet-automation-filters-list">
      {step.filters.map((filter) => (
        <div
          key={filter.field_key}
          className="mailpoet-automation-filters-list-item"
        >
          <div className="mailpoet-automation-filters-list-item-content">
            <span className="mailpoet-automation-filters-list-item-field">
              {fields[filter.field_key]?.name ??
                sprintf(__('Unknown field "%s"', 'mailpoet'), filter.field_key)}
            </span>{' '}
            <span className="mailpoet-automation-filters-list-item-condition">
              {filter.condition}
            </span>{' '}
            <span className="mailpoet-automation-filters-list-item-value">
              {filter.args.value as string}
            </span>
          </div>
          <Button
            className="mailpoet-automation-filters-list-item-remove"
            isSmall
          >
            <Icon icon={closeSmall} />
          </Button>
        </div>
      ))}
    </div>
  );
}
