define(
  [
    'react',
    'mailpoet',
    'classnames',
    'react-router',
    'react-checkbox-group'
  ],
  function(
    React,
    MailPoet,
    classNames,
    Router,
    CheckboxGroup
  ) {


    var FormFieldSelect = React.createClass({
      render: function() {
        var options =
          Object.keys(this.props.field.values).map(function(value, index) {
            return (
              <option
                key={ 'option-' + index }
                value={ value }>
                { this.props.field.values[value] }
              </option>
            );
          }.bind(this)
        );

        return (
          <select
            name={ this.props.field.name }
            id={ 'field_'+this.props.field.name }
            value={ this.props.item[this.props.field.name] }
            onChange={ this.props.onValueChange }>
            {options}
          </select>
        );
      }
    });

    var FormFieldRadio = React.createClass({
      render: function() {
        var selected_value = this.props.item[this.props.field.name];
        var count = Object.keys(this.props.field.values).length;

        var options = Object.keys(this.props.field.values).map(
          function(value, index) {
            return (
              <p key={ 'radio-' + index }>
                <label>
                  <input
                    type="radio"
                    checked={ selected_value === value }
                    value={ value }
                    onChange={ this.props.onValueChange }
                    name={ this.props.field.name } />
                  &nbsp;{ this.props.field.values[value] }
                </label>
              </p>
            );
          }.bind(this)
        );

        return (
          <div>
            { options }
          </div>
        );
      }
    });

    var FormFieldCheckbox = React.createClass({
      render: function() {
        var selected_values = this.props.item[this.props.field.name] || '';
        if(
          selected_values !== undefined
          && selected_values.constructor !== Array
        ) {
          selected_values = selected_values.split(';').map(function(value) {
            return value.trim();
          });
        }
        var count = Object.keys(this.props.field.values).length;

        var options = Object.keys(this.props.field.values).map(
          function(value, index) {
            return (
              <p key={ 'checkbox-' + index }>
                <label>
                  <input type="checkbox" value={ value } />
                  &nbsp;{ this.props.field.values[value] }
                </label>
              </p>
            );
          }.bind(this)
        );

        return (
          <CheckboxGroup
            name={ this.props.field.name }
            value={ selected_values }
            ref={ this.props.field.name }
            onChange={ this.handleValueChange }>
            { options }
          </CheckboxGroup>
        );
      },
      handleValueChange: function() {
        var field = this.props.field.name;
        var group = this.refs[field];
        var selected_values = [];

        if(group !== undefined) {
          selected_values = group.getCheckedValues();
        }

        return this.props.onValueChange({
          target: {
            name: field,
            value: selected_values.join(';')
          }
        });
      }
    });

    var FormFieldText = React.createClass({
      render: function() {
        return (
          <input
            type="text"
            className="regular-text"
            name={ this.props.field.name }
            id={ 'field_'+this.props.field.name }
            value={ this.props.item[this.props.field.name] }
            onChange={ this.props.onValueChange } />
        );
      }
    });

    var FormFieldTextarea = React.createClass({
      render: function() {
        return (
          <textarea
            type="text"
            className="regular-text"
            name={ this.props.field.name }
            id={ 'field_'+this.props.field.name }
            value={ this.props.item[this.props.field.name] }
            onChange={ this.props.onValueChange } />
        );
      }
    });

    var FormField = React.createClass({
      render: function() {

        var description = false;
        if(this.props.field.description) {
          description = (
            <p className="description">{ this.props.field.description }</p>
          );
        }

        var field = false;

        switch(this.props.field.type) {
          case 'text':
            field = (<FormFieldText {...this.props} />);
          break;

          case 'textarea':
            field = (<FormFieldTextarea {...this.props} />);
          break;

          case 'select':
            field = (<FormFieldSelect {...this.props} />);
          break;

          case 'radio':
            field = (<FormFieldRadio {...this.props} />);
          break;

          case 'checkbox':
            field = (<FormFieldCheckbox {...this.props} />);
          break;
        }

        return (
          <tr>
            <th scope="row">
              <label
                htmlFor={ 'field_'+this.props.field.name }
              >{ this.props.field.label }</label>
            </th>
            <td>
              { field }
              { description }
            </td>
          </tr>
        );
      }
    });

    var Form = React.createClass({
      mixins: [
        Router.Navigation
      ],
      getInitialState: function() {
        return {
          loading: false,
          errors: [],
          item: {}
        };
      },
      componentDidMount: function() {
        if(this.props.params.id !== undefined) {
          if(this.isMounted()) {
            this.loadItem(this.props.params.id);
          }
        }
      },
      componentWillReceiveProps: function(props) {
        if(props.params.id === undefined) {
          this.setState({
            loading: false,
            item: {}
          });
          this.refs.form.getDOMNode().reset();
        } else {
          this.loadItem(props.params.id);
        }
      },
      loadItem: function(id) {
        this.setState({ loading: true });

        MailPoet.Ajax.post({
          endpoint: this.props.endpoint,
          action: 'get',
          data: { id: id }
        }).done(function(response) {
          if(response === false) {
            this.setState({
              loading: false,
              item: {}
            }, function() {
              this.transitionTo('/new');
            }.bind(this));
          } else {
            this.setState({
              loading: false,
              item: response
            });
          }
        }.bind(this));
      },
      handleSubmit: function(e) {
        e.preventDefault();

        this.setState({ loading: true });

        MailPoet.Ajax.post({
          endpoint: this.props.endpoint,
          action: 'save',
          data: this.state.item
        }).done(function(response) {
          this.setState({ loading: false });

          if(response === true) {
            this.transitionTo('/');
            if(this.props.params.id !== undefined) {
              this.props.messages['updated']();
            } else {
              this.props.messages['created']();
            }
          } else {
            if(response === false) {
              // unknown error occurred
            } else {
              this.setState({ errors: response });
            }
          }
        }.bind(this));
      },
      handleValueChange: function(e) {
        var item = this.state.item,
          field = e.target.name;

        item[field] = e.target.value;

        this.setState({
          item: item
        });
        return true;
      },
      render: function() {
        var errors = this.state.errors.map(function(error, index) {
          return (
            <p key={ 'error-'+index } className="mailpoet_error">
              { error }
            </p>
          );
        });

        var formClasses = classNames(
          { 'mailpoet_form_loading': this.state.loading }
        );

        var fields = this.props.fields.map(function(field, index) {
          return (
            <FormField
              field={ field }
              item={ this.state.item }
              onValueChange={ this.handleValueChange }
              key={ 'field-'+index } />
          );
        }.bind(this));

        return (
          <form
            ref="form"
            className={ formClasses }
            onSubmit={ this.handleSubmit }>

            { errors }

            <table className="form-table">
              <tbody>
                {fields}
              </tbody>
            </table>

            <input
              className="button button-primary"
              type="submit"
              value="Save"
              disabled={this.state.loading} />
          </form>
        );
      }
    });

    return Form;
  }
);