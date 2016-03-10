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
            subject: MailPoetI18n.draftNewsletter,
          }
        }).done(function(response) {
          if(response.result && response.newsletter.id) {
            this.history.pushState(null, `/template/${response.newsletter.id}`);
          } else {
            if(response.errors.length > 0) {
              response.errors.map(function(error) {
                MailPoet.Notice.error(error);
              });
            }
          }
        }.bind(this));
      },
      render: function() {
        return (
          <div>
            <h1>{MailPoetI18n.pickCampaignType}</h1>

            <Breadcrumb step="type" />

            <ul className="mailpoet_boxes clearfix">
              <li data-type="standard">
                <div className="mailpoet_thumbnail"></div>

                <div className="mailpoet_description">
                  <h3>{MailPoetI18n.standardNewsletter}</h3>
                  <p>
                    {MailPoetI18n.standardNewsletterDescription}
                  </p>
                </div>

                <div className="mailpoet_actions">
                  <a
                    className="button button-primary"
                    onClick={ this.createNewsletter.bind(null, 'standard') }
                  >
                    {MailPoetI18n.create}
                  </a>
                </div>
              </li>

              <li data-type="welcome">
                <div className="mailpoet_thumbnail"></div>

                <div className="mailpoet_description">
                  <h3>{MailPoetI18n.welcomeEmail}</h3>
                  <p>
                    {MailPoetI18n.welcomeEmailDescription}
                  </p>
                </div>

                <div className="mailpoet_actions">
                  <a
                    className="button button-primary"
                    onClick={ this.setupNewsletter.bind(null, 'welcome') }
                  >
                    {MailPoetI18n.setUp}
                  </a>
                </div>
              </li>

              <li data-type="notification">
                <div className="mailpoet_thumbnail"></div>

                <div className="mailpoet_description">
                  <h3>{MailPoetI18n.postNotificationsNewsletter}</h3>
                  <p>
                    {MailPoetI18n.postNotificationsNewsletterDescription}
                  </p>
                </div>

                <div className="mailpoet_actions">
                  <a
                    className="button button-primary"
                    onClick={ this.setupNewsletter.bind(null, 'notification') }
                  >
                    {MailPoetI18n.setUp}
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
