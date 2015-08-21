define('subscribers', ['react', 'jquery', 'mailpoet'], function(React, jQuery, MailPoet) {

  var data = [
  {
    first_name: "John",
    last_name: "Mailer",
    email: 'john@mailpoet.com'
  },
  {
    first_name: "Mark",
    last_name: "Trailer",
    email: 'mark@mailpoet.com'
  }
  ];

  var Subscriber = React.createClass({
    render: function() {
      return (
        <div className="subscriber">
          <h3 className="name">
            {this.props.subscriber.first_name} {this.props.subscriber.last_name}
          </h3>
          {this.props.subscriber.email}
        </div>
      );
    }
  });

  var SubscribersList = React.createClass({
    load: function() {
      MailPoet.Ajax.post({
        endpoint: 'subscribers',
        action: 'get',
        data: {},
        onSuccess: function(response) {
          this.setState({data: response});
        }.bind(this)
      });
    },
    getInitialState: function() {
      return {data: []};
    },
    componentDidMount: function() {
      this.load();
      setInterval(this.load, this.props.pollInterval);
    },
    render: function() {
      var nodes = this.state.data.map(function (subscriber) {
        return (
          <Subscriber key={subscriber.id} subscriber={subscriber} />
        );
      });
      return (
        <div className="subscribersList">
        {nodes}
        </div>
      );
    }
  });

  var element = jQuery('#mailpoet_subscribers');

  if(element.length > 0) {
    React.render(
      <SubscribersList data={data} pollInterval={2000} />,
      element[0]
    );
  }
});
