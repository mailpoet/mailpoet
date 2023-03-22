import { __ } from '@wordpress/i18n';
import { select } from '@wordpress/data';
import { Filter } from '../automation/types';
import { storeName } from '../../store';

const getValue = ({ field_key, args }: Filter): string => {
  const field = select(storeName).getRegistry().fields[field_key];
  switch (field.type) {
    case 'string':
      return args.value as string;
    case 'enum_array': {
      const options = (field.args.options ?? []) as {
        id: string;
        name: string;
      }[];
      const values = Array.isArray(args.value) ? args.value : [args.value];
      const labels = values
        .map((v) => options.find(({ id }) => id === v)?.name)
        .filter((v) => v !== undefined);

      if (labels.length === 0) {
        return __('Unknown value', 'mailpoet');
      }

      const suffix =
        labels.length < values.length
          ? __('and unknown values', 'mailpoet')
          : '';
      return `${labels.join(', ')}${suffix}`;
    }
    default:
      return __('Unknown value', 'mailpoet');
  }
};

type Props = {
  filter: Filter;
};

export function Value({ filter }: Props): JSX.Element {
  return (
    <span className="mailpoet-automation-filters-list-item-value">
      {getValue(filter)}
    </span>
  );
}
