import React from 'react';
import MailPoet from 'mailpoet';
import Breadcrumb from 'newsletters/breadcrumb.jsx';
import Hooks from 'wp-js-hooks';
import _ from 'underscore';
import 'react-router';

const NewsletterTypes = React.createClass({
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
  },
  getAutomaticEmails: function getAutomaticEmails() {
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
  },
  render: function render() {
    const createStandardNewsletter = _.partial(this.createNewsletter, 'standard');
    const createNotificationNewsletter = _.partial(this.setupNewsletter, 'notification');
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
        action: (function action() {
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

    return (
      <div>
        <h1>{MailPoet.I18n.t('pickCampaignType')}</h1>

        <Breadcrumb step="type" />

        <ul className="mailpoet_boxes clearfix">
          {types.map(type => (
            <li key={type.slug} data-type={type.slug}>
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

module.exports = NewsletterTypes;
