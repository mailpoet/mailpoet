import { Fragment, ComponentType, useState } from 'react';
import MailPoet from 'mailpoet';
import Hooks from 'wp-js-hooks';
import _ from 'underscore';
import { withRouter, RouteComponentProps } from 'react-router-dom';

import AutomaticEmailEventsList from 'newsletters/types/automatic_emails/events_list.jsx';
import AutomaticEmailEventGroupLogos from 'newsletters/types/automatic_emails/event_group_logos.jsx';
import Button from 'common/button/button';
import Heading from 'common/typography/heading/heading';
import ModalCloseIcon from 'common/modal/close_icon';
import HideScreenOptions from 'common/hide_screen_options/hide_screen_options';
import APIErrorsNotice from '../notices/api_errors_notice';
import { isErrorResponse } from '../ajax';

interface Props {
  filter?: () => void;
  history: RouteComponentProps['history'];
  hideScreenOptions?: boolean;
  hideClosingButton?: boolean;
}

interface NewsletterTypesWindow extends Window {
  mailpoet_woocommerce_transactional_email_id: string;
  mailpoet_is_new_user: boolean;
  mailpoet_editor_javascript_url: string;
  mailpoet_woocommerce_automatic_emails: Record<string, unknown>;
}

declare let window: NewsletterTypesWindow;

function NewsletterTypes({
  filter,
  history,
  hideClosingButton = false,
  hideScreenOptions = true,
}: Props): JSX.Element {
  const [isCreating, setIsCreating] = useState(false);

  const setupNewsletter = (type): void => {
    if (type !== undefined) {
      history.push(`/new/${type}`);
      MailPoet.trackEvent('Emails > Type selected', {
        'Email type': type,
      });
    }
  };

  const openWooCommerceCustomizer = async (): Promise<JSX.Element> => {
    MailPoet.trackEvent('Emails > Type selected', {
      'Email type': 'wc_transactional',
    });
    let emailId = window.mailpoet_woocommerce_transactional_email_id;
    if (!emailId) {
      try {
        const response = await MailPoet.Ajax.post({
          api_version: MailPoet.apiVersion,
          endpoint: 'settings',
          action: 'set',
          data: {
            'woocommerce.use_mailpoet_editor': 1,
          },
        });
        emailId = response.data.woocommerce.transactional_email_id;
        MailPoet.trackEvent('Emails > WooCommerce email customizer enabled');
      } catch (response) {
        if (isErrorResponse(response) && response.errors.length > 0) {
          return <APIErrorsNotice errors={response.errors} />;
        }
        return null;
      }
    }
    window.location.href = `?page=mailpoet-newsletter-editor&id=${emailId}`;
    return null;
  };

  const renderType = (type): JSX.Element => {
    const badgeClassName =
      window.mailpoet_is_new_user === true
        ? 'mailpoet_badge mailpoet_badge_video'
        : 'mailpoet_badge mailpoet_badge_video mailpoet_badge_video_grey';

    return (
      <div
        key={type.slug}
        data-type={type.slug}
        className="mailpoet-newsletter-type"
      >
        <div className="mailpoet-newsletter-type-image" />
        <div className="mailpoet-newsletter-type-content">
          <Heading level={4}>
            {type.title} {type.beta ? `(${MailPoet.I18n.t('beta')})` : ''}
          </Heading>
          <p>{type.description}</p>
          {type.videoGuide && (
            <a
              className={badgeClassName}
              href={type.videoGuide}
              data-beacon-article={type.videoGuideBeacon}
              target="_blank"
              rel="noopener noreferrer"
            >
              <span className="dashicons dashicons-format-video" />
              {MailPoet.I18n.t('seeVideoGuide')}
            </a>
          )}
          <div className="mailpoet-flex-grow" />
          <div className="mailpoet-newsletter-type-action">{type.action}</div>
        </div>
      </div>
    );
  };

  const getAdditionalTypes = (): Record<string, unknown>[] => {
    const show = MailPoet.isWoocommerceActive;
    if (!show) {
      return [];
    }
    return [
      {
        slug: 'wc_transactional',
        title: MailPoet.I18n.t('wooCommerceCustomizerTypeTitle'),
        description: MailPoet.I18n.t('wooCommerceCustomizerTypeDescription'),
        action: (
          <Button
            automationId="customize_woocommerce"
            onClick={openWooCommerceCustomizer}
            tabIndex={0}
            onKeyDown={async (event): Promise<void> => {
              if (
                ['keydown', 'keypress'].includes(event.type) &&
                ['Enter', ' '].includes(event.key)
              ) {
                event.preventDefault();
                await openWooCommerceCustomizer();
              }
            }}
          >
            {MailPoet.I18n.t('customize')}
          </Button>
        ),
      },
    ];
  };

  const getAutomaticEmails = (): JSX.Element[] => {
    if (!window.mailpoet_woocommerce_automatic_emails) return [];
    let automaticEmails = window.mailpoet_woocommerce_automatic_emails;
    if (filter) {
      automaticEmails = _.filter(automaticEmails, filter);
    }

    return _.map(automaticEmails, (automaticEmail) => {
      const email = automaticEmail;
      return (
        <Fragment key={email.slug}>
          {!filter && (
            <div className="mailpoet-newsletter-types-separator">
              <div className="mailpoet-newsletter-types-separator-line" />
              <div className="mailpoet-newsletter-types-separator-logo">
                {AutomaticEmailEventGroupLogos[email.slug] || null}
              </div>
              <div className="mailpoet-newsletter-types-separator-line" />
            </div>
          )}

          <AutomaticEmailEventsList email={email} history={history} />

          {email.slug === 'woocommerce' &&
            getAdditionalTypes().map((type) => renderType(type), this)}
        </Fragment>
      );
    });
  };

  const createNewsletter = (type): void => {
    setIsCreating(true);
    MailPoet.trackEvent('Emails > Type selected', {
      'Email type': type,
    });
    void MailPoet.Ajax.post({
      api_version: MailPoet.apiVersion,
      endpoint: 'newsletters',
      action: 'create',
      data: {
        type,
        subject: MailPoet.I18n.t('draftNewsletterTitle'),
      },
    })
      .done((response) => {
        history.push(`/template/${response.data.id}`);
      })
      .fail((response) => {
        setIsCreating(false);
        if (response.errors.length > 0) {
          return <APIErrorsNotice errors={response.errors} />;
        }
        return null;
      });
  };

  const createStandardNewsletter = _.partial(createNewsletter, 'standard');
  const createNotificationNewsletter = _.partial(
    setupNewsletter,
    'notification',
  );
  const createWelcomeNewsletter = _.partial(setupNewsletter, 'welcome');
  const createReEngagementNewsletter = _.partial(
    setupNewsletter,
    're-engagement',
  );

  const defaultTypes = [
    {
      slug: 'standard',
      title: MailPoet.I18n.t('regularNewsletterTypeTitle'),
      description: MailPoet.I18n.t('regularNewsletterTypeDescription'),
      action: (
        <Button
          automationId="create_standard"
          onClick={createStandardNewsletter}
          tabIndex={0}
          withSpinner={isCreating}
          onKeyDown={(event): void => {
            if (
              ['keydown', 'keypress'].includes(event.type) &&
              ['Enter', ' '].includes(event.key)
            ) {
              event.preventDefault();
              createStandardNewsletter();
            }
          }}
        >
          {MailPoet.I18n.t('create')}
        </Button>
      ),
    },
    {
      slug: 'welcome',
      title: MailPoet.I18n.t('welcomeNewsletterTypeTitle'),
      description: MailPoet.I18n.t('welcomeNewsletterTypeDescription'),
      videoGuide:
        'https://kb.mailpoet.com/article/254-video-guide-to-welcome-emails',
      videoGuideBeacon: '5b05ebf20428635ba8b2aa53',
      action: (
        <Button
          onClick={createWelcomeNewsletter}
          automationId="create_welcome"
          withSpinner={isCreating}
          onKeyDown={(event): void => {
            if (
              ['keydown', 'keypress'].includes(event.type) &&
              ['Enter', ' '].includes(event.key)
            ) {
              event.preventDefault();
              createWelcomeNewsletter();
            }
          }}
          tabIndex={0}
        >
          {MailPoet.I18n.t('setUp')}
        </Button>
      ),
    },
    {
      slug: 'notification',
      title: MailPoet.I18n.t('postNotificationNewsletterTypeTitle'),
      description: MailPoet.I18n.t('postNotificationNewsletterTypeDescription'),
      videoGuide:
        'https://kb.mailpoet.com/article/210-video-guide-to-post-notifications',
      videoGuideBeacon: '59ba6fb3042863033a1cd5a5',
      action: (
        <Button
          automationId="create_notification"
          onClick={createNotificationNewsletter}
          withSpinner={isCreating}
          tabIndex={0}
          onKeyDown={(event): void => {
            if (
              ['keydown', 'keypress'].includes(event.type) &&
              ['Enter', ' '].includes(event.key)
            ) {
              event.preventDefault();
              createNotificationNewsletter();
            }
          }}
        >
          {MailPoet.I18n.t('setUp')}
        </Button>
      ),
    },
    {
      slug: 're_engagement',
      title: MailPoet.I18n.t('tabReEngagementTitle'),
      description: MailPoet.I18n.t('reEngagementDescription'),
      action: (
        <Button
          automationId="create_notification"
          onClick={createReEngagementNewsletter}
          withSpinner={isCreating}
          tabIndex={0}
          onKeyDown={(event): void => {
            if (
              ['keydown', 'keypress'].includes(event.type) &&
              ['Enter', ' '].includes(event.key)
            ) {
              event.preventDefault();
              createReEngagementNewsletter();
            }
          }}
        >
          {MailPoet.I18n.t('setUp')}
        </Button>
      ),
    },
  ];

  let types = Hooks.applyFilters(
    'mailpoet_newsletters_types',
    [...defaultTypes],
    this,
  );
  if (filter) {
    types = types.filter(filter);
  }

  const templatesGETUrl = MailPoet.Ajax.constructGetUrl({
    api_version: MailPoet.apiVersion,
    endpoint: 'newsletterTemplates',
    action: 'getAll',
  });

  return (
    <>
      {hideScreenOptions && <HideScreenOptions />}
      <link
        rel="prefetch"
        href={window.mailpoet_editor_javascript_url}
        as="script"
      />

      <div className="mailpoet-newsletter-types">
        {!hideClosingButton && (
          <div className="mailpoet-newsletter-types-close">
            <button
              type="button"
              onClick={(): void => history.push('/')}
              className="mailpoet-modal-close"
            >
              {ModalCloseIcon}
            </button>
          </div>
        )}

        {types.map((type) => renderType(type), this)}

        {getAutomaticEmails()}
      </div>

      <link rel="prefetch" href={templatesGETUrl} as="fetch" />
    </>
  );
}

NewsletterTypes.defaultProps = {
  filter: null,
  hideScreenOptions: true,
  hideClosingButton: false,
};

export default withRouter(
  NewsletterTypes as ComponentType<RouteComponentProps>,
);
