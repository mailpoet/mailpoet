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
      jQuery('#'+this.props.id).select2({
        width: '25em'
      });
    },
    loadCachedItems: function() {
      if(typeof(window['mailpoet_'+this.props.endpoint]) !== 'undefined') {
        var items = window['mailpoet_'+this.props.endpoint];
        this.setState({
          items: items
        });
      }
    },
    loadItems: function() {
      this.setState({ loading: true });

      MailPoet.Ajax.post({
        endpoint: this.props.endpoint,
        action: 'listing',
        data: {
          'offset': 0,
          'limit': 100,
          'search': '',
          'sort_by': 'name',
          'sort_order': 'asc'
        }
      })
      .done(function(response) {
        if(this.isMounted()) {
          if(response === false) {
            this.setState({
              loading: false,
              items: []
            });
          } else {
            this.setState({
              loading: false,
              items: response.items
            });
          }
        }
      }.bind(this));
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
          id={ this.props.id }
          value={ this.state.selected }
          onChange={ this.handleChange }
          placeholder={ this.props.placeholder }
          multiple
        >
          { options }
        </select>
      );
    }
  });

  return Selection;
});