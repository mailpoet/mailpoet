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
    const WooCommerceAutomaticEmail = React.createClass({
      contextTypes: {
        router: React.PropTypes.object.isRequired,
      },
      setupNewsletter: function setupNewsletter(type) {
        if (type !== undefined) {
          this.context.router.push(`/new/${type}`);
          MailPoet.trackEvent('Emails > Type selected', {
            'MailPoet Free version': window.mailpoet_version,
            'Email type': type,
          });
        }
      },
      createNewsletter: function createNewsletter(type) {
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
      render: function render() {
        const types = [
          {
            id: 'woocommerce',
            title: MailPoet.I18n.t('wooCommerceEventAbandonedCartTitle'),
            description: MailPoet.I18n.t('wooCommerceEventAbandonedCartDescription'),
            badge: {
              text: MailPoet.I18n.t('wooCommerceEventAbandonedCartBadge'),
              style: 'red',
            },
          },
          {
            id: 'woocommerce',
            title: MailPoet.I18n.t('wooCommerceEventFirstPurchaseTitle'),
            description: MailPoet.I18n.t('wooCommerceEventFirstPurchaseDescription'),
            badge: {
              text: MailPoet.I18n.t('wooCommerceEventFirstPurchaseBadge'),
              style: 'yellow',
            },
          },
          {
            id: 'woocommerce',
            title: MailPoet.I18n.t('wooCommerceEventPurchasedProductTitle'),
            description: MailPoet.I18n.t('wooCommerceEventPurchasedProductDescription'),
          },
          {
            id: 'woocommerce',
            title: MailPoet.I18n.t('wooCommerceEventPurchasedInCategoryTitle'),
            description: MailPoet.I18n.t('wooCommerceEventPurchasedInCategoryDescription'),
            soon: true,
          },
          {
            id: 'woocommerce',
            title: MailPoet.I18n.t('wooCommerceEventBigSpenderTitle'),
            description: MailPoet.I18n.t('wooCommerceEventBigSpenderDescription'),
            soon: true,
            badge: {
              text: MailPoet.I18n.t('wooCommerceEventSmartToHaveBadge'),
              style: 'teal',
            },
          },
        ];

        const steps = [
          {
            name: 'type',
            label: MailPoet.I18n.t('selectType'),
            link: '/new',
          },
          {
            name: 'events',
            label: MailPoet.I18n.t('wooCommerceBreadcrumbsEvents'),
          },
          {
            name: 'conditions',
            label: MailPoet.I18n.t('wooCommerceBreadcrumbsConditions'),
          },
          {
            name: 'template',
            label: MailPoet.I18n.t('template'),
          },
          {
            name: 'editor',
            label: MailPoet.I18n.t('designer'),
          },
          {
            name: 'send',
            label: MailPoet.I18n.t('send'),
          },
        ];

        return (
          <div>
            <h1>{MailPoet.I18n.t('wooCommerceSelectEventHeading')}</h1>

            <Breadcrumb step="events" steps={steps} />

            <ul className="mailpoet_boxes woocommerce clearfix">
              {types.map((type, index) => (
                <li key={index} data-type={type.id}>
                  <div>
                    <div className="mailpoet_thumbnail">
                      {type.thumbnailImage ? <img src={type.thumbnailImage} alt="" /> : null}
                    </div>
                    <div className="mailpoet_description">
                      <div className="title_and_badge">
                        <h3>{type.title} {type.soon ? `(${MailPoet.I18n.t('wooCommerceEventTitleSoon')})` : ''}</h3>
                        {type.badge ? <span className={`mailpoet_badge mailpoet_badge_${type.badge.style}`}>{type.badge.text}</span> : ''}
                      </div>
                      <p>{type.description}</p>
                    </div>

                    <div className="mailpoet_actions">
                      <a href="?page=mailpoet-premium" target="_blank">
                        {MailPoet.I18n.t('wooCommercePremiumFeatureLink')}
                      </a>
                    </div>
                  </div>
                </li>
              ), this)}
            </ul>
          </div>
        );
      },
    });

    return WooCommerceAutomaticEmail;
  }
);
