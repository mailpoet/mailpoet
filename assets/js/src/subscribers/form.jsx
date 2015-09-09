define(
  [
    'react',
    'react-router',
    'jquery',
    'mailpoet',
    'classnames',
  ],
  function(
    React,
    Router,
    jQuery,
    MailPoet,
    classNames
  ) {

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
      handleItemChange: function(e) {
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

        return (
          <form
            className={ formClasses }
            onSubmit={ this.handleSubmit }>
            { errors }
            <p>
              <input
                type="text"
                placeholder="Email"
                name="email"
                value={ this.state.item.email }
                onChange={ this.handleItemChange }/>
            </p>
            <p>
              <input
                type="text"
                placeholder="First name"
                name="first_name"
                value={ this.state.item.first_name }
                onChange={ this.handleItemChange }/>
            </p>
            <p>
              <input
                type="text"
                placeholder="Last name"
                name="last_name"
                value={ this.state.item.last_name }
                onChange={ this.handleItemChange }/>
            </p>
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