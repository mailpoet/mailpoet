import { Component } from 'react';
import PropTypes from 'prop-types';
import { Input } from 'common/form/input/input';

// eslint-disable-next-line react/prefer-stateless-function, max-len
class FormFieldText extends Component {
  render() {
    const { onValueChange = () => {}, onBlurEvent = () => {} } = this.props;
    const name = this.props.field.name || null;
    const item = this.props.item || {};
    let value;
    let defaultValue;
    // value should only be set when onChangeValue is configured
    if (onValueChange instanceof Function) {
      value = item[this.props.field.name];
      // set value to defaultValue if available
      value = value === undefined ? this.props.field.defaultValue || '' : value;
    }
    // defaultValue should only be set only when value is not set
    if (!value && this.props.field.defaultValue) {
      defaultValue = this.props.field.defaultValue;
    }

    let id = this.props.field.id || null;
    if (!id && this.props.field.name) {
      id = `field_${this.props.field.name}`;
    }

    let className = this.props.field.className || null;
    if (!className && !this.props.field.size) {
      className = 'regular-text';
    }

    let disabled;
    if (typeof this.props.field.disabled === 'function') {
      disabled = this.props.field.disabled(this.props.item);
    } else if (typeof this.props.field.disabled === 'boolean') {
      disabled = this.props.field.disabled;
    } else {
      disabled = false;
    }

    return (
      <Input
        type="text"
        disabled={disabled}
        className={className}
        size={
          this.props.field.size !== 'auto' && this.props.field.size > 0
            ? this.props.field.size
            : null
        }
        name={name}
        id={id}
        value={value}
        defaultValue={defaultValue}
        placeholder={this.props.field.placeholder}
        onChange={onValueChange}
        onBlur={onBlurEvent}
        customLabel={this.props.field.customLabel}
        tooltip={this.props.field.tooltip}
        {...this.props.field.validation}
      />
    );
  }
}

FormFieldText.propTypes = {
  onValueChange: PropTypes.func,
  onBlurEvent: PropTypes.func,
  field: PropTypes.shape({
    name: PropTypes.string.isRequired,
    defaultValue: PropTypes.string,
    id: PropTypes.string,
    className: PropTypes.string,
    size: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    disabled: PropTypes.oneOfType([PropTypes.bool, PropTypes.func]),
    placeholder: PropTypes.string,
    validation: PropTypes.shape({
      'data-parsley-required': PropTypes.bool,
      'data-parsley-required-message': PropTypes.string,
      'data-parsley-type': PropTypes.string,
      'data-parsley-errors-container': PropTypes.string,
      maxLength: PropTypes.number,
    }),
    customLabel: PropTypes.string,
    tooltip: PropTypes.string,
  }).isRequired,
  item: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
};

export { FormFieldText };
