define([
  'react'
],
function(
  React
) {
  var FormFieldTextarea = React.createClass({
    render: function() {
      return (
        <textarea
          type="text"
          className="regular-text"
          name={ this.props.field.name }
          id={ 'field_'+this.props.field.name }
          value={ this.props.item[this.props.field.name] }
          onChange={ this.props.onValueChange } />
      );
    }
  });

  return FormFieldTextarea;
});