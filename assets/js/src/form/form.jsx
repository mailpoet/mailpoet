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
      mixins: [
        Router.History
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
          this.refs.form.reset();
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
              this.history.pushState(null, '/new');
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
            this.history.pushState(null, '/');
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
          'mailpoet_form',
          { 'mailpoet_form_loading': this.state.loading }
        );

        var fields = this.props.fields.map(function(field, i) {
          return (
            <FormField
              field={ field }
              item={ this.state.item }
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
              value="Save"
              disabled={this.state.loading} />
          );
        }

        return (
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
        );
      }
    });

    return Form;
  }
);