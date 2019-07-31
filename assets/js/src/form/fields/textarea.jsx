import React from 'react';
import PropTypes from 'prop-types';

const FormFieldTextarea = (props) => (
  <textarea
    type="text"
    className="regular-text"
    name={props.field.name}
    id={`field_${props.field.name}`}
    value={props.item[props.field.name]}
    placeholder={props.field.placeholder}
    defaultValue={props.field.defaultValue}
    onChange={props.onValueChange}
    {...props.field.validation}
  />
);

FormFieldTextarea.propTypes = {
  item: PropTypes.object.isRequired, //  eslint-disable-line react/forbid-prop-types
  field: PropTypes.shape({
    name: PropTypes.string,
    placeholder: PropTypes.string,
    defaultValue: PropTypes.string,
    validation: PropTypes.object, //  eslint-disable-line react/forbid-prop-types
  }).isRequired,
  onValueChange: PropTypes.func.isRequired,
};

export default FormFieldTextarea;
