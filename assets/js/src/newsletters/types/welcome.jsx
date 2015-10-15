define(
  [
    'react',
    'react-router',
    'mailpoet',
    'form/form.jsx',
    'form/fields/selection.jsx',
    'newsletters/breadcrumb.jsx'
  ],
  function(
    React,
    Router,
    MailPoet,
    Form,
    Selection,
    Breadcrumb
  ) {

    var NewsletterWelcome = React.createClass({
      render: function() {
        return (
          <div>
            <h1>Welcome email</h1>
            <Breadcrumb step="type" />

            Welcome email options placeholder
          </div>
        );
      },
    });

    return NewsletterWelcome;
  }
);
