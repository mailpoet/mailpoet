define([
  'react'
],
function(
  React
) {
  var FormFieldText = React.createClass({
    render: function() {
      return (
        <input
          type="text"
          className={ (this.props.field.size) ? '' : 'regular-text' }
          size={ (this.props.field.size !== 'auto') ? this.props.field.size : false }
          name={ this.props.field.name }
          id={ 'field_'+this.props.field.name }
          value={ this.props.item[this.props.field.name] }
          placeholder={ this.props.field.placeholder }
          onChange={ this.props.onValueChange } />
      );
    }
  });

  return FormFieldText;
});