define([
  'react'
],
function(
  React
) {
  var FormFieldRadio = React.createClass({
    render: function() {
      var selected_value = this.props.item[this.props.field.name];
      var count = Object.keys(this.props.field.values).length;

      var options = Object.keys(this.props.field.values).map(
        function(value, index) {
          return (
            <p key={ 'radio-' + index }>
              <label>
                <input
                  type="radio"
                  checked={ selected_value === value }
                  value={ value }
                  onChange={ this.props.onValueChange }
                  name={ this.props.field.name } />
                &nbsp;{ this.props.field.values[value] }
              </label>
            </p>
          );
        }.bind(this)
      );

      return (
        <div>
          { options }
        </div>
      );
    }
  });

  return FormFieldRadio;
});