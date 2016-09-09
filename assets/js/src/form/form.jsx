define(
  [
    'react',
    'mailpoet',
    'classnames',
    'react-router',
    'form/fields/field.jsx'
  ],
  function(
    React,
    MailPoet,
    classNames,
    Router,
    FormField
  ) {

    var Form = React.createClass({
      contextTypes: {
        router: React.PropTypes.object.isRequired
      },
      getDefaultProps: function() {
        return {
          params: {},
        };
      },
      getInitialState: function() {
        return {
          loading: false,
          errors: [],
          item: {}
        };
      },
      getValues: function() {
        return this.props.item ? this.props.item : this.state.item;
      },
      getErrors: function() {
        return this.props.errors ? this.props.errors : this.state.errors;
      },
      componentDidMount: function() {
        if(this.isMounted()) {
          if(this.props.params.id !== undefined) {
            this.loadItem(this.props.params.id);
          } else {
            this.setState({
              item: jQuery('.mailpoet_form').serializeObject()
            });
          }
        }
      },
      componentWillReceiveProps: function(props) {
        if(props.params.id === undefined) {
          this.setState({
            loading: false,
            item: {}
          });
          if (props.item === undefined) {
            this.refs.form.reset();
          }
        } else {
          this.loadItem(props.params.id);
        }
      },
      loadItem: function(id) {
        this.setState({ loading: true });

        MailPoet.Ajax.post({
          endpoint: this.props.endpoint,
          action: 'get',
          data: {
            id: id
          }
        }).done((response) => {
          this.setState({
            loading: false,
            item: response.data
          });
        }).fail((response) => {
          this.setState({
            loading: false,
            item: {}
          }, function() {
            this.context.router.push('/new');
          });
        });
      },
      handleSubmit: function(e) {
        e.preventDefault();

        // handle validation
        if(this.props.isValid !== undefined) {
          if(this.props.isValid() === false) {
            return;
          }
        }

        this.setState({ loading: true });

        // only get values from displayed fields
        var item = {};
        this.props.fields.map(function(field) {
          if(field['fields'] !== undefined) {
            field.fields.map(function(subfield) {
              item[subfield.name] = this.state.item[subfield.name];
            }.bind(this));
          } else {
            item[field.name] = this.state.item[field.name];
          }
        }.bind(this));
        // set id if specified
        if(this.props.params.id !== undefined) {
          item.id = this.props.params.id;
        }

        MailPoet.Ajax.post({
          endpoint: this.props.endpoint,
          action: 'save',
          data: item
        }).always(() => {
          this.setState({ loading: false });
        }).done((response) => {
          if(this.props.onSuccess !== undefined) {
            this.props.onSuccess();
          } else {
            this.context.router.push('/');
          }

          if(this.props.params.id !== undefined) {
            this.props.messages.onUpdate();
          } else {
            this.props.messages.onCreate();
          }
        }).fail((response) => {
          if(response.errors.length > 0) {
            this.setState({ errors: response.errors });
          }
        });
      },
      handleValueChange: function(e) {
        if (this.props.onChange) {
          return this.props.onChange(e);
        } else {
          var item = this.state.item,
            field = e.target.name;

          item[field] = e.target.value;

          this.setState({
            item: item
          });
          return true;
        }
      },
      render: function() {
        if(this.getErrors() !== undefined) {
          var errors = this.getErrors().map(function(error, index) {
            return (
              <p key={ 'error-'+index } className="mailpoet_error">
                { error.message }
              </p>
            );
          });
        }

        var formClasses = classNames(
          'mailpoet_form',
          { 'mailpoet_form_loading': this.state.loading || this.props.loading }
        );

        var beforeFormContent = false;
        var afterFormContent = false;

        if (this.props.beforeFormContent !== undefined) {
          beforeFormContent = this.props.beforeFormContent(this.getValues());
        }

        if (this.props.afterFormContent !== undefined) {
          afterFormContent = this.props.afterFormContent(this.getValues());
        }

        var fields = this.props.fields.map(function(field, i) {
          return (
            <FormField
              field={ field }
              item={ this.getValues() }
              onValueChange={ this.handleValueChange }
              key={ 'field-'+i } />
          );
        }.bind(this));

        var actions = false;
        if(this.props.children) {
          actions = this.props.children;
        } else {
          actions = (
            <input
              className="button button-primary"
              type="submit"
              value={MailPoet.I18n.t('save')}
              disabled={this.state.loading} />
          );
        }

        return (
          <div>
            { beforeFormContent }
            <form
              id={ this.props.id }
              ref="form"
              className={ formClasses }
              onSubmit={
                (this.props.onSubmit !== undefined)
                ? this.props.onSubmit
                : this.handleSubmit
              }
            >
              { errors }

              <table className="form-table">
                <tbody>
                  {fields}
                </tbody>
              </table>

              { actions }
            </form>
            { afterFormContent }
          </div>
        );
      }
    });

    return Form;
  }
);
