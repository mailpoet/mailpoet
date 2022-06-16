import { FormTokenField } from '@wordpress/components';

type Event = {
  value: readonly FormTokenField.Value[] | string[];
  name: string;
};

type Props = {
  id?: string;
  label?: string;
  name?: string;
  placeholder?: string;
  onChange: (event: Event) => void;
  selectedValues?: FormTokenField.Value[];
  suggestedValues?: readonly string[];
};

export function TokenField({
  id,
  label,
  name,
  placeholder,
  selectedValues,
  suggestedValues,
  onChange,
}: Props) {
  const args = {
    id,
    label,
    name,
    placeholder,
    className: 'mailpoet-form-token-field',
  };
  return (
    <FormTokenField
      {...args}
      value={selectedValues}
      suggestions={suggestedValues}
      onChange={(tokens) => onChange({ value: tokens, name })}
    />
  );
}
