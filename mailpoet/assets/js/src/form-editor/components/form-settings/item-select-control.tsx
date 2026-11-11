import { useEffect, useMemo, useRef } from 'react';
import { ControlWithError, SearchableChipSelectControl } from '@wordpress/ui';

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
  error?: string;
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
  error,
}: Props): JSX.Element {
  const inputRef = useRef<HTMLInputElement>(null);
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

  useEffect(() => {
    if (error) {
      inputRef.current?.dispatchEvent(new Event('invalid'));
    }
  }, [error]);

  return (
    <ControlWithError
      getValidityTarget={() => inputRef.current}
      customValidity={error ? { type: 'invalid', message: error } : undefined}
    >
      <SearchableChipSelectControl
        ref={inputRef}
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
    </ControlWithError>
  );
}
