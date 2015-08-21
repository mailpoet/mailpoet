define('newsletters_list', ['react', 'jquery', 'mailpoet'], function(React, jQuery, MailPoet) {

  var Newsletter = React.createClass({
    render: function() {
      return (
        <div className="newsletter">
          <h3 className="subject">
            {this.props.newsletter.subject}
          </h3>
        </div>
      );
    }
  });

  var NewslettersList = React.createClass({
    load: function() {
      MailPoet.Ajax.post({
        endpoint: 'newsletters',
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
      var nodes = this.state.data.map(function (newsletter) {
        return (
          <Newsletter key={newsletter.id} newsletter={newsletter} />
        );
      });
      return (
        <div className="newslettersList">
        {nodes}
        </div>
      );
    }
  });

  var element = jQuery('#newsletters_list');

  if(element.length > 0) {
    React.render(
      <NewslettersList pollInterval={2000} />,
      element[0]
    );
  }
});
