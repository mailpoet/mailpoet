import { useMemo } from 'react';
import { SearchableChipSelectControl } from '@wordpress/ui';

export type SelectItem = {
  id: string;
  name: string;
};

type Option = {
  value: string;
  label: string;
};

type Props = {
  label: string;
  hideLabelFromVision?: boolean;
  placeholder: string;
  items: SelectItem[];
  selected: (string | number)[];
  onChange: (selected: string[]) => void;
};

const isSameOption = (option: Option, value: Option): boolean =>
  option.value === value.value;

export function ItemSelectControl({
  label,
  hideLabelFromVision = false,
  placeholder,
  items,
  selected,
  onChange,
}: Props): JSX.Element {
  const options = useMemo(
    () =>
      items.map((item) => ({
        value: String(item.id),
        label: item.name.trim() || `#${item.id}`,
      })),
    [items],
  );

  const value = useMemo(() => {
    const optionsById = new Map(
      options.map((option) => [option.value, option]),
    );
    return selected
      .map((id) => optionsById.get(String(id)))
      .filter((option) => option !== undefined);
  }, [options, selected]);

  return (
    <SearchableChipSelectControl
      label={label}
      hideLabelFromVision={hideLabelFromVision}
      searchPlaceholder={placeholder}
      items={options}
      value={value}
      onValueChange={(newValue: Option[]): void =>
        onChange(newValue.map((option) => option.value))
      }
      isItemEqualToValue={isSameOption}
      autoHighlight
    />
  );
}
