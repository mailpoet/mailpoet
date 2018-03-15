define(
  [
    'react',
    'mailpoet',
    'classnames',
    'react-router',
    'form/fields/field.jsx',
    'jquery',
  ],
  (
    React,
    MailPoet,
    classNames,
    Router,
    FormField,
    jQuery
  ) => {
    const Form = React.createClass({
      contextTypes: {
        router: React.PropTypes.object.isRequired,
      },
      getDefaultProps: function getDefaultProps() {
        return {
          params: {},
        };
      },
      getInitialState: function getInitialState() {
        return {
          loading: false,
          errors: [],
          item: {},
        };
      },
      getValues: function getValues() {
        return this.props.item ? this.props.item : this.state.item;
      },
      getErrors: function getErrors() {
        return this.props.errors ? this.props.errors : this.state.errors;
      },
      componentDidMount: function componentDidMount() {
        if (this.isMounted()) {
          if (this.props.params.id !== undefined) {
            this.loadItem(this.props.params.id);
          } else {
            setImmediate(() => {
              this.setState({
                item: jQuery('.mailpoet_form').mailpoetSerializeObject(),
              });
            });
          }
        }
      },
      componentWillReceiveProps: function componentWillReceiveProps(props) {
        if (props.params.id === undefined) {
          setImmediate(() => {
            this.setState({
              loading: false,
              item: {},
            });
          });
          if (props.item === undefined) {
            this.form.reset();
          }
        }
      },
      loadItem: function loadItem(id) {
        this.setState({ loading: true });

        MailPoet.Ajax.post({
          api_version: window.mailpoet_api_version,
          endpoint: this.props.endpoint,
          action: 'get',
          data: {
            id: id,
          },
        }).done((response) => {
          this.setState({
            loading: false,
            item: response.data,
          });
          if (typeof this.props.onItemLoad === 'function') {
            this.props.onItemLoad(response.data);
          }
        }).fail(() => {
          this.setState({
            loading: false,
            item: {},
          }, function failSetStateCallback() {
            this.context.router.push('/new');
          });
        });
      },
      handleSubmit: function handleSubmit(e) {
        e.preventDefault();

        // handle validation
        if (this.props.isValid !== undefined) {
          if (this.props.isValid() === false) {
            return;
          }
        }

        this.setState({ loading: true });

        // only get values from displayed fields
        const item = {};
        this.props.fields.forEach((field) => {
          if (field.fields !== undefined) {
            field.fields.forEach((subfield) => {
              item[subfield.name] = this.state.item[subfield.name];
            });
          } else {
            item[field.name] = this.state.item[field.name];
          }
        });
        // set id if specified
        if (this.props.params.id !== undefined) {
          item.id = this.props.params.id;
        }

        MailPoet.Ajax.post({
          api_version: window.mailpoet_api_version,
          endpoint: this.props.endpoint,
          action: 'save',
          data: item,
        }).always(() => {
          this.setState({ loading: false });
        }).done(() => {
          if (this.props.onSuccess !== undefined) {
            this.props.onSuccess();
          } else {
            this.context.router.push('/');
          }

          if (this.props.params.id !== undefined) {
            this.props.messages.onUpdate();
          } else {
            this.props.messages.onCreate();
          }
        }).fail((response) => {
          if (response.errors.length > 0) {
            this.setState({ errors: response.errors });
          }
        });
      },
      handleValueChange: function handleValueChange(e) {
        if (this.props.onChange) {
          return this.props.onChange(e);
        }
        const item = this.state.item;
        const field = e.target.name;

        item[field] = e.target.value;

        this.setState({
          item: item,
        });
        return true;
      },
      render: function render() {
        let errors;
        if (this.getErrors() !== undefined) {
          errors = this.getErrors().map((error, index) => (
            <div className="mailpoet_notice notice inline error is-dismissible" key={`error-${index}`}>
              <p>{ error.message }</p>
            </div>
          ));
        }

        const formClasses = classNames(
          'mailpoet_form',
          { mailpoet_form_loading: this.state.loading || this.props.loading }
        );

        let beforeFormContent = false;
        let afterFormContent = false;

        if (this.props.beforeFormContent !== undefined) {
          beforeFormContent = this.props.beforeFormContent(this.getValues());
        }

        if (this.props.afterFormContent !== undefined) {
          afterFormContent = this.props.afterFormContent(this.getValues());
        }

        const fields = this.props.fields.map((field, i) => {
          // Compose an onChange handler from the default and custom one
          let onValueChange = this.handleValueChange;
          if (field.onBeforeChange) {
            onValueChange = (e) => {
              field.onBeforeChange(e);
              return this.handleValueChange(e);
            };
          }

          return (
            <FormField
              field={field}
              item={this.getValues()}
              onValueChange={onValueChange}
              key={`field-${i}`}
            />
          );
        });

        let actions = false;
        if (this.props.children) {
          actions = this.props.children;
        } else {
          actions = (
            <input
              className="button button-primary"
              type="submit"
              value={MailPoet.I18n.t('save')}
              disabled={this.state.loading}
            />
          );
        }

        return (
          <div>
            { beforeFormContent }
            <form
              id={this.props.id}
              ref={(c) => { this.form = c; }}
              className={formClasses}
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
      },
    });

    return Form;
  }
);
