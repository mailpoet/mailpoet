import { Component } from 'react';
import PropTypes from 'prop-types';
import Checkbox from 'common/form/checkbox/checkbox';

class FormFieldCheckbox extends Component {
  constructor(props) {
    super(props);
    this.onValueChange = this.onValueChange.bind(this);
  }

  onValueChange = (value, e) => {
    e.target.value = value ? '1' : '0';
    return this.props.onValueChange(e);
  };

  render() {
    if (this.props.field.values === undefined) {
      return false;
    }

    // isChecked will be true only if the value is "1"
    // it will be false in case value is "0" or empty
    const isChecked = !!Number(this.props.item[this.props.field.name]);
    const options = Object.keys(this.props.field.values).map((value) => (
      <p key={`checkbox-${value}`}>
        <Checkbox
          value="1"
          checked={isChecked}
          name={this.props.field.name}
          onCheck={this.onValueChange}
        >
          {this.props.field.values[value]}
        </Checkbox>
      </p>
    ));

    return <div>{options}</div>;
  }
}

FormFieldCheckbox.propTypes = {
  onValueChange: PropTypes.func.isRequired,
  field: PropTypes.shape({
    name: PropTypes.string.isRequired,
    values: PropTypes.objectOf(PropTypes.string),
  }).isRequired,
  item: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
};

export default FormFieldCheckbox;
