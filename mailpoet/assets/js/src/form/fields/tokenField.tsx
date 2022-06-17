import PropTypes from 'prop-types';
import { TokenField } from 'common/form/tokenField/tokenField';

function getItems(endpoint: string) {
  let items = [];
  if (typeof window[`mailpoet_${endpoint}`] !== 'undefined') {
    items = window[`mailpoet_${endpoint}`];
  }

  return items;
}

function FormFieldTokenField(props) {
  const selectedValues = Array.isArray(props.item[props.field.name])
    ? props.item[props.field.name].map((item) => props.field.getName(item))
    : [];

  let suggestedValues = [];
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

FormFieldTokenField.propTypes = {
  onValueChange: PropTypes.func,
  item: PropTypes.object, // eslint-disable-line react/forbid-prop-types
  field: PropTypes.shape({
    name: PropTypes.string,
    label: PropTypes.string,
    suggestedValues: PropTypes.arrayOf(PropTypes.string),
    placeholder: PropTypes.string,
    getName: PropTypes.func,
  }).isRequired,
};

export { FormFieldTokenField };
