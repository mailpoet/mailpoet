define([
  'react',
  'react-dom',
  'jquery',
  'select2',
],
(
  React,
  ReactDOM,
  jQuery
) => {
  const Selection = React.createClass({
    getInitialState: function () {
      return {
        items: [],
        select2: false,
      };
    },
    componentWillMount: function () {
      this.loadCachedItems();
    },
    allowMultipleValues: function () {
      return (this.props.field.multiple === true);
    },
    isSelect2Initialized: function () {
      return (this.state.select2 === true);
    },
    componentDidMount: function () {
      if (this.allowMultipleValues()) {
        this.setupSelect2();
      }
    },
    componentDidUpdate: function (prevProps) {
      if (
        (this.props.item !== undefined && prevProps.item !== undefined)
        && (this.props.item.id !== prevProps.item.id)
      ) {
        jQuery(`#${this.refs.select.id}`)
          .val(this.getSelectedValues())
          .trigger('change');
      }
    },
    componentWillUnmount: function () {
      if (this.allowMultipleValues()) {
        this.destroySelect2();
      }
    },
    destroySelect2: function () {
      if (this.isSelect2Initialized()) {
        jQuery(`#${this.refs.select.id}`).select2('destroy');
      }
    },
    setupSelect2: function () {
      if (this.isSelect2Initialized()) {
        return;
      }

      const select2 = jQuery(`#${this.refs.select.id}`).select2({
        width: (this.props.width || ''),
        templateResult: function (item) {
          if (item.element && item.element.selected) {
            return null;
          } else if (item.title) {
            return item.title;
          }
          return item.text;
        },
      });

      let hasRemoved = false;
      select2.on('select2:unselecting', () => {
        hasRemoved = true;
      });
      select2.on('select2:opening', (e) => {
        if (hasRemoved === true) {
          hasRemoved = false;
          e.preventDefault();
        }
      });

      select2.on('change', this.handleChange);

      this.setState({ select2: true });
    },
    getSelectedValues: function () {
      if (this.props.field.selected !== undefined) {
        return this.props.field.selected(this.props.item);
      } else if (this.props.item !== undefined && this.props.field.name !== undefined) {
        if (this.allowMultipleValues()) {
          if (Array.isArray(this.props.item[this.props.field.name])) {
            return this.props.item[this.props.field.name].map(item => item.id);
          }
        } else {
          return this.props.item[this.props.field.name];
        }
      }
      return null;
    },
    loadCachedItems: function () {
      if (typeof (window[`mailpoet_${this.props.field.endpoint}`]) !== 'undefined') {
        let items = window[`mailpoet_${this.props.field.endpoint}`];


        if (this.props.field.filter !== undefined) {
          items = items.filter(this.props.field.filter);
        }

        this.setState({
          items: items,
        });
      }
    },
    handleChange: function (e) {
      let value;
      if (this.props.onValueChange !== undefined) {
        if (this.props.field.multiple) {
          value = jQuery(`#${this.refs.select.id}`).val();
        } else {
          value = e.target.value;
        }
        const transformedValue = this.transformChangedValue(value);
        this.props.onValueChange({
          target: {
            value: transformedValue,
            name: this.props.field.name,
          },
        });
      }
    },
    getLabel: function (item) {
      if (this.props.field.getLabel !== undefined) {
        return this.props.field.getLabel(item, this.props.item);
      }
      return item.name;
    },
    getSearchLabel: function (item) {
      if (this.props.field.getSearchLabel !== undefined) {
        return this.props.field.getSearchLabel(item, this.props.item);
      }
      return null;
    },
    getValue: function (item) {
      if (this.props.field.getValue !== undefined) {
        return this.props.field.getValue(item, this.props.item);
      }
      return item.id;
    },
    // When it's impossible to represent the desired value in DOM,
    // this function may be used to transform the placeholder value into
    // desired value.
    transformChangedValue: function (value) {
      if (typeof this.props.field.transformChangedValue === 'function') {
        return this.props.field.transformChangedValue.call(this, value);
      }
      return value;
    },
    render: function () {
      const options = this.state.items.map((item, index) => {
        const label = this.getLabel(item);
        const searchLabel = this.getSearchLabel(item);
        const value = this.getValue(item);

        return (
          <option
            key={`option-${index}`}
            value={value}
            title={searchLabel}
          >
            { label }
          </option>
        );
      });

      return (
        <select
          id={this.props.field.id || this.props.field.name}
          ref="select"
          disabled={this.props.field.disabled}
          data-placeholder={this.props.field.placeholder}
          multiple={this.props.field.multiple}
          defaultValue={this.getSelectedValues()}
          {...this.props.field.validation}
        >{ options }</select>
      );
    },
  });

  return Selection;
});
