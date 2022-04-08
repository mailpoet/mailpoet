import { Component } from 'react';
import PropTypes from 'prop-types';
import Radio from 'common/form/radio/radio';

class FormFieldRadio extends Component {
  // eslint-disable-line react/prefer-stateless-function, max-len
  constructor(props) {
    super(props);
    this.onValueChange = this.onValueChange.bind(this);
  }

  onValueChange = (value, e) => this.props.onValueChange(e);

  render() {
    if (this.props.field.values === undefined) {
      return false;
    }

    const selectedValue = this.props.item[this.props.field.name];
    const options = Object.keys(this.props.field.values).map((value) => (
      <p key={`radio-${value}`}>
        <Radio
          checked={selectedValue === value}
          value={value}
          onCheck={this.onValueChange}
          name={this.props.field.name}
        >
          {this.props.field.values[value]}
        </Radio>
      </p>
    ));

    return <div>{options}</div>;
  }
}

FormFieldRadio.propTypes = {
  onValueChange: PropTypes.func,
  field: PropTypes.shape({
    name: PropTypes.string.isRequired,
    values: PropTypes.objectOf(PropTypes.string),
  }).isRequired,
  item: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
};

FormFieldRadio.defaultProps = {
  onValueChange: function onValueChange() {
    // no-op
  },
};

export default FormFieldRadio;
