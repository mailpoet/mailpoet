define([
  'react',
  'jquery'
],
function(
  React,
  jQuery
) {
  var Selection = React.createClass({
    getInitialState: function() {
      return {
        loading: false,
        items: [],
        selected: []
      }
    },
    componentWillMount: function() {
      this.loadCachedItems();
    },
    componentDidMount: function() {
      if(this.props.select2) {
        jQuery('#'+this.props.id).select2({
          width: '25em'
        });
      }
    },
    loadCachedItems: function() {
      if(typeof(window['mailpoet_'+this.props.endpoint]) !== 'undefined') {
        var items = window['mailpoet_'+this.props.endpoint];
        this.setState({
          items: items
        });
      }
    },
    handleChange: function() {
      this.setState({
        selected: jQuery('#'+this.props.id).val()
      });
    },
    getSelected: function() {
      return this.state.selected;
    },
    render: function() {
      var options = this.state.items.map(function(item, index) {
        return (
          <option
            key={ 'action-' + index }
            value={ item.id }>
            { item.name }
          </option>
        );
      });

      return (
        <select
          ref="selection"
          id={ this.props.id || 'mailpoet_field_selection'}
          placeholder={ this.props.placeholder }
          multiple={ this.props.multiple }
        >
          { options }
        </select>
      );
    }
  });

  return Selection;
});