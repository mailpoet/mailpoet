import { Component } from 'react';
import { FormFieldText } from 'form/fields/text.jsx';
import jQuery from 'jquery';
import PropTypes from 'prop-types';
import classNames from 'classnames';

import { FormFieldTextarea } from 'form/fields/textarea.jsx';
import { FormFieldSelect } from 'form/fields/select.jsx';
import { FormFieldRadio } from 'form/fields/radio.jsx';
import { FormFieldCheckbox } from 'form/fields/checkbox.jsx';
import { Selection } from 'form/fields/selection.jsx';
import { FormFieldDate } from 'form/fields/date.jsx';
import { Heading } from 'common/typography/heading/heading';
import { FormFieldTokenField } from 'form/fields/tokenField';

class FormField extends Component {
  renderField = (data) => {
    let description = false;
    if (data.field.description) {
      description = <p className="description">{data.field.description}</p>;
    }

    let field;
    let dataField = data.field;

    if (data.field.field !== undefined) {
      dataField = jQuery.merge(dataField, data.field.field);
    }

    switch (dataField.type) {
      case 'text':
        field = (
          <FormFieldText
            onValueChange={data.onValueChange}
            field={data.field}
            item={data.item}
            automationId={data.automationId}
            inline={data.inline}
            description={data.description}
          />
        );
        break;

      case 'textarea':
        field = (
          <FormFieldTextarea
            onValueChange={data.onValueChange}
            field={data.field}
            item={data.item}
            automationId={data.automationId}
            inline={data.inline}
            description={data.description}
          />
        );
        break;

      case 'select':
        field = (
          <FormFieldSelect
            onValueChange={data.onValueChange}
            field={data.field}
            item={data.item}
            automationId={data.automationId}
            inline={data.inline}
            description={data.description}
          />
        );
        break;

      case 'radio':
        field = (
          <FormFieldRadio
            onValueChange={data.onValueChange}
            field={data.field}
            item={data.item}
            automationId={data.automationId}
            inline={data.inline}
            description={data.description}
          />
        );
        break;

      case 'checkbox':
        field = (
          <FormFieldCheckbox
            onValueChange={data.onValueChange}
            field={data.field}
            item={data.item}
            automationId={data.automationId}
            inline={data.inline}
            description={data.description}
          />
        );
        break;

      case 'selection':
        field = (
          <Selection
            key={`selection-field-${dataField.name}`}
            onValueChange={data.onValueChange}
            field={data.field}
            automationId={data.automationId}
            inline={data.inline}
            description={data.description}
            item={data.item}
          />
        );
        break;

      case 'date':
        field = (
          <FormFieldDate
            onValueChange={data.onValueChange}
            field={data.field}
            item={data.item}
            automationId={data.automationId}
            inline={data.inline}
            description={data.description}
          />
        );
        break;

      case 'reactComponent':
        field = (
          <data.field.component
            onValueChange={data.onValueChange}
            field={data.field}
            item={data.item}
            automationId={data.automationId}
            inline={data.inline}
            description={data.description}
          />
        );
        break;

      case 'tokenField':
        field = (
          <FormFieldTokenField
            onValueChange={data.onValueChange}
            field={data.field}
            item={data.item}
            automationId={data.automationId}
            description={data.description}
          />
        );
        break;

      case 'empty':
        break;

      default:
        field = 'invalid';
        break;
    }

    const isDisabled =
      typeof this.props.field.disabled === 'function'
        ? this.props.field.disabled(this.props.field)
        : this.props.field.disabled;

    const eventListeners = {
      ...(this.props.field.onWrapperClick
        ? { onClick: this.props.field.onWrapperClick }
        : {}),
    };

    return (
      <div
        className={classNames('mailpoet-form-field', {
          'mailpoet-form-field-disabled': isDisabled,
        })}
        key={`field-${data.index || 0}`}
        {...eventListeners}
      >
        {field}
        {description}
      </div>
    );
  };

  render() {
    let field = false;

    if (this.props.field.fields !== undefined) {
      field = this.props.field.fields.map((subfield, index) =>
        this.renderField({
          index,
          field: subfield,
          item: this.props.item,
          onValueChange: this.props.onValueChange || false,
        }),
      );
    } else {
      field = this.renderField(this.props);
    }

    let label = false;
    if (this.props.field.label) {
      label = (
        <Heading level={4}>
          <label htmlFor={`field_${this.props.field.name}`}>
            {this.props.field.label}
          </label>
        </Heading>
      );
    }

    let tip = false;
    if (this.props.field.tip) {
      tip = <p className="mailpoet-form-description">{this.props.field.tip}</p>;
    }

    return (
      <div
        className={`mailpoet-form-field-${this.props.field.name} form-field-row-${this.props.field.name}`}
      >
        {label}
        {tip}
        {field}
      </div>
    );
  }
}

FormField.propTypes = {
  onValueChange: PropTypes.func,
  field: PropTypes.shape({
    name: PropTypes.string.isRequired,
    values: PropTypes.objectOf(PropTypes.string),
    tip: PropTypes.oneOfType([PropTypes.array, PropTypes.string]),
    label: PropTypes.string,
    fields: PropTypes.arrayOf(PropTypes.object),
    description: PropTypes.string,
    onWrapperClick: PropTypes.func,
    disabled: PropTypes.oneOfType([PropTypes.func, PropTypes.bool]),
  }).isRequired,
  item: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
};

FormField.defaultProps = {
  onValueChange: function onValueChange() {
    // no-op
  },
};

export { FormField };
