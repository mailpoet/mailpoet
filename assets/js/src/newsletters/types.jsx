import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';
import Breadcrumb from 'newsletters/breadcrumb.jsx';
import Hooks from 'wp-js-hooks';
import _ from 'underscore';
import jQuery from 'jquery';
import { withRouter } from 'react-router-dom';

class NewsletterTypes extends React.Component {
  static propTypes = {
    history: PropTypes.shape({
      push: PropTypes.func.isRequired,
    }).isRequired,
  };

  setupNewsletter = (type) => {
    if (type !== undefined) {
      this.props.history.push(`/new/${type}`);
      MailPoet.trackEvent('Emails > Type selected', {
        'MailPoet Free version': window.mailpoet_version,
        'Email type': type,
      });
    }
  };

  getAutomaticEmails = () => {
    if (!window.mailpoet_automatic_emails) return [];

    return _.map(window.mailpoet_automatic_emails, (automaticEmail) => {
      const email = automaticEmail;
      const onClick = window.mailpoet_premium_active
        ? _.partial(this.setupNewsletter, automaticEmail.slug)
        : undefined;
      email.disabled = !window.mailpoet_premium_active;
      email.action = (() => (
        <div>
          <a
            className="button button-primary"
            onClick={onClick}
            role="button"
            tabIndex={0}
            disabled={!window.mailpoet_premium_active}
            onKeyDown={(event) => {
              if ((['keydown', 'keypress'].includes(event.type) && ['Enter', ' '].includes(event.key))
              ) {
                event.preventDefault();
                this.onClick();
              }
            }}
          >
            { MailPoet.I18n.t('setUp') }
          </a>
        </div>
      ))();

      return email;
    });
  };

  getAdditionalTypes = () => {
    const show = window.mailpoet_woocommerce_active && MailPoet.FeaturesController.isSupported('wc-transactional-emails-customizer');
    if (!show) {
      return [];
    }
    return [
      {
        slug: 'wc_transactional',
        title: MailPoet.I18n.t('wooCommerceCustomizerTypeTitle'),
        description: MailPoet.I18n.t('wooCommerceCustomizerTypeDescription'),
        action: (
          <a
            className="button button-primary"
            data-automation-id="customize_woocommerce"
            onClick={this.openWooCommerceCustomizer}
            role="button"
            tabIndex={0}
            onKeyDown={(event) => {
              if ((['keydown', 'keypress'].includes(event.type) && ['Enter', ' '].includes(event.key))
              ) {
                event.preventDefault();
                this.openWooCommerceCustomizer();
              }
            }}
          >
            {MailPoet.I18n.t(window.mailpoet_woocommerce_customizer_enabled ? 'customize' : 'activate_and_customize')}
          </a>
        ),
      },
    ];
  };

  openWooCommerceCustomizer = () => {
    const promise = jQuery.Deferred();
    MailPoet.trackEvent('Emails > Type selected', {
      'MailPoet Free version': window.mailpoet_version,
      'Email type': 'wc_transactional',
    });
    if (!window.mailpoet_woocommerce_customizer_enabled) {
      promise.then(() => MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'settings',
        action: 'set',
        data: {
          'woocommerce.use_mailpoet_editor': 1,
        },
      }));
    }
    promise.done(() => {
      window.location.href = `?page=mailpoet-newsletter-editor&id=${window.mailpoet_woocommerce_transactional_email_id}`;
    }).fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map((error) => error.message),
          { scroll: true }
        );
      }
    });
    promise.resolve();
  };

  createNewsletter = (type) => {
    MailPoet.trackEvent('Emails > Type selected', {
      'MailPoet Free version': window.mailpoet_version,
      'Email type': type,
    });
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'create',
      data: {
        type,
        subject: MailPoet.I18n.t('draftNewsletterTitle'),
      },
    }).done((response) => {
      this.props.history.push(`/template/${response.data.id}`);
    }).fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map((error) => error.message),
          { scroll: true }
        );
      }
    });
  };

  render() {
    const createStandardNewsletter = _.partial(this.createNewsletter, 'standard');
    const createNotificationNewsletter = _.partial(this.setupNewsletter, 'notification');
    const createWelcomeNewsletter = _.partial(this.setupNewsletter, 'welcome');

    const defaultTypes = [
      {
        slug: 'standard',
        title: MailPoet.I18n.t('regularNewsletterTypeTitle'),
        description: MailPoet.I18n.t('regularNewsletterTypeDescription'),
        action: (function action() {
          return (
            <a
              className="button button-primary"
              data-automation-id="create_standard"
              onClick={createStandardNewsletter}
              role="button"
              tabIndex={0}
              onKeyDown={(event) => {
                if ((['keydown', 'keypress'].includes(event.type) && ['Enter', ' '].includes(event.key))
                ) {
                  event.preventDefault();
                  createStandardNewsletter();
                }
              }}
            >
              {MailPoet.I18n.t('create')}
            </a>
          );
        }()),
      },
      {
        slug: 'welcome',
        title: MailPoet.I18n.t('welcomeNewsletterTypeTitle'),
        description: MailPoet.I18n.t('welcomeNewsletterTypeDescription'),
        videoGuide: 'https://kb.mailpoet.com/article/254-video-guide-to-welcome-emails',
        videoGuideBeacon: '5b05ebf20428635ba8b2aa53',
        action: (function action() {
          return (
            <a
              className="button button-primary"
              onClick={createWelcomeNewsletter}
              data-automation-id="create_welcome"
              onKeyDown={(event) => {
                if ((['keydown', 'keypress'].includes(event.type) && ['Enter', ' '].includes(event.key))
                ) {
                  event.preventDefault();
                  createWelcomeNewsletter();
                }
              }}
              role="button"
              tabIndex={0}
            >
              {MailPoet.I18n.t('setUp')}
            </a>
          );
        }()),
      },
      {
        slug: 'notification',
        title: MailPoet.I18n.t('postNotificationNewsletterTypeTitle'),
        description: MailPoet.I18n.t('postNotificationNewsletterTypeDescription'),
        videoGuide: 'https://kb.mailpoet.com/article/210-video-guide-to-post-notifications',
        videoGuideBeacon: '59ba6fb3042863033a1cd5a5',
        action: (function action() {
          return (
            <a
              className="button button-primary"
              data-automation-id="create_notification"
              onClick={createNotificationNewsletter}
              role="button"
              tabIndex={0}
              onKeyDown={(event) => {
                if ((['keydown', 'keypress'].includes(event.type) && ['Enter', ' '].includes(event.key))
                ) {
                  event.preventDefault();
                  createNotificationNewsletter();
                }
              }}
            >
              {MailPoet.I18n.t('setUp')}
            </a>
          );
        }()),
      },
    ];

    const types = Hooks.applyFilters('mailpoet_newsletters_types', [
      ...defaultTypes,
      ...this.getAutomaticEmails(),
      ...this.getAdditionalTypes(),
    ], this);
    const badgeClassName = (window.mailpoet_is_new_user === true) ? 'mailpoet_badge mailpoet_badge_video' : 'mailpoet_badge mailpoet_badge_video mailpoet_badge_video_grey';

    return (
      <div>
        <link rel="prefetch" href={window.mailpoet_editor_javascript_url} as="script" />
        <h1>{MailPoet.I18n.t('pickCampaignType')}</h1>

        <Breadcrumb step="type" />

        <ul className="mailpoet_boxes mailpoet_boxes_types">
          {types.map((type) => (
            <li key={type.slug} data-type={type.slug} className="mailpoet_newsletter_types">
              <div className="mailpoet_thumbnail">
                {type.thumbnailImage ? <img src={type.thumbnailImage} alt="" /> : null}
              </div>
              <div className="mailpoet_boxes_content">
                <div className="mailpoet_description">
                  <h3>
                    {type.title}
                    {' '}
                    {type.beta ? `(${MailPoet.I18n.t('beta')})` : ''}
                  </h3>
                  <p>{type.description}</p>
                  {type.disabled && (
                  <p data-automation-id={`${type.slug}_premium_feature_notice`}>
                    <span style={{ color: 'red' }}>{MailPoet.I18n.t('premiumFeature')}</span>
                    {' '}
                    <a href="?page=mailpoet-premium">{MailPoet.I18n.t('learnMore')}</a>
                  </p>
                  )}
                  { type.videoGuide && (
                    <a className={badgeClassName} href={type.videoGuide} data-beacon-article={type.videoGuideBeacon} target="_blank" rel="noopener noreferrer">
                      <span className="dashicons dashicons-format-video" />
                      {MailPoet.I18n.t('seeVideoGuide')}
                    </a>
                  ) }
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
  }
}

export default withRouter(NewsletterTypes);
