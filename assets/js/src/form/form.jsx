define(
  [
    'react',
    'mailpoet',
    'classnames',
    'react-router'
  ],
  function(
    React,
    MailPoet,
    classNames,
    Router
  ) {

    var FormField = React.createClass({
      render: function() {
        return (
          <tr>
            <th scope="row">
              <label
                htmlFor={ 'field_'+this.props.field.name }
              >{ this.props.field.label }</label>
            </th>
            <td>
              <input
                type="text"
                name={ this.props.field.name }
                id={ 'field_'+this.props.field.name }
                value={ this.props.item[this.props.field.name] }
                onChange={ this.props.onValueChange } />
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
        } else {
          this.loadItem(props.params.id);
        }
      },
      loadItem: function(id) {
        this.setState({ loading: true });

        MailPoet.Ajax.post({
          endpoint: 'subscribers',
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
          endpoint: 'subscribers',
          action: 'save',
          data: this.state.item
        }).done(function(response) {
          this.setState({ loading: false });

          if(response === true) {
            this.transitionTo('/');
            if(this.props.params.id !== undefined) {
              MailPoet.Notice.success('Subscriber succesfully updated!');
            } else {
              MailPoet.Notice.success('Subscriber succesfully added!');
            }

          } else {
            this.setState({ errors: response });
          }
        }.bind(this));
      },
      handleValueChange: function(e) {
        var item = this.state.item;
        item[e.target.name] = e.target.value;
        this.setState({
          item: item
        });
        return true;
      },
      render: function() {
        var errors = this.state.errors.map(function(error, index) {
          return (
            <p key={'error-'+index} className="mailpoet_error">{ error }</p>
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