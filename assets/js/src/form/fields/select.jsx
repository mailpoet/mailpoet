import React from 'react'

const FormFieldSelect = React.createClass({
  render() {
    if (this.props.field.values === undefined) {
      return false;
    }

    let filter = false;
    let placeholder = false;

    if (this.props.field.placeholder !== undefined) {
      placeholder = (
        <option value="">{ this.props.field.placeholder }</option>
      );
    }

    if (this.props.field['filter'] !== undefined) {
      filter = this.props.field.filter;
    }

    const options = Object.keys(this.props.field.values).map(
      (value, index) => {

        if (filter !== false && filter(this.props.item, value) === false) {
          return;
        }

        return (
          <option
            key={ 'option-' + index }
            value={ value }>
            { this.props.field.values[value] }
          </option>
        );
      }
    );

    return (
      <select
        name={ this.props.field.name }
        id={ 'field_'+this.props.field.name }
        value={ this.props.item[this.props.field.name] }
        onChange={ this.props.onValueChange }
        {...this.props.field.validation}
      >
        {placeholder}
        {options}
      </select>
    );
  }
});

module.exports = FormFieldSelect;