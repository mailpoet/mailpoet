define(
  'newsletters_form',
  [
    'react',
    'jquery',
    'mailpoet'
  ],
  function(
    React,
    jQuery,
    MailPoet
  ) {

  var NewslettersForm = React.createClass({
    getInitialState: function() {
      return {
        disabled: false
      };
    },

    post: function(data) {
      MailPoet.Ajax.post({
        endpoint: 'newsletters',
        action: 'save',
        data: data,
        onSuccess: function(response) {
        }.bind(this)
      })
    },

    handleSubmit: function(e) {
      e.preventDefault();
      this.setState({
        disabled: true
      });

      var subject =
        React.findDOMNode(this.refs.subject);
      var body =
        React.findDOMNode(this.refs.body);

      if (!subject.value || !body.value) {
        return;
      }

      this.post({
        subject: subject.value,
        body: body.value
      });

      subject.value = '';
      body.value = '';
      this.setState({
        disabled: false
      });

      return;
    },

    render: function() {
      return (
          <form className="newslettersForm" onSubmit={this.handleSubmit}>
          <input type="text" placeholder="Subject" ref="subject" />
          <br />
          <textarea placeholder="Body" ref="body" />
          <br />
          <input type="submit" value="Save" disabled={this.state.locked} />
          </form>
          );
    }
  });

  var element = jQuery('#newsletters_form');
  if(element.length > 0) {
    React.render(
        <NewslettersForm />,
        element[0]
        );
  }
});
