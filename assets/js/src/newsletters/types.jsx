import React from 'react';
import MailPoet from 'mailpoet';
import Breadcrumb from 'newsletters/breadcrumb.jsx';
import Hooks from 'wp-js-hooks';
import _ from 'underscore';
import 'react-router';

class NewsletterTypes extends React.Component {
  static contextTypes = {
    router: React.PropTypes.object.isRequired,
  };

  setupNewsletter = (type) => {
    if (type !== undefined) {
      this.context.router.push(`/new/${type}`);
      MailPoet.trackEvent('Emails > Type selected', {
        'MailPoet Free version': window.mailpoet_version,
        'Email type': type,
      });
    }
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
      this.context.router.push(`/template/${response.data.id}`);
    }).fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map(error => error.message),
          { scroll: true }
        );
      }
    });
  };

  getAutomaticEmails = () => {
    if (!window.mailpoet_automatic_emails) return [];

    return _.map(window.mailpoet_automatic_emails, (automaticEmail) => {
      const email = automaticEmail;
      const onClick = _.partial(this.setupNewsletter, automaticEmail.slug);
      email.action = (() => (
        <div>
          <a
            className="button button-primary"
            onClick={onClick}
            role="button"
            tabIndex={0}
          >
            { MailPoet.I18n.t('setUp') }
          </a>
        </div>
      ))();

      return email;
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
        videoGuide: 'https://beta.docs.mailpoet.com/article/254-video-guide-to-welcome-emails',
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
        videoGuide: 'https://beta.docs.mailpoet.com/article/210-video-guide-to-post-notifications',
        action: (function action() {
          return (
            <a
              className="button button-primary"
              data-automation-id="create_notification"
              onClick={createNotificationNewsletter}
              role="button"
              tabIndex={0}
            >
              {MailPoet.I18n.t('setUp')}
            </a>
          );
        }()),
      },
    ];

    const types = Hooks.applyFilters('mailpoet_newsletters_types', [...defaultTypes, ...this.getAutomaticEmails()], this);
    const badgeClassName = (window.mailpoet_is_new_user === true) ? 'mailpoet_badge mailpoet_badge_video' : 'mailpoet_badge mailpoet_badge_video mailpoet_badge_video_grey';

    return (
      <div>
        <h1>{MailPoet.I18n.t('pickCampaignType')}</h1>

        <Breadcrumb step="type" />

        <ul className="mailpoet_boxes clearfix">
          {types.map(type => (
            <li key={type.slug} data-type={type.slug} className="mailpoet_newsletter_types">
              <div>
                <div className="mailpoet_thumbnail">
                  {type.thumbnailImage ? <img src={type.thumbnailImage} alt="" /> : null}
                </div>
                <div className="mailpoet_description">
                  <h3>{type.title} {type.beta ? `(${MailPoet.I18n.t('beta')})` : ''}</h3>
                  <p>{type.description}</p>
                  { type.videoGuide && (
                    <a className={badgeClassName} href={type.videoGuide} target="_blank">
                      <span className="dashicons dashicons-format-video" />{MailPoet.I18n.t('seeVideoGuide')}
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

module.exports = NewsletterTypes;
