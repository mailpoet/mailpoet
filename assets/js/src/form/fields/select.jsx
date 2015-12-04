define([
  'react'
],
function(
  React
) {
  var FormFieldSelect = React.createClass({
    render: function() {
      var options =
        Object.keys(this.props.field.values).map(function(value, index) {
          return (
            <option
              key={ 'option-' + index }
              value={ value }>
              { this.props.field.values[value] }
            </option>
          );
        }.bind(this)
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