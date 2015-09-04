define(
  'subscribers_form',
  [
    'react',
    'react-router',
    'jquery',
    'mailpoet'
  ],
  function(
    React,
    Router,
    jQuery,
    MailPoet
  ) {

    var Form = React.createClass({
      mixins: [
        Router.Navigation
      ],
      getInitialState: function() {
        return {
          loading: false,
          errors: []
        };
      },
      handleSubmit: function(e) {
        e.preventDefault();

        this.setState({ loading: true });

        MailPoet.Ajax.post({
          endpoint: 'subscribers',
          action: 'save',
          data: {
            email: React.findDOMNode(this.refs.email).value,
            firstname: React.findDOMNode(this.refs.firstname).value,
            lastname: React.findDOMNode(this.refs.lastname).value
          }
        }).done(function(response) {
          this.setState({ loading: false });

          if(response === true) {
            this.transitionTo('/');
          } else {
            this.setState({ errors: response });
          }
        }.bind(this));
      },
      render: function() {
        var errors = this.state.errors.map(function(error, index) {
          return (
            <p key={'error-'+index} className="mailpoet_error">{ error }</p>
          );
        });

        return (
          <form onSubmit={ this.handleSubmit }>
            { errors }
            <p>
              <input type="text" placeholder="Email" ref="email" />
            </p>
            <p>
              <input type="text" placeholder="First name" ref="firstname" />
            </p>
            <p>
              <input type="text" placeholder="Last name" ref="lastname" />
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