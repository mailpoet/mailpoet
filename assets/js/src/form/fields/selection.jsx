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
      };
    },
    componentWillMount: function() {
      this.loadCachedItems();
    },
    componentDidMount: function() {
      this.setupSelect2();
    },
    componentDidUpdate: function(prevProps, prevState) {
      if(
        (this.props.item !== undefined && prevProps.item !== undefined)
        && (this.props.item.id !== prevProps.item.id)
      ) {
        jQuery('#'+this.refs.select.id)
          .val(this.getSelectedValues())
          .trigger('change');
      }
    },
    componentWillUnmount: function() {
      if(
          !this.props.field.multiple
          || this.state.initialized === false
          || this.refs.select === undefined
        ) {
        return;
      }

      jQuery('#'+this.refs.select.id).select2('destroy');
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
            if(item.title) {
              return item.title;
            } else {
              return item.text;
            }
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

      this.setState({ initialized: true });
    },
    getSelectedValues: function() {
      if(this.props.field['selected'] !== undefined) {
        return this.props.field['selected'](this.props.item);
      } else if(this.props.item !== undefined && this.props.field.name !== undefined) {
        return this.props.item[this.props.field.name];
      } else {
        return null;
      }
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
    getLabel: function(item) {
      if(this.props.field['getLabel'] !== undefined) {
        return this.props.field.getLabel(item, this.props.item);
      }
      return item.name;
    },
    getSearchLabel: function(item) {
      if(this.props.field['getSearchLabel'] !== undefined) {
        return this.props.field.getSearchLabel(item, this.props.item);
      }
      return null;
    },
    getValue: function(item) {
      if(this.props.field['getValue'] !== undefined) {
        return this.props.field.getValue(item, this.props.item);
      }
      return item.id;
    },
    render: function() {
      const options = this.state.items.map((item, index) => {
        let label = this.getLabel(item);
        let searchLabel = this.getSearchLabel(item);
        let value = this.getValue(item);

        return (
          <option
            key={ 'option-'+index }
            value={ value }
            title={ searchLabel }
          >
            { label }
          </option>
        );
      });

      return (
        <select
          id={ this.props.field.id || this.props.field.name }
          ref="select"
          data-placeholder={ this.props.field.placeholder }
          multiple={ this.props.field.multiple }
          defaultValue={ this.getSelectedValues() }
          {...this.props.field.validation}
        >{ options }</select>
      );
    }
  });

  return Selection;
});
