import React from 'react';
import PropTypes from 'prop-types';

class FormFieldRadio extends React.Component { // eslint-disable-line react/prefer-stateless-function, max-len
  render() {
    if (this.props.field.values === undefined) {
      return false;
    }

    const selectedValue = this.props.item[this.props.field.name];
    const options = Object.keys(this.props.field.values).map(
      (value) => (
        <p key={`radio-${value}`}>
          <label htmlFor={this.props.field.name}>
            <input
              type="radio"
              checked={selectedValue === value}
              value={value}
              onChange={this.props.onValueChange}
              name={this.props.field.name}
              id={this.props.field.name}
            />
            { this.props.field.values[value] }
          </label>
        </p>
      )
    );

    return (
      <div>
        { options }
      </div>
    );
  }
}

FormFieldRadio.propTypes = {
  onValueChange: PropTypes.func,
  field: PropTypes.shape({
    name: PropTypes.string.isRequired,
    values: PropTypes.object,
  }).isRequired,
  item: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
};

FormFieldRadio.defaultProps = {
  onValueChange: function onValueChange() {
    // no-op
  },
};


export default FormFieldRadio;
