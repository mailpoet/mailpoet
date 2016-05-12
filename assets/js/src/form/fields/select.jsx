define([
  'react'
],
function(
  React
) {
  const FormFieldSelect = React.createClass({
    render: function() {
      if (this.props.field.values === undefined) {
        return false;
      }

      var values = (this.props.field.filterValues !== undefined)
        ? this.props.field.filterValues(this.props.item)
        : this.props.field.values;

      const options = Object.keys(values).map(
        (value, index) => {
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
          {options}
        </select>
      );
    }
  });

  return FormFieldSelect;
});