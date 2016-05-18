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

      let values = this.props.field.values;
      let filter = false;
      let empty_option = false;

      if (this.props.field.empty_value_label !== undefined) {
        empty_option = (
          <option value="">{ this.props.field.empty_value_label }</option>
        );
      }

      if (this.props.field['filter'] !== undefined) {
        filter = this.props.field.filter;
      }

      const options = Object.keys(values).map(
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
          {empty_option}
          {options}
        </select>
      );
    }
  });

  return FormFieldSelect;
});