import { TokenFieldProps, TokenField } from 'common/form/tokenField/tokenField';
import { FormTokenItem } from '../../automation/editor/components';

interface TokenFormFieldProps {
  onValueChange: TokenFieldProps['onChange'];
  item?: Record<string, FormTokenItem[]>;
  field: Omit<TokenFieldProps, 'id' | 'onChange' | 'selectedValues'> & {
    endpoint: string;
    getName: (item: FormTokenItem) => string;
  };
}

function getItems(endpoint: string): FormTokenItem[] {
  let items = [];
  if (typeof window[`mailpoet_${endpoint}`] !== 'undefined') {
    items = window[`mailpoet_${endpoint}`];
  }

  return items;
}

function FormFieldTokenField(props: TokenFormFieldProps) {
  const selectedValues: TokenFieldProps['selectedValues'] = Array.isArray(
    props.item[props.field.name],
  )
    ? props.field.name &&
      props.item[props.field.name].map((item) => props.field.getName(item))
    : [];

  let suggestedValues: readonly string[] = [];
  if (props.field.endpoint) {
    const items = getItems(String(props.field.endpoint));
    suggestedValues = items.map((item) => props.field.getName(item));
  } else if (props.field.suggestedValues) {
    suggestedValues = props.field.suggestedValues;
  }

  return (
    <TokenField
      label={props.field.label}
      name={props.field.name}
      placeholder={props.field.placeholder}
      selectedValues={selectedValues}
      suggestedValues={suggestedValues}
      onChange={props.onValueChange}
    />
  );
}

export { FormFieldTokenField };
