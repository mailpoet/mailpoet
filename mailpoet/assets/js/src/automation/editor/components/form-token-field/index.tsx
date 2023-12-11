import { FormTokenField as WpFormTokenField } from '@wordpress/components';

export type FormTokenItem = {
  id: number | string;
  name: string;
};

export type FormTokenFieldProps = Omit<
  React.ComponentProps<typeof WpFormTokenField>,
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
      label={label}
      value={value.map((item) => item.name)}
      suggestions={suggestions.map((item) => item.name)}
      __experimentalExpandOnFocus
      __experimentalAutoSelectFirstMatch
      __experimentalShowHowTo={false}
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
