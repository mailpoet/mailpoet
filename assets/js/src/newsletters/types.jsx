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
      contextTypes: {
        router: React.PropTypes.object.isRequired
      },
      setupNewsletter: function(type) {
        if(type !== undefined) {
          this.context.router.push(`/new/${type}`);
        }
      },
      createNewsletter: function(type) {
        MailPoet.Ajax.post({
          endpoint: 'newsletters',
          action: 'create',
          data: {
            type: type,
            subject: MailPoet.I18n.t('draftNewsletterTitle'),
          }
        }).done(function(response) {
          if(response.result && response.newsletter.id) {
            this.context.router.push(`/template/${response.newsletter.id}`);
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
            <h1>{MailPoet.I18n.t('pickCampaignType')}</h1>

            <Breadcrumb step="type" />

            <ul className="mailpoet_boxes clearfix">
              <li data-type="standard">
                <div className="mailpoet_thumbnail"></div>

                <div className="mailpoet_description">
                  <h3>{MailPoet.I18n.t('regularNewsletterTypeTitle')}</h3>
                  <p>
                    {MailPoet.I18n.t('regularNewsletterTypeDescription')}
                  </p>
                </div>

                <div className="mailpoet_actions">
                  <a
                    className="button button-primary"
                    onClick={ this.createNewsletter.bind(null, 'standard') }
                  >
                    {MailPoet.I18n.t('create')}
                  </a>
                </div>
              </li>

              <li data-type="welcome">
                <div className="mailpoet_thumbnail"></div>

                <div className="mailpoet_description">
                  <h3>{MailPoet.I18n.t('welcomeNewsletterTypeTitle')}</h3>
                  <p>
                    {MailPoet.I18n.t('welcomeNewsletterTypeDescription')}
                  </p>
                </div>

                <div className="mailpoet_actions">
                  <a
                    className="button button-primary"
                    onClick={ this.setupNewsletter.bind(null, 'welcome') }
                  >
                    {MailPoet.I18n.t('setUp')}
                  </a>
                </div>
              </li>

              <li data-type="notification">
                <div className="mailpoet_thumbnail"></div>

                <div className="mailpoet_description">
                  <h3>{MailPoet.I18n.t('postNotificationNewsletterTypeTitle')}</h3>
                  <p>
                    {MailPoet.I18n.t('postNotificationsNewsletterTypeDescription')}
                  </p>
                </div>

                <div className="mailpoet_actions">
                  <a
                    className="button button-primary"
                    onClick={ this.setupNewsletter.bind(null, 'notification') }
                  >
                    {MailPoet.I18n.t('setUp')}
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
