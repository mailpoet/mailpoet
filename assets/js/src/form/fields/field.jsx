define([
  'react',
  'form/fields/text.jsx',
  'form/fields/textarea.jsx',
  'form/fields/select.jsx',
  'form/fields/radio.jsx',
  'form/fields/checkbox.jsx'
],
function(
  React,
  FormFieldText,
  FormFieldTextarea,
  FormFieldSelect,
  FormFieldRadio,
  FormFieldCheckbox
) {
  var FormField = React.createClass({
    renderField: function(data, inline = true) {
      var description = false;
      if(data.field.description) {
        description = (
          <p className="description">{ data.field.description }</p>
        );
      }

      var field = false;

      if(data.field['field'] !== undefined) {
        field = data.field.field;
      } else{
        switch(data.field.type) {
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
        }
      }

      if(inline === true) {
        return (
          <span>
            { field }
            { description }
          </span>
        );
      } else {
        return (
          <div>
            { field }
            { description }
          </div>
        );
      }
    },
    render: function() {
      var field = false;

      if(this.props.field['fields'] !== undefined) {
        field = this.props.field.fields.map(function(subfield) {
          return this.renderField({
            field: subfield,
            item: this.props.item
          });
        }.bind(this));
      } else {
        field = this.renderField(this.props);
      }

      var tip = false;
      if(this.props.field.tip) {
        tip = (
          <p className="description">{ this.props.field.tip }</p>
        );
      }

      return (
        <tr>
          <th scope="row">
            <label
              htmlFor={ 'field_'+this.props.field.name }
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
  });

  return FormField;
});