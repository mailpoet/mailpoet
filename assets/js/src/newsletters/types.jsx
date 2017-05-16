define(
  [
    'react',
    'mailpoet',
    'wp-js-hooks',
    'react-router',
    'newsletters/breadcrumb.jsx'
  ],
  function(
    React,
    MailPoet,
    Hooks,
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
          api_version: window.mailpoet_api_version,
          endpoint: 'newsletters',
          action: 'create',
          data: {
            type: type,
            subject: MailPoet.I18n.t('draftNewsletterTitle'),
          }
        }).done((response) => {
          this.context.router.push(`/template/${response.data.id}`);
        }).fail((response) => {
          if (response.errors.length > 0) {
            MailPoet.Notice.error(
              response.errors.map(function(error) { return error.message; }),
              { scroll: true }
            );
          }
        });
      },
      render: function() {
        var types = [
          {
            'id': 'standard',
            'title': MailPoet.I18n.t('regularNewsletterTypeTitle'),
            'description': MailPoet.I18n.t('regularNewsletterTypeDescription'),
            'action': function() {
              return (
                <a className="button button-primary" onClick={ this.createNewsletter.bind(null, 'standard') }>
                  {MailPoet.I18n.t('create')}
                </a>
              )
            }.bind(this)()
          },
          {
            'id': 'welcome',
            'title': MailPoet.I18n.t('welcomeNewsletterTypeTitle'),
            'description': MailPoet.I18n.t('welcomeNewsletterTypeDescription'),
            'action': function() {
              return (
                <div>
                  Premium feature text (TBD)
                </div>
              )
            }()
          },
          {
            'id': 'notification',
            'title': MailPoet.I18n.t('postNotificationNewsletterTypeTitle'),
            'description': MailPoet.I18n.t('postNotificationNewsletterTypeDescription'),
            'action': function() {
              return (
                <a className="button button-primary" onClick={ this.createNewsletter.bind(null, 'standard') }>
                  {MailPoet.I18n.t('setUp')}
                </a>
              )
            }.bind(this)()
          }
        ];

        types = Hooks.applyFilters('mailpoet_newsletters_types', types);

        return (
          <div>
            <h1>{MailPoet.I18n.t('pickCampaignType')}</h1>

            <Breadcrumb step="type" />

            <ul className="mailpoet_boxes clearfix">
              {types.map(function(type, index) {
                return (
                  <li key={index} data-type={type.id}>
                    <div>
                      <div className="mailpoet_thumbnail"></div>

                      <div className="mailpoet_description">
                        <h3>{type.title}</h3>
                        <p>{type.description}</p>
                      </div>

                      <div className="mailpoet_actions">
                        {type.action}
                      </div>
                    </div>
                  </li>
                )
              }, this)}
            </ul>
          </div>
        );
      }
    });

    return NewsletterTypes;
  }
);