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
        items: [],
        initialized: false
      }
    },
    componentDidMount: function() {
      this.loadCachedItems();
    },
    componentDidUpdate: function() {
      this.setupSelect2();
    },
    setupSelect2: function() {
      if(this.state.initialized === true) {
        return;
      }

      if(this.props.field.select2 && Object.keys(this.props.item).length > 0) {
        var select2 = jQuery('#'+this.props.field.id).select2({
          width: (this.props.width || ''),
          templateResult: function(item) {
            if (item.element && item.element.selected) {
              return null;
            } else {
              return item.text;
            }
          }
        });

        select2.on('change', this.handleChange)

        select2.select2(
          'val',
          this.props.item[this.props.field.name]
        );

        this.setState({ initialized: true });
      }
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
      if(this.props.onValueChange !== undefined) {
        this.props.onValueChange({
         target: {
            value: jQuery('#'+this.props.field.id).select2('val'),
            name: this.props.field.name
          }
        });
      }
      return true;
    },
    render: function() {
      if(this.state.items.length === 0) {
        return false;
      } else {
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

        var default_value = (
          (this.props.item !== undefined && this.props.field.name !== undefined)
          ? this.props.item[this.props.field.name]
          : null
        );

        return (
          <select
            id={ this.props.field.id }
            placeholder={ this.props.field.placeholder }
            multiple={ this.props.field.multiple }
            onChange={ this.handleChange }
            defaultValue={ default_value }
          >{ options }</select>
        );
      }
    }
  });

  return Selection;
});