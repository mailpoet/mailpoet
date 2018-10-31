import React from 'react';
import FormFieldText from 'form/fields/text.jsx';
import FormFieldTextarea from 'form/fields/textarea.jsx';
import FormFieldSelect from 'form/fields/select.jsx';
import FormFieldRadio from 'form/fields/radio.jsx';
import FormFieldCheckbox from 'form/fields/checkbox.jsx';
import FormFieldSelection from 'form/fields/selection.jsx';
import FormFieldDate from 'form/fields/date.jsx';
import jQuery from 'jquery';

class FormField extends React.Component {
  renderField = (data, inline = false) => {
    let description = false;
    if (data.field.description) {
      description = (
        <p className="description">{ data.field.description }</p>
      );
    }

    let field = false;
    let dataField = data.field;

    if (data.field.field !== undefined) {
      dataField = jQuery.merge(dataField, data.field.field);
    }

    switch (dataField.type) {
      case 'text':
        field = (<FormFieldText {...data} />);
        break;

      case 'textarea':
        field = (<FormFieldTextarea {...data} />);
        break;

      case 'select':
        field = (<FormFieldSelect {...data} />);
        break;

      case 'radio':
        field = (<FormFieldRadio {...data} />);
        break;

      case 'checkbox':
        field = (<FormFieldCheckbox {...data} />);
        break;

      case 'selection':
        field = (<FormFieldSelection {...data} />);
        break;

      case 'date':
        field = (<FormFieldDate {...data} />);
        break;

      case 'reactComponent':
        field = (<data.field.component {...data} />);
        break;

      default:
        field = 'invalid';
        break;
    }

    if (inline === true) {
      return (
        <span key={`field-${data.index || 0}`}>
          { field }
          { description }
        </span>
      );
    }
    return (
      <div key={`field-${data.index || 0}`}>
        { field }
        { description }
      </div>
    );
  };

  render() {
    let field = false;

    if (this.props.field.fields !== undefined) {
      field = this.props.field.fields.map((subfield, index) => this.renderField({
        index,
        field: subfield,
        item: this.props.item,
        onValueChange: this.props.onValueChange || false,
      }));
    } else {
      field = this.renderField(this.props);
    }

    let tip = false;
    if (this.props.field.tip) {
      tip = (
        <p className="description">{ this.props.field.tip }</p>
      );
    }

    return (
      <tr className={`form-field-row-${this.props.field.name}`}>
        <th scope="row">
          <label
            htmlFor={`field_${this.props.field.name}`}
          >
            { this.props.field.label }
            { tip }
          </label>
        </th>
        <td>
          { field }
        </td>
      </tr>
    );
  }
}

export default FormField;
