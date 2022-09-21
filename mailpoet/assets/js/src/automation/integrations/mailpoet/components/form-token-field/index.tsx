import { FormTokenField as WpFormTokenField } from '@wordpress/components';
import { uniq } from 'lodash';

export type FormTokenItem = {
  id: number | string;
  name: string;
};

export type FormTokenFieldProps = Omit<
  WpFormTokenField.Props,
  'value' | 'suggestions' | 'onChange'
> & {
  selected: FormTokenItem[];
  suggestions: FormTokenItem[];
  onChange: (values: FormTokenItem[]) => void;
  label: string;
  anyValue?: FormTokenItem;
  anyValueIsDefault?: boolean;
};

export function FormTokenField({
  label,
  selected,
  suggestions,
  onChange,
  anyValue,
  anyValueIsDefault,
  ...props
}: FormTokenFieldProps): JSX.Element {
  if (anyValue) {
    suggestions.push(anyValue);
  }
  const uniqueSuggestions = uniq(suggestions);
  return (
    <WpFormTokenField
      // eslint-disable-next-line @typescript-eslint/ban-ts-comment
      // @ts-ignore
      // The following error seems to be a mismatch. It claims the 'label' prop does not exist, but it does.
      label={label}
      value={selected.map((item) => item.name)}
      suggestions={uniqueSuggestions.map((item) => item.name)}
      __experimentalExpandOnFocus
      __experimentalAutoSelectFirstMatch
      onChange={(raw: string[]) => {
        const allSelected: FormTokenItem[] = raw.map((item) => {
          const match = uniqueSuggestions.find(
            (suggestion) =>
              suggestion.name.toLowerCase() === item.toLowerCase(),
          );
          return match;
        });

        // only the newly selected items
        const newSelected = allSelected.filter((item) => {
          for (let i = 0; i < selected.length; i += 1) {
            if (selected[i].id === item.id) {
              return false;
            }
          }
          return true;
        });
        if (anyValue) {
          const hasAny =
            newSelected.filter((item) => item.id === anyValue.id).length > 0;
          const hasNone = allSelected.length === 0 && anyValueIsDefault;
          if (hasAny || hasNone) {
            // when "any item" has been selected, all other items are removed
            // or when no item has been selected but "any item" is default, we store "any item"
            onChange([anyValue]);
            return;
          }
        }

        // kick out the "any item" if it was selected before.
        const allSelectedWithoutAny = anyValue
          ? allSelected.filter((item) => item.id !== anyValue.id)
          : allSelected;
        onChange(allSelectedWithoutAny);
      }}
      {...props}
    />
  );
}
