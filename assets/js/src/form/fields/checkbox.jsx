define([
  'react',
  'react-checkbox-group'
],
function(
  React,
  CheckboxGroup
) {
  var FormFieldCheckbox = React.createClass({
    render: function() {
      var selected_values = this.props.item[this.props.field.name] || '';
      if(
        selected_values !== undefined
        && selected_values.constructor !== Array
      ) {
        selected_values = selected_values.split(';').map(function(value) {
          return value.trim();
        });
      }
      var count = Object.keys(this.props.field.values).length;

      var options = Object.keys(this.props.field.values).map(
        function(value, index) {
          return (
            <p key={ 'checkbox-' + index }>
              <label>
                <input type="checkbox" value={ value } />
                &nbsp;{ this.props.field.values[value] }
              </label>
            </p>
          );
        }.bind(this)
      );

      return (
        <CheckboxGroup
          name={ this.props.field.name }
          value={ selected_values }
          ref={ this.props.field.name }
          onChange={ this.handleValueChange }>
          { options }
        </CheckboxGroup>
      );
    },
    handleValueChange: function() {
      var field = this.props.field.name;
      var group = this.refs[field];
      var selected_values = [];

      if(group !== undefined) {
        selected_values = group.getCheckedValues();
      }

      return this.props.onValueChange({
        target: {
          name: field,
          value: selected_values.join(';')
        }
      });
    }
  });

  return FormFieldCheckbox;
});