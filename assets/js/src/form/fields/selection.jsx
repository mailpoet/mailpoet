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

    },
    componentDidMount: function() {
      this.loadCachedItems();
    },
    setupSelect2: function() {
      if(this.props.field.select2 && Object.keys(this.props.item).length > 0) {
        console.log('do it!');
        jQuery('#'+this.props.field.id).select2({
          width: this.props.field.width
        }).select2(
          'val',
          this.props.item[this.props.field.name]
        ).on('change', this.handleChange);

        // set values
        /*jQuery('#'+this.props.field.id).select2(
          'val',
          this.props.item[this.props.field.name]
        );*/

        console.log(this.props.item[this.props.field.name]);
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

        this.setupSelect2();

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