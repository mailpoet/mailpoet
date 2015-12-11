define(
  [
    'react',
    'mailpoet',
    'react-router',
    'newsletters/breadcrumb.jsx'
  ],
  function(
    React,
    MailPoet,
    Router,
    Breadcrumb
  ) {
    var NewsletterTypes = React.createClass({
      mixins: [
        Router.History
      ],
      setupNewsletter: function(type) {
        if(type !== undefined) {
          this.history.pushState(null, `/new/${type}`);
        }
      },
      createNewsletter: function(type) {
        MailPoet.Ajax.post({
          endpoint: 'newsletters',
          action: 'create',
          data: {
            type: type,
            subject: 'Draft newsletter',
          }
        }).done(function(response) {
          if(response.id !== undefined) {
            this.history.pushState(null, `/template/${response.id}`);
          } else {
            response.map(function(error) {
              MailPoet.Notice.error(error);
            });
          }
        }.bind(this));
      },
      render: function() {
        return (
          <div>
            <h1>Pick a type of campaign</h1>

            <Breadcrumb step="type" />

            <ul className="mailpoet_boxes clearfix">
              <li data-type="standard">
                <div className="mailpoet_thumbnail"></div>

                <div className="mailpoet_description">
                  <h3>Newsletter</h3>
                  <p>
                    Send a newsletter with images, buttons, dividers,
                    and social bookmarks. Or a simple email.
                  </p>
                </div>

                <div className="mailpoet_actions">
                  <a
                    className="button button-primary"
                    onClick={ this.createNewsletter.bind(null, 'standard') }
                  >
                    Create
                  </a>
                </div>
              </li>

              <li data-type="welcome">
                <div className="mailpoet_thumbnail"></div>

                <div className="mailpoet_description">
                  <h3>Welcome email</h3>
                  <p>
                    Send an email for new users.
                  </p>
                </div>

                <div className="mailpoet_actions">
                  <a
                    className="button button-primary"
                    onClick={ this.setupNewsletter.bind(null, 'welcome') }
                  >
                    Set up
                  </a>
                </div>
              </li>

              <li data-type="notification">
                <div className="mailpoet_thumbnail"></div>

                <div className="mailpoet_description">
                  <h3>Post notifications</h3>
                  <p>
                    Automatically send posts immediately, daily, weekly or monthly. Filter by categories, if you like.
                  </p>
                </div>

                <div className="mailpoet_actions">
                  <a
                    className="button button-primary"
                    onClick={ this.setupNewsletter.bind(null, 'notification') }
                  >
                    Set up
                  </a>
                </div>
              </li>
            </ul>
          </div>
        );
      }
    });

    return NewsletterTypes;
  }
);
