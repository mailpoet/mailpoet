import { useSelect } from '@wordpress/data';
import { Icon, helpFilled } from '@wordpress/icons';
import { Filter } from '../automation/types';
import { storeName } from '../../store';

type Props = {
  filter: Filter;
};

export function Value({ filter }: Props): JSX.Element | null {
  const { field, filterType } = useSelect(
    (select) => ({
      field: select(storeName).getRegistry().fields[filter.field_key],
      filterType: select(storeName).getFilterType(filter.field_type),
    }),
    [],
  );

  const expectsValue = ![
    'is-set',
    'is-not-set',
    'is-blank',
    'is-not-blank',
  ].includes(filter.condition);

  if (!field || !expectsValue) {
    return undefined;
  }

  const value = filterType?.formatValue(filter, field);
  const params = filterType?.formatParams?.(filter, field);
  if (value === undefined) {
    return expectsValue ? (
      <span className="mailpoet-automation-filters-list-item-value-missing">
        <Icon icon={helpFilled} size={16} />
      </span>
    ) : null;
  }
  return (
    <>
      <span className="mailpoet-automation-filters-list-item-value">
        {value}
      </span>
      {params && <span> {params}</span>}
    </>
  );
}
