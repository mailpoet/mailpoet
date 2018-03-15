define(
  [
    'react',
    'mailpoet',
    'wp-js-hooks',
    'react-router',
    'newsletters/breadcrumb.jsx',
    'underscore',
  ],
  (
    React,
    MailPoet,
    Hooks,
    Router,
    Breadcrumb,
    _
  ) => {
    const NewsletterTypes = React.createClass({
      contextTypes: {
        router: React.PropTypes.object.isRequired,
      },
      setupNewsletter: function (type) {
        if (type !== undefined) {
          this.context.router.push(`/new/${type}`);
          MailPoet.trackEvent('Emails > Type selected', {
            'MailPoet Free version': window.mailpoet_version,
            'Email type': type,
          });
        }
      },
      createNewsletter: function (type) {
        MailPoet.trackEvent('Emails > Type selected', {
          'MailPoet Free version': window.mailpoet_version,
          'Email type': type,
        });
        MailPoet.Ajax.post({
          api_version: window.mailpoet_api_version,
          endpoint: 'newsletters',
          action: 'create',
          data: {
            type: type,
            subject: MailPoet.I18n.t('draftNewsletterTitle'),
          },
        }).done((response) => {
          this.context.router.push(`/template/${response.data.id}`);
        }).fail((response) => {
          if (response.errors.length > 0) {
            MailPoet.Notice.error(
              response.errors.map(error => error.message),
              { scroll: true }
            );
          }
        });
      },
      getAutomaticEmails: function () {
        if (!window.mailpoet_automatic_emails) return [];

        return _.map(window.mailpoet_automatic_emails, (automaticEmail) => {
          const email = automaticEmail;
          const disabled = !email.events;

          email.action = (() => (
            <div>
              <a className="button button-primary"
                disabled={disabled}
                onClick={!disabled ? this.setupNewsletter.bind(null, automaticEmail.slug) : null}
              >
                { MailPoet.I18n.t('setUp') }
              </a>
            </div>
          ))();

          return email;
        });
      },
      render: function () {
        const defaultTypes = [
          {
            slug: 'standard',
            title: MailPoet.I18n.t('regularNewsletterTypeTitle'),
            description: MailPoet.I18n.t('regularNewsletterTypeDescription'),
            action: function () {
              return (
                <a
                  className="button button-primary"
                  data-automation-id="create_standard"
                  onClick={this.createNewsletter.bind(null, 'standard')}
                  role="button"
                  tabIndex={0}
                >
                  {MailPoet.I18n.t('create')}
                </a>
              );
            }.bind(this)(),
          },
          {
            slug: 'welcome',
            title: MailPoet.I18n.t('welcomeNewsletterTypeTitle'),
            description: MailPoet.I18n.t('welcomeNewsletterTypeDescription'),
            action: (function () {
              return (
                <div>
                  <a href="?page=mailpoet-premium" target="_blank">
                    {MailPoet.I18n.t('premiumFeatureLink')}
                  </a>
                </div>
              );
            }()),
          },
          {
            slug: 'notification',
            title: MailPoet.I18n.t('postNotificationNewsletterTypeTitle'),
            description: MailPoet.I18n.t('postNotificationNewsletterTypeDescription'),
            action: function () {
              return (
                <a
                  className="button button-primary"
                  data-automation-id="create_notification"
                  onClick={this.setupNewsletter.bind(null, 'notification')}
                  role="button"
                  tabIndex={0}
                >
                  {MailPoet.I18n.t('setUp')}
                </a>
              );
            }.bind(this)(),
          },
        ];

        const types = Hooks.applyFilters('mailpoet_newsletters_types', [...defaultTypes, ...this.getAutomaticEmails()], this);

        return (
          <div>
            <h1>{MailPoet.I18n.t('pickCampaignType')}</h1>

            <Breadcrumb step="type" />

            <ul className="mailpoet_boxes clearfix">
              {types.map((type, index) => (
                <li key={index} data-type={type.slug}>
                  <div>
                    <div className="mailpoet_thumbnail">
                      {type.thumbnailImage ? <img src={type.thumbnailImage} alt="" /> : null}
                    </div>
                    <div className="mailpoet_description">
                      <h3>{type.title}</h3>
                      <p>{type.description}</p>
                    </div>

                    <div className="mailpoet_actions">
                      {type.action}
                    </div>
                  </div>
                </li>
                ), this)}
            </ul>
          </div>
        );
      },
    });

    return NewsletterTypes;
  }
);
