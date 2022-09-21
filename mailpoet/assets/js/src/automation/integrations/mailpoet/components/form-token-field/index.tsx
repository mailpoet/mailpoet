import { FormTokenField as WpFormTokenField } from '@wordpress/components';

export type FormTokenItem = {
  id: number | string;
  name: string;
};

export type FormTokenFieldProps = Omit<
  WpFormTokenField.Props,
  'value' | 'suggestions' | 'onChange'
> & {
  value: FormTokenItem[];
  suggestions: FormTokenItem[];
  onChange: (values: FormTokenItem[]) => void;
  placeholder: string;
  label: string;
};

export function FormTokenField({
  label,
  value,
  suggestions,
  placeholder,
  onChange,
  ...props
}: FormTokenFieldProps): JSX.Element {
  return (
    <WpFormTokenField
      // eslint-disable-next-line @typescript-eslint/ban-ts-comment
      // @ts-ignore
      // The following error seems to be a mismatch. It claims the 'label' prop does not exist, but it does.
      label={label}
      value={value.map((item) => item.name)}
      suggestions={suggestions.map((item) => item.name)}
      __experimentalExpandOnFocus
      __experimentalAutoSelectFirstMatch
      placeholder={placeholder}
      onChange={(raw: string[]) => {
        const allSelected: FormTokenItem[] = raw
          .map((item) => {
            const match = suggestions.find(
              (suggestion) =>
                suggestion.name.toLowerCase() === item.toLowerCase(),
            );
            return match ?? null;
          })
          .filter((item) => item !== null);
        onChange(allSelected);
      }}
      {...props}
    />
  );
}
