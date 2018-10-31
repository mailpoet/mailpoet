import React from 'react';
import jQuery from 'jquery';
import _ from 'underscore';
import 'react-dom';
import 'select2';

class Selection extends React.Component {
  allowMultipleValues = () => {
    return (this.props.field.multiple === true);
  };

  isSelect2Initialized = () => {
    return (jQuery(`#${this.select.id}`).hasClass('select2-hidden-accessible') === true);
  };

  isSelect2Component = () => {
    return this.allowMultipleValues() || this.props.field.forceSelect2;
  };

  componentDidMount() {
    if (this.isSelect2Component()) {
      this.setupSelect2();
    }
  }

  componentDidUpdate(prevProps) {
    if ((this.props.item !== undefined && prevProps.item !== undefined)
      && (this.props.item.id !== prevProps.item.id)
    ) {
      jQuery(`#${this.select.id}`)
        .val(this.getSelectedValues())
        .trigger('change');
    }

    if (this.isSelect2Initialized() &&
      (this.getFieldId(this.props) !== this.getFieldId(prevProps)) &&
      this.props.field.resetSelect2OnUpdate !== undefined
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

  resetSelect2 = () => {
    this.destroySelect2();
    this.setupSelect2();
  };

  destroySelect2 = () => {
    if (this.isSelect2Initialized()) {
      jQuery(`#${this.select.id}`).select2('destroy');
      this.cleanupAfterSelect2();
    }
  };

  cleanupAfterSelect2 = () => {
    // remove DOM elements created by Select2 that are not tracked by React
    jQuery(`#${this.select.id}`)
      .find('option:not(.default)')
      .remove();

    // unbind events (https://select2.org/programmatic-control/methods#event-unbinding)
    jQuery(`#${this.select.id}`)
      .off('select2:unselecting')
      .off('select2:opening');
  };

  setupSelect2 = () => {
    if (this.isSelect2Initialized()) {
      return;
    }

    let select2Options = {
      disabled: this.props.disabled || false,
      width: (this.props.width || ''),
      placeholder: {
        id: '', // the value of the option
        text: this.props.field.placeholder,
      },
      templateResult: function templateResult(item) {
        if (item.element && item.element.selected) {
          return null;
        } else if (item.title) {
          return item.title;
        }
        return item.text;
      },
    };

    const remoteQuery = this.props.field.remoteQuery || null;
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
              data: Object.assign(
                remoteQuery.data,
                { query: params.term }
              ),
            };
          },
          processResults: function processResults(response) {
            return { results: (!_.has(response, 'data')) ?
              [] :
              response.data.map(item =>
                ({ id: item.id || item.value, text: item.name || item.text })
              ),
            };
          },
        },
        minimumInputLength: remoteQuery.minimumInputLength || 2,
      });
    }

    if (this.props.field.extendSelect2Options !== undefined) {
      select2Options = Object.assign(select2Options, this.props.field.extendSelect2Options);
    }

    const select2 = jQuery(`#${this.select.id}`).select2(select2Options);

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

  getSelectedValues = () => {
    if (this.props.field.selected !== undefined) {
      return this.props.field.selected(this.props.item);
    } else if (this.props.item !== undefined && this.props.field.name !== undefined) {
      if (this.allowMultipleValues()) {
        if (_.isArray(this.props.item[this.props.field.name])) {
          return this.props.item[this.props.field.name].map(item => item.id);
        }
      } else {
        return this.props.item[this.props.field.name];
      }
    }
    return null;
  };

  getItems = () => {
    let items;
    if (typeof (window[`mailpoet_${this.props.field.endpoint}`]) !== 'undefined') {
      items = window[`mailpoet_${this.props.field.endpoint}`];
    } else if (this.props.field.values !== undefined) {
      items = this.props.field.values;
    }

    if (_.isArray(items)) {
      if (this.props.field.filter !== undefined) {
        items = items.filter(this.props.field.filter);
      }
    }

    return items;
  };

  handleChange = (e) => {
    if (this.props.onValueChange === undefined) return;

    const valueTextPair = jQuery(`#${this.select.id}`).children(':selected').map(function element() {
      return { id: jQuery(this).val(), text: jQuery(this).text() };
    });
    const value = (this.props.field.multiple) ? _.pluck(valueTextPair, 'id') : _.pluck(valueTextPair, 'id').toString();
    const transformedValue = this.transformChangedValue(value, valueTextPair);

    this.props.onValueChange({
      target: {
        value: transformedValue,
        name: this.props.field.name,
        id: e.target.id,
      },
    });
  };

  getLabel = (item) => {
    if (this.props.field.getLabel !== undefined) {
      return this.props.field.getLabel(item, this.props.item);
    }
    return item.name;
  };

  getSearchLabel = (item) => {
    if (this.props.field.getSearchLabel !== undefined) {
      return this.props.field.getSearchLabel(item, this.props.item);
    }
    return null;
  };

  getValue = (item) => {
    if (this.props.field.getValue !== undefined) {
      return this.props.field.getValue(item, this.props.item);
    }
    return item.id;
  };

  // When it's impossible to represent the desired value in DOM,
  // this function may be used to transform the placeholder value into
  // desired value.
  transformChangedValue = (value, textValuePair) => {
    if (typeof this.props.field.transformChangedValue === 'function') {
      return this.props.field.transformChangedValue.call(this, value, textValuePair);
    }
    return value;
  };

  insertEmptyOption = () => {
    // https://select2.org/placeholders
    // For single selects only, in order for the placeholder value to appear,
    // we must have a blank <option> as the first option in the <select> control.
    if (this.allowMultipleValues()) return undefined;
    if (this.props.field.placeholder) return (<option className="default" />);
    return undefined;
  };

  render() {
    const items = this.getItems(this.props.field);
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
          { label }
        </option>
      );
    });

    return (
      <select
        id={this.getFieldId()}
        ref={(c) => { this.select = c; }}
        disabled={this.props.field.disabled}
        data-placeholder={this.props.field.placeholder}
        multiple={this.props.field.multiple}
        defaultValue={selectedValues}
        {...this.props.field.validation}
      >
        { this.insertEmptyOption() }
        { options }
      </select>
    );
  }
}

export default Selection;
