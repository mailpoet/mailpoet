import { FormTokenField } from '@wordpress/components';
import { find, last, slice } from 'lodash';

type Event = {
  value: readonly FormTokenField.Value[] | string[];
  name: string;
};

export type TokenFieldProps = {
  id?: string;
  label?: string;
  name?: string;
  placeholder?: string;
  onChange: (event: Event) => void;
  selectedValues?: FormTokenField.Value[] | [];
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
}: TokenFieldProps) {
  const args = {
    id,
    label,
    name,
    placeholder,
    className: 'mailpoet-form-token-field',
  };

  const handleSave = (
    tokens: readonly FormTokenField.Value[],
    onChangeCallback,
  ) => {
    // Check if the newest value is already in tokens
    const values = slice(tokens, 0, tokens.length - 1);
    const newValue = last(tokens);
    const newTag = newValue ? newValue.toString() : '';
    const newTagExists = find(
      values,
      (location: string) => location.toLowerCase() === newTag.toLowerCase(),
    );
    if (newTag && !newTagExists) {
      values.push(newValue);
    }
    onChangeCallback({ value: values, name });
  };

  return (
    <FormTokenField
      {...args}
      value={selectedValues}
      suggestions={suggestedValues}
      onChange={(tokens) => handleSave(tokens, onChange)}
    />
  );
}
