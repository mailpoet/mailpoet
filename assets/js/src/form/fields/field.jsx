define([
  'react',
  'form/fields/text.jsx',
  'form/fields/textarea.jsx',
  'form/fields/select.jsx',
  'form/fields/radio.jsx',
  'form/fields/checkbox.jsx',
  'form/fields/selection.jsx',
  'form/fields/date.jsx',
  'jquery',
],
(
  React,
  FormFieldText,
  FormFieldTextarea,
  FormFieldSelect,
  FormFieldRadio,
  FormFieldCheckbox,
  FormFieldSelection,
  FormFieldDate,
  jQuery
) => {
  const FormField = React.createClass({
    renderField: function (data, inline = false) {
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
    },
    render: function () {
      let field = false;

      if (this.props.field.fields !== undefined) {
        field = this.props.field.fields.map((subfield, index) => this.renderField({
          index: index,
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
        <tr>
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
    },
  });

  return FormField;
});
