// This is a temporary component until we can properly use TreeSelectControl from @woocommerce/components
import { TreeSelect } from '@wordpress/components';

export type MultiSelectOption = {
  name: string;
  id: string;
  children?: MultiSelectOption[];
};

type MultiSelectProps = {
  selected: string[] | undefined;
  options: MultiSelectOption[];
  onChange: (value: string[]) => void;
  allOption?: string;
  label: string;
};

export function MultiSelect({
  selected,
  label,
  allOption,
  options,
  onChange,
}: MultiSelectProps): JSX.Element {
  const change = (value: string) => {
    onChange(value.split(','));
  };
  let selectedId = '';
  if (selected && selected.length > 0) {
    selectedId = selected.join(',');
  }

  return (
    <div className="mailpoet-analytics-multiselect">
      <TreeSelect
        label={label}
        noOptionLabel={allOption}
        onChange={change}
        selectedId={selectedId}
        tree={options}
      />
    </div>
  );
}
