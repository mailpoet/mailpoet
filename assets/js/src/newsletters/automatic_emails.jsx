import React from 'react';
import _ from 'underscore';
import Hooks from 'wp-js-hooks';
import MailPoet from 'mailpoet';
import AutomaticEmailEventsList from 'newsletters/types/automatic_emails/events_list.jsx';
import EventsConditions from 'newsletters/automatic_emails/events_conditions.jsx';
import AutomaticEmailsBreadcrumb from 'newsletters/types/automatic_emails/breadcrumb.jsx';
import SendEventConditions from 'newsletters/automatic_emails/send_event_conditions.jsx';
import Listings from 'newsletters/automatic_emails/listings.jsx';

const emails = window.mailpoet_premium_automatic_emails || [];
const newslettersContainer = document.getElementById('newsletters_container');

if (newslettersContainer && !_.isEmpty(emails)) {
  const addEmails = (types, that) => {
    // remove automatic emails declared in Free version if they are declared in Premium
    const existingTypes = _.reject(types, (type) => _.has(emails, type.slug));
    const newTypes = _.map(emails, (email) => {
      const updatedEmail = email;
      const onClick = _.partial(that.setupNewsletter, email.slug);

      updatedEmail.action = (() => (
        <div>
          <span
            className="button button-primary"
            onClick={onClick}
            onKeyDown={(event) => {
              if ((['keydown', 'keypress'].includes(event.type) && ['Enter', ' '].includes(event.key))
              ) {
                event.preventDefault();
                onClick();
              }
            }}
            role="button"
            data-automation-id="create_woocommerce"
            tabIndex={0}
          >
            {email.actionButtonTitle || MailPoet.I18n.t('setUp')}
          </span>
        </div>
      ))();

      return updatedEmail;
    });

    return [...existingTypes, ...newTypes];
  };

  const addEmailsRoutes = (routes) => {
    // remove routes declared in Free version if they are declared in Premium
    const existingRoutes = _.reject(routes, (route) => _.has(emails, route.name));

    const emailsRoutes = [];
    const emailsEventsRoutes = [];
    const emailsListingsRoute = [];

    _.each(emails, (email) => {
      const { events } = email;

      if (_.isObject(events)) {
        _.each(events, (event) => {
          emailsEventsRoutes.push({
            path: `/new/${email.slug}/${event.slug}/conditions`,
            name: event.slug,
            render: (props) => {
              const componentProps = {
                ...props,
                email,
                name: event.slug,
              };
              return (<EventsConditions {...componentProps} />);
            },
          });
        });
      }

      emailsRoutes.push({
        path: `/new/${email.slug}`,
        name: email.slug,
        render: (props) => {
          const componentProps = {
            ...props,
            email,
          };
          return (<AutomaticEmailEventsList {...componentProps} />);
        },
      });

      emailsListingsRoute.push({
        path: `/${email.slug}/(.*)?`,
        params: {
          tab: email.slug,
        },
        component: Listings,
      });
    });
    return [...emailsEventsRoutes, ...emailsRoutes, ...emailsListingsRoute, ...existingRoutes];
  };

  Hooks.addFilter('mailpoet_newsletters_types', 'mailpoet', addEmails);
  Hooks.addFilter('mailpoet_newsletters_before_router', 'mailpoet', addEmailsRoutes);
}

const addListingsTabs = (tabs) => {
  const listingsTabs = [];

  _.each(emails, (email) => {
    listingsTabs.push({
      name: email.slug,
      label: email.title,
      link: `/${email.slug}`,
      display: window.mailpoet_woocommerce_active,
    });
  });

  return [...tabs, ...listingsTabs];
};

Hooks.addFilter('mailpoet_newsletters_listings_tabs', 'mailpoet', addListingsTabs);

const addTemplateSelectionBreadcrumb = (defaultBreadcrumb, newsletterType, step) => (
  (newsletterType === 'automatic')
    ? <AutomaticEmailsBreadcrumb step={step} />
    : defaultBreadcrumb
);

Hooks.addFilter('mailpoet_newsletters_template_breadcrumb', 'mailpoet', addTemplateSelectionBreadcrumb);
Hooks.addFilter('mailpoet_newsletters_editor_breadcrumb', 'mailpoet', addTemplateSelectionBreadcrumb);
Hooks.addFilter('mailpoet_newsletters_send_breadcrumb', 'mailpoet', addTemplateSelectionBreadcrumb);

const extendNewsletterEditorConfig = (defaultConfig, newsletter) => {
  if (newsletter.type !== 'automatic') return defaultConfig;

  const config = defaultConfig;

  return MailPoet.Ajax
    .post({
      api_version: window.mailpoet_api_version,
      endpoint: 'automatic_emails',
      action: 'get_event_shortcodes',
      data: {
        email_slug: newsletter.options.group,
        event_slug: newsletter.options.event,
      },
    })
    .then((response) => {
      if (!_.isObject(response) || !response.data) return config;
      config.shortcodes = { ...config.shortcodes, ...response.data };
      return config;
    })
    .fail((pauseFailResponse) => {
      if (pauseFailResponse.errors.length > 0) {
        MailPoet.Notice.error(
          pauseFailResponse.errors.map((error) => error.message),
          { scroll: true, static: true }
        );
      }
    });
};

Hooks.addFilter('mailpoet_newsletters_editor_extend_config', 'mailpoet', extendNewsletterEditorConfig);

const configureSendPageOptions = (defaultFields, newsletter) => {
  if (newsletter.type !== 'automatic') return defaultFields;

  const email = emails[newsletter.options.group];

  if (!email) return defaultFields;

  const emailOptions = newsletter.options;
  const fields = [
    {
      name: 'subject',
      label: MailPoet.I18n.t('subjectLine'),
      tip: MailPoet.I18n.t('subjectLineTip'),
      type: 'text',
      validation: {
        'data-parsley-required': true,
        'data-parsley-required-message': MailPoet.I18n.t('emptySubjectLineError'),
      },
    },
    {
      name: 'options',
      label: MailPoet.I18n.t('sendAutomaticEmailWhenHeading').replace('%1s', email.title),
      type: 'reactComponent',
      component: SendEventConditions,
      email,
      emailOptions,
    },
    {
      name: 'sender',
      label: MailPoet.I18n.t('sender'),
      tip: MailPoet.I18n.t('senderTip'),
      fields: [
        {
          name: 'sender_name',
          type: 'text',
          placeholder: MailPoet.I18n.t('senderNamePlaceholder'),
          validation: {
            'data-parsley-required': true,
          },
        },
        {
          name: 'sender_address',
          type: 'text',
          placeholder: MailPoet.I18n.t('senderAddressPlaceholder'),
          validation: {
            'data-parsley-required': true,
            'data-parsley-type': 'email',
          },
        },
      ],
    },
    {
      name: 'reply-to',
      label: MailPoet.I18n.t('replyTo'),
      tip: MailPoet.I18n.t('replyToTip'),
      inline: true,
      fields: [
        {
          name: 'reply_to_name',
          type: 'text',
          placeholder: MailPoet.I18n.t('replyToNamePlaceholder'),
        },
        {
          name: 'reply_to_address',
          type: 'text',
          placeholder: MailPoet.I18n.t('replyToAddressPlaceholder'),
          validation: {
            'data-parsley-type': 'email',
          },
        },
      ],
    },
  ];

  return {
    getFields: function getFields() {
      return fields;
    },
    getSendButtonOptions: function getSendButtonOptions() {
      return {
        value: MailPoet.I18n.t('activate'),
      };
    },
  };
};

Hooks.addFilter('mailpoet_newsletters_send_newsletter_fields', 'mailpoet', configureSendPageOptions);

const configureSendPageServerRequest = (defaultParameters, newsletter) => (
  (newsletter.type === 'automatic') ? {
    api_version: window.mailpoet_api_version,
    endpoint: 'newsletters',
    action: 'setStatus',
    data: {
      id: newsletter.id,
      status: 'active',
    },
  } : defaultParameters
);

Hooks.addFilter('mailpoet_newsletters_send_server_request_parameters', 'mailpoet', configureSendPageServerRequest);

const redirectAfterSendPageServerRequest = (defaultRedirect, newsletter) => (
  (newsletter.type === 'automatic') ? `/${newsletter.options.group}` : defaultRedirect
);

Hooks.addFilter('mailpoet_newsletters_send_server_request_response_redirect', 'mailpoet', redirectAfterSendPageServerRequest);

const configureSendPageServerResponse = (newsletter) => {
  if (newsletter.type !== 'automatic') return null;

  const email = emails[newsletter.options.group];

  if (!email) return null;

  return () => {
    MailPoet.Notice.success(MailPoet.I18n.t('automaticEmailActivated').replace('%1s', email.title));
  };
};

Hooks.addFilter('mailpoet_newsletters_send_server_request_response', 'mailpoet', configureSendPageServerResponse);
