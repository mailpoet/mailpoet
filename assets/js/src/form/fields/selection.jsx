define([
  'react',
  'jquery',
  'select2'
],
function(
  React,
  jQuery
) {
  var Selection = React.createClass({
    getInitialState: function() {
      return {
        items: []
      }
    },
    componentWillMount: function() {
      this.loadCachedItems();
    },
    componentDidMount: function() {
      jQuery('#'+this.props.field.id).select2()
        .on('change', this.handleChange);
    },
    componentDidUpdate: function() {
      jQuery('#'+this.props.field.id).select2(
        'val',
        (this.props.item[this.props.field.name])
      );
    },
    loadCachedItems: function() {
      if(typeof(window['mailpoet_'+this.props.field.endpoint]) !== 'undefined') {
        var items = window['mailpoet_'+this.props.field.endpoint];
        this.setState({
          items: items
        });
      }
    },
    handleChange: function() {
      return this.props.onValueChange({
        target: {
          value: jQuery('#'+this.props.field.id).select2('val'),
          name: this.props.field.name
        }
      });
    },
    render: function() {
      var options = this.state.items.map(function(item, index) {
        return (
          <option
            key={ item.id }
            value={ item.id }
          >
            { item.name }
          </option>
        );
      });

      return (
        <select
          ref="selection"
          id={ this.props.field.id || 'mailpoet_field_selection'}
          placeholder={ this.props.field.placeholder }
          multiple={ this.props.field.multiple }
          onChange={ this.handleChange }
          defaultValue={ this.props.item[this.props.field.name] }
        >{ options }</select>
      );
    }
  });

  return Selection;
});