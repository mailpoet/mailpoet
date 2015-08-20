define('subscribers', ['react'], function(React) {
  var CommentBox = React.createClass({
    render: function() {
      return (
          <div className="commentBox">
          Hello, world! I am a CommentBox.
          </div>
          );
    }
  });

  var element = document.getElementById('mailpoet_subscribers');
  if (element) {
    React.render(
        <CommentBox />,
        document.getElementById('mailpoet_subscribers')
        );
  }
});
