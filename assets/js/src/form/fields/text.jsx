define([
  'react'
],
function(
  React
) {
  var FormFieldText = React.createClass({
    render: function() {
      var value = this.props.item[this.props.field.name];
      if(!value) { value = null; }
      return (
        <input
          type="text"
          className={ (this.props.field.size) ? '' : 'regular-text' }
          size={
            (this.props.field.size !== 'auto' && this.props.field.size > 0)
            ? this.props.field.size
            : false
          }
          name={ this.props.field.name }
          id={ 'field_'+this.props.field.name }
          value={ value }
          placeholder={ this.props.field.placeholder }
          defaultValue={ this.props.field.defaultValue }
          onChange={ this.props.onValueChange }
          {...this.props.field.validation}
        />
      );
    }
  });

  return FormFieldText;
});