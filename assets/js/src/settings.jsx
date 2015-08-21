define('settings', ['react'], function(React) {
  var CommentBox = React.createClass({
    render: function() {
      return (
          <div className="commentBox">
          Hello, world! I am a CommentBox.
          </div>
          );
    }
  });

  var element = document.getElementById('settings-container');
  if (element) {
    React.render(
        <CommentBox />,
        document.getElementById('settings-container')
        );
  }
});
