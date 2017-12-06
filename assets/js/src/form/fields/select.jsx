import React from 'react';
import _ from 'underscore';

const FormFieldSelect = React.createClass({
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
      keys =
        _.map(
          _.sortBy(
            _.pairs(this.props.field.values),
            item => sortBy(item[0], item[1])
          ),
          item => item[0]
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
        (value, index) => (
          <option
            key={`option-${index}`}
            value={value}>
            { this.props.field.values[value] }
          </option>
          )
      );

    return (
      <select
        name={this.props.field.name}
        id={`field_${this.props.field.name}`}
        value={this.props.item[this.props.field.name]}
        onChange={this.props.onValueChange}
        {...this.props.field.validation}
      >
        {placeholder}
        {options}
      </select>
    );
  },
});

module.exports = FormFieldSelect;
