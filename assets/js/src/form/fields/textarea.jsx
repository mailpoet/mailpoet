import React from 'react';
import PropTypes from 'prop-types';
import Textarea from 'common/form/textarea/textarea';

const FormFieldTextarea = (props) => (
  <Textarea
    type="text"
    name={props.field.name}
    id={`field_${props.field.name}`}
    value={props.item[props.field.name]}
    placeholder={props.field.placeholder}
    defaultValue={props.field.defaultValue}
    onChange={props.onValueChange}
    className={props.field.class}
    customLabel={props.field.customLabel}
    tooltip={props.field.tooltip}
    {...props.field.validation}// eslint-disable-line react/jsx-props-no-spreading
  />
);

FormFieldTextarea.propTypes = {
  item: PropTypes.object.isRequired, //  eslint-disable-line react/forbid-prop-types
  field: PropTypes.shape({
    name: PropTypes.string,
    placeholder: PropTypes.string,
    defaultValue: PropTypes.string,
    validation: PropTypes.object, //  eslint-disable-line react/forbid-prop-types
    class: PropTypes.string,
    customLabel: PropTypes.string,
    tooltip: PropTypes.string,
  }).isRequired,
  onValueChange: PropTypes.func.isRequired,
};

export default FormFieldTextarea;
