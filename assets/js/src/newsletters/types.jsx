define(
  [
    'react',
    'mailpoet',
    'wp-js-hooks',
    'react-router',
    'newsletters/breadcrumb.jsx',
  ],
  (
    React,
    MailPoet,
    Hooks,
    Router,
    Breadcrumb
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
      render: function () {
        let types = [
          {
            id: 'standard',
            title: MailPoet.I18n.t('regularNewsletterTypeTitle'),
            description: MailPoet.I18n.t('regularNewsletterTypeDescription'),
            action: function () {
              return (
                <a className="button button-primary" data-automation-id="create_standard" onClick={this.createNewsletter.bind(null, 'standard')}>
                  {MailPoet.I18n.t('create')}
                </a>
              );
            }.bind(this)(),
          },
          {
            id: 'welcome',
            title: MailPoet.I18n.t('welcomeNewsletterTypeTitle'),
            description: MailPoet.I18n.t('welcomeNewsletterTypeDescription'),
            action: (function () {
              return (
                <div>
                  <a href="?page=mailpoet-premium" target="_blank">
                    {MailPoet.I18n.t('getPremiumVersion')}
                  </a>
                </div>
              );
            }()),
          },
          {
            id: 'notification',
            title: MailPoet.I18n.t('postNotificationNewsletterTypeTitle'),
            description: MailPoet.I18n.t('postNotificationNewsletterTypeDescription'),
            action: function () {
              return (
                <a className="button button-primary" data-automation-id="create_notification" onClick={this.setupNewsletter.bind(null, 'notification')}>
                  {MailPoet.I18n.t('setUp')}
                </a>
              );
            }.bind(this)(),
          },
        ];

        types = Hooks.applyFilters('mailpoet_newsletters_types', types, this);

        return (
          <div>
            <h1>{MailPoet.I18n.t('pickCampaignType')}</h1>

            <Breadcrumb step="type" />

            <ul className="mailpoet_boxes clearfix">
              {types.map((type, index) => (
                <li key={index} data-type={type.id}>
                  <div>
                    <div className="mailpoet_thumbnail">
                      {type.thumbnailImage ? <img src={type.thumbnailImage} /> : null}
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
