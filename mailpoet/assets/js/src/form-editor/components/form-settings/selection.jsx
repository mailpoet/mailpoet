import { Component, createRef } from 'react';
import jQuery from 'jquery';
import _ from 'underscore';
import 'react-dom';
import 'select2';
import PropTypes from 'prop-types';
import { withBoundary } from 'common';

class Selection extends Component {
  constructor(props) {
    super(props);
    this.selectRef = createRef();
  }

  componentDidMount() {
    if (this.isSelect2Component()) {
      this.setupSelect2();
    }
  }

  componentDidUpdate(prevProps) {
    const { field, item = undefined } = this.props;
    if (
      item !== undefined &&
      prevProps.item !== undefined &&
      item.id !== prevProps.item.id
    ) {
      jQuery(`#${this.selectRef.current.id}`)
        .val(this.getSelectedValues())
        .trigger('change');
    }

    // After change data in store this component didn't call trigger for change
    // It happened only when component allowed multipleValues
    // Following lines are modified lines on top for multipeValues
    if (
      item !== undefined &&
      prevProps.item !== undefined &&
      this.allowMultipleValues() &&
      _.isArray(item[field.name]) &&
      !_.isEqual(item[field.name], prevProps.item[field.name])
    ) {
      jQuery(`#${this.selectRef.current.id}`)
        .val(this.getSelectedValues())
        .trigger('change');
    }

    if (
      this.isSelect2Initialized() &&
      this.getFieldId(this.props) !== this.getFieldId(prevProps) &&
      field.resetSelect2OnUpdate !== undefined
    ) {
      this.resetSelect2();
    }
  }

  componentWillUnmount() {
    if (this.isSelect2Component()) {
      this.destroySelect2();
    }
  }

  getFieldId = (data) => {
    const props = data || this.props;
    return props.field.id || props.field.name;
  };

  getSelectedValues = () => {
    const { field, item = undefined } = this.props;
    if (field.selected !== undefined) {
      return field.selected(item);
    }
    if (item !== undefined && field.name !== undefined) {
      if (this.allowMultipleValues()) {
        if (_.isArray(item[field.name])) {
          return item[field.name].map((it) => it.id);
        }
      } else {
        return item[field.name];
      }
    }
    return null;
  };

  getItems = () => {
    const { field } = this.props;
    let items;
    if (typeof window[`mailpoet_${field.endpoint}`] !== 'undefined') {
      items = window[`mailpoet_${field.endpoint}`];
    } else if (field.values !== undefined) {
      items = field.values;
    }

    if (_.isArray(items)) {
      if (field.filter !== undefined) {
        items = items.filter(field.filter);
      }
    }

    return items;
  };

  getLabel = (item) => {
    const { field, item: propsItem = undefined } = this.props;
    if (field.getLabel !== undefined) {
      return field.getLabel(item, propsItem);
    }
    return item.name;
  };

  getSearchLabel = (item) => {
    const { field, item: propsItem = undefined } = this.props;
    if (field.getSearchLabel !== undefined) {
      return field.getSearchLabel(item, propsItem);
    }
    return null;
  };

  getValue = (item) => {
    const { field, item: propsItem = undefined } = this.props;
    if (field.getValue !== undefined) {
      return field.getValue(item, propsItem);
    }
    return item.id;
  };

  setupSelect2 = () => {
    if (this.isSelect2Initialized()) {
      return;
    }

    const {
      field,
      disabled = false,
      dropDownParent = undefined,
      width = '',
    } = this.props;

    let select2Options = {
      disabled: disabled || false,
      width: width || '',
      placeholder: {
        id: '', // the value of the option
        text: field.placeholder,
      },
      templateResult: function templateResult(item) {
        if (item.element && item.element.selected) {
          return null;
        }
        if (item.title) {
          return item.title;
        }
        return item.text;
      },
    };
    if (dropDownParent) {
      select2Options.dropdownParent = jQuery(dropDownParent);
    }

    const remoteQuery = field.remoteQuery || null;
    if (remoteQuery) {
      select2Options = Object.assign(select2Options, {
        ajax: {
          url: window.ajaxurl,
          type: 'POST',
          dataType: 'json',
          data: function data(params) {
            return {
              action: 'mailpoet',
              api_version: window.mailpoet_api_version,
              token: window.mailpoet_token,
              endpoint: remoteQuery.endpoint,
              method: remoteQuery.method,
              data: Object.assign(remoteQuery.data, { query: params.term }),
            };
          },
          processResults: function processResults(response) {
            let results;
            if (!_.has(response, 'data')) {
              results = [];
            } else {
              results = response.data.map((item) => {
                const id = item.id || item.value;
                const text = item.name || item.text;
                return { id, text };
              });
            }
            return { results };
          },
        },
        minimumInputLength: remoteQuery.minimumInputLength || 2,
      });
    }

    if (field.extendSelect2Options !== undefined) {
      select2Options = Object.assign(
        select2Options,
        field.extendSelect2Options,
      );
    }

    const select2 = jQuery(`#${this.selectRef.current.id}`).select2(
      select2Options,
    );

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
  };

  resetSelect2 = () => {
    this.destroySelect2();
    this.setupSelect2();
  };

  destroySelect2 = () => {
    if (this.isSelect2Initialized()) {
      jQuery(`#${this.selectRef.current.id}`).select2('destroy');
      this.cleanupAfterSelect2();
    }
  };

  cleanupAfterSelect2 = () => {
    // remove DOM elements created by Select2 that are not tracked by React
    jQuery(`#${this.selectRef.current.id}`)
      .find('option:not(.default)')
      .remove();

    // unbind events (https://select2.org/programmatic-control/methods#event-unbinding)
    jQuery(`#${this.selectRef.current.id}`)
      .off('select2:unselecting')
      .off('select2:opening');
  };

  allowMultipleValues = () => this.props.field.multiple === true;

  isSelect2Initialized = () =>
    jQuery(`#${this.selectRef.current.id}`).hasClass(
      'select2-hidden-accessible',
    ) === true;

  isSelect2Component = () =>
    this.allowMultipleValues() || this.props.field.forceSelect2;

  handleChange = (e) => {
    const { field, onValueChange = undefined } = this.props;

    if (typeof onValueChange !== 'function') return;

    const valueTextPair = jQuery(`#${this.selectRef.current.id}`)
      .children(':selected')
      .map(function element() {
        return { id: jQuery(this).val(), text: jQuery(this).text() };
      });
    const value = field.multiple
      ? _.pluck(valueTextPair, 'id')
      : _.pluck(valueTextPair, 'id').toString();
    const transformedValue = this.transformChangedValue(value, valueTextPair);

    onValueChange({
      target: {
        value: transformedValue,
        name: field.name,
        id: e.target.id,
      },
    });
  };

  // When it's impossible to represent the desired value in DOM,
  // this function may be used to transform the placeholder value into
  // desired value.
  transformChangedValue = (value, textValuePair) => {
    const { field } = this.props;
    if (typeof field.transformChangedValue === 'function') {
      return field.transformChangedValue.call(this, value, textValuePair);
    }
    return value;
  };

  insertEmptyOption = () => {
    const { field } = this.props;
    // https://select2.org/placeholders
    // For single selects only, in order for the placeholder value to appear,
    // we must have a blank <option> as the first option in the <select> control.
    if (this.allowMultipleValues()) return undefined;
    if (field.placeholder) {
      return (
        <option className="default" /> // eslint-disable-line jsx-a11y/control-has-associated-label
      );
    }
    return undefined;
  };

  render() {
    const { field } = this.props;

    const items = this.getItems(field);
    const selectedValues = this.getSelectedValues();
    const options = items.map((item) => {
      const label = this.getLabel(item);
      const searchLabel = this.getSearchLabel(item);
      const value = this.getValue(item);

      return (
        <option
          key={`option-${item.id}`}
          className="default"
          value={value}
          title={searchLabel}
        >
          {label}
        </option>
      );
    });

    return (
      <select
        id={this.getFieldId()}
        ref={this.selectRef}
        disabled={field.disabled}
        data-placeholder={field.placeholder}
        multiple={field.multiple}
        defaultValue={selectedValues}
        {...field.validation}
      >
        {this.insertEmptyOption()}
        {options}
      </select>
    );
  }
}

Selection.propTypes = {
  onValueChange: PropTypes.func,
  field: PropTypes.shape({
    name: PropTypes.string.isRequired,
    values: PropTypes.oneOfType([PropTypes.object, PropTypes.array]),
    getLabel: PropTypes.func,
    resetSelect2OnUpdate: PropTypes.bool,
    selected: PropTypes.func,
    endpoint: PropTypes.string,
    filter: PropTypes.func,
    getSearchLabel: PropTypes.func,
    getValue: PropTypes.func,
    placeholder: PropTypes.string,
    /* eslint-disable-next-line react/forbid-prop-types -- unknown type of the object */
    remoteQuery: PropTypes.object,
    /* eslint-disable-next-line react/forbid-prop-types -- unknown type of the object */
    extendSelect2Options: PropTypes.object,
    multiple: PropTypes.bool,
    forceSelect2: PropTypes.bool,
    transformChangedValue: PropTypes.func,
    disabled: PropTypes.bool,
    validation: PropTypes.shape({
      'data-parsley-required': PropTypes.bool,
      'data-parsley-required-message': PropTypes.string,
      'data-parsley-type': PropTypes.string,
      'data-parsley-errors-container': PropTypes.string,
      maxLength: PropTypes.number,
    }),
  }).isRequired,
  item: PropTypes.object, // eslint-disable-line react/forbid-prop-types
  disabled: PropTypes.bool,
  width: PropTypes.string,
  dropDownParent: PropTypes.string,
};

Selection.displayName = 'FormEditorSelection';
const SelectionWithBoundary = withBoundary(Selection);
export { SelectionWithBoundary as Selection };
