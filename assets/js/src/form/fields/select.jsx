import React from 'react';
import _ from 'underscore';
import PropTypes from 'prop-types';

class FormFieldSelect extends React.Component {
  render() {
    if (this.props.field.values === undefined) {
      return false;
    }

    let filter = false;
    let placeholder = false;
    let sortBy = false;

    if (this.props.field.placeholder !== undefined) {
      placeholder = (
        <option value="">{ this.props.field.placeholder }</option>
      );
    }

    if (this.props.field.filter !== undefined) {
      filter = this.props.field.filter;
    }

    if (_.isFunction(this.props.field.sortBy)) {
      sortBy = this.props.field.sortBy;
    }

    let keys;
    if (sortBy) {
      // Extract keys from sorted [key, value] select value pairs, sorted by
      // provided sorting order.
      keys = _.map(
        _.sortBy(
          _.pairs(this.props.field.values),
          (item) => sortBy(item[0], item[1])
        ),
        (item) => item[0]
      );
    } else {
      keys = Object.keys(this.props.field.values);
    }

    const options = keys
      .filter((value) => {
        if (filter === false) return true;
        return filter(this.props.item, value);
      })
      .map(
        (value) => (
          <option
            key={`option-${value}`}
            value={value}
          >
            { this.props.field.values[value] }
          </option>
        )
      );

    return (
      <select
        name={this.props.field.name}
        id={`field_${this.props.field.name}`}
        value={this.props.item[this.props.field.name] || ''}
        onChange={this.props.onValueChange}
        data-automation-id={this.props.automationId}
        {...this.props.field.validation} // eslint-disable-line react/jsx-props-no-spreading
      >
        {placeholder}
        {options}
      </select>
    );
  }
}

FormFieldSelect.propTypes = {
  onValueChange: PropTypes.func,
  field: PropTypes.shape({
    name: PropTypes.string.isRequired,
    values: PropTypes.object,
    placeholder: PropTypes.string,
    filter: PropTypes.func,
    sortBy: PropTypes.func,
    validation: PropTypes.object,
  }).isRequired,
  item: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
  automationId: PropTypes.string,
};

FormFieldSelect.defaultProps = {
  automationId: '',
  onValueChange: function onValueChange() {
    // no-op
  },
};


export default FormFieldSelect;
