import React from 'react';
import PropTypes from 'prop-types';
import Input from 'common/form/input/input';

class FormFieldText extends React.Component { // eslint-disable-line react/prefer-stateless-function, max-len
  render() {
    const name = this.props.field.name || null;
    const item = this.props.item || {};
    let value;
    let defaultValue;
    // value should only be set when onChangeValue is configured
    if (this.props.onValueChange instanceof Function) {
      value = item[this.props.field.name];
      // set value to defaultValue if available
      value = (value === undefined)
        ? (this.props.field.defaultValue || '') : value;
    }
    // defaultValue should only be set only when value is not set
    if (!value && this.props.field.defaultValue) {
      defaultValue = this.props.field.defaultValue;
    }

    let id = this.props.field.id || null;
    if (!id && this.props.field.name) {
      id = `field_${this.props.field.name}`;
    }

    let className = this.props.field.class || null;
    if (!className && !this.props.field.size) {
      className = 'regular-text';
    }

    return (
      <Input
        type="text"
        disabled={
          (this.props.field.disabled !== undefined)
            ? this.props.field.disabled(this.props.item)
            : false
        }
        className={className}
        size={
          (this.props.field.size !== 'auto' && this.props.field.size > 0)
            ? this.props.field.size
            : null
        }
        name={name}
        id={id}
        value={value}
        defaultValue={defaultValue}
        placeholder={this.props.field.placeholder}
        onChange={this.props.onValueChange}
        customLabel={this.props.field.customLabel}
        tooltip={this.props.field.tooltip}
        {...this.props.field.validation}// eslint-disable-line react/jsx-props-no-spreading
      />
    );
  }
}

FormFieldText.propTypes = {
  onValueChange: PropTypes.func,
  field: PropTypes.shape({
    name: PropTypes.string.isRequired,
    defaultValue: PropTypes.string,
    id: PropTypes.string,
    class: PropTypes.string,
    size: PropTypes.oneOfType([
      PropTypes.string,
      PropTypes.number,
    ]),
    disabled: PropTypes.func,
    placeholder: PropTypes.string,
    validation: PropTypes.object,
    customLabel: PropTypes.string,
    tooltip: PropTypes.string,
  }).isRequired,
  item: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
};

FormFieldText.defaultProps = {
  onValueChange: function onValueChange() {
    // no-op
  },
};

export default FormFieldText;
