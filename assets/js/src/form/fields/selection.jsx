define([
  'react',
  'react-dom',
  'jquery',
  'select2'
],
function(
  React,
  ReactDOM,
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
      this.setupSelect2();
    },
    componentDidUpdate: function(prevProps, prevState) {
      if(
        (this.props.item !== undefined && prevProps.item !== undefined)
        && (this.props.item.id !== prevProps.item.id)
      ) {
        jQuery('#'+this.refs.select.id)
          .val(this.props.item[this.props.field.name])
          .trigger('change');
      }
    },
    setupSelect2: function() {
      if(
          !this.props.field.multiple
          || this.state.initialized === true
          || this.refs.select === undefined
        ) {
        return;
      }

      var select2 = jQuery('#'+this.refs.select.id).select2({
        width: (this.props.width || ''),
        templateResult: function(item) {
          if(item.element && item.element.selected) {
            return null;
          } else {
            return item.text;
          }
        }
      });

      var hasRemoved = false;
      select2.on('select2:unselecting', function(e) {
        hasRemoved = true;
      });
      select2.on('select2:opening', function(e) {
        if(hasRemoved === true) {
          hasRemoved = false;
          e.preventDefault();
        }
      });

      select2.on('change', this.handleChange);

      select2.select2(
        'val',
        this.props.item[this.props.field.name]
      );

      this.setState({ initialized: true });
    },
    loadCachedItems: function() {
      if(typeof(window['mailpoet_'+this.props.field.endpoint]) !== 'undefined') {
        var items = window['mailpoet_'+this.props.field.endpoint];

        if(this.props.field['filter'] !== undefined) {
          items = items.filter(this.props.field.filter);
        }

        this.setState({
          items: items
        });
      }
    },
    handleChange: function(e) {
      if(this.props.onValueChange !== undefined) {
        if(this.props.field.multiple) {
          value = jQuery('#'+this.refs.select.id).val();
        } else {
          value = e.target.value;
        }
        this.props.onValueChange({
         target: {
            value: value,
            name: this.props.field.name
          }
        });
      }
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

      var default_value = (
        (this.props.item !== undefined && this.props.field.name !== undefined)
        ? this.props.item[this.props.field.name]
        : null
      );

      return (
        <select
          id={ this.props.field.id || this.props.field.name }
          ref="select"
          placeholder={ this.props.field.placeholder }
          multiple={ this.props.field.multiple }
          defaultValue={ default_value }
          {...this.props.field.validation}
        >{ options }</select>
      );
    }
  });

  return Selection;
});