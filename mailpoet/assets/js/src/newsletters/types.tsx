import { ButtonGroup, Dropdown, MenuItem } from '@wordpress/components';
import { ComponentType, Fragment, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { chevronDown, Icon } from '@wordpress/icons';
import { MailPoet } from 'mailpoet';
import { Hooks } from 'wp-js-hooks';
import _ from 'underscore';
import { RouteComponentProps, withRouter } from 'react-router-dom';

import { AutomaticEmailEventsList } from 'newsletters/types/automatic-emails/events-list.jsx';
import { AutomaticEmailEventGroupLogos } from 'newsletters/types/automatic-emails/event-group-logos.jsx';
import { Button } from 'common/button/button';
import { Heading } from 'common/typography/heading/heading';
import { modalCloseIcon } from 'common/modal/close-icon';
import { EditorSelectModal } from 'newsletters/editor-select-modal';
import { HideScreenOptions } from 'common/hide-screen-options/hide-screen-options';
import { APIErrorsNotice } from '../notices/api-errors-notice';
import { isErrorResponse } from '../ajax';

interface Props {
  filter?: () => void;
  history: RouteComponentProps['history'];
  hideScreenOptions?: boolean;
  hideClosingButton?: boolean;
}

function NewsletterTypesComponent({
  filter,
  history,
  hideClosingButton = false,
  hideScreenOptions = true,
}: Props): JSX.Element {
  const [isCreating, setIsCreating] = useState(false);

  const [isSelectEditorModalOpen, setIsSelectEditorModalOpen] = useState(false);
  const isNewEmailEditorEnabled = MailPoet.FeaturesController.isSupported(
    'gutenberg_email_editor',
  );

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
            {type.title} {type.beta ? `(${__('Beta', 'mailpoet')})` : ''}
          </Heading>
          <p>{type.description}</p>
          {type.videoGuide && (
            <a
              className={badgeClassName}
              href={type.videoGuide}
              target="_blank"
              rel="noopener noreferrer"
            >
              <span className="dashicons dashicons-format-video" />
              {__('See video guide', 'mailpoet')}
            </a>
          )}
          {type.kbLink && (
            <a href={type.kbLink} target="_blank" rel="noopener noreferrer">
              {__('Read more.', 'mailpoet')}
            </a>
          )}
          <div className="mailpoet-flex-grow" />
          <div className="mailpoet-newsletter-type-action">{type.action}</div>
        </div>
      </div>
    );
  };

  const getRedirectToAutomateWooType = () => {
    const redirectToAutomateWoo = (): void => {
      MailPoet.trackEvent(
        'Emails > Type selected',
        {
          'Email type': 'woocommerce_automatewoo',
        },
        { send_immediately: true }, // This tell Mixpanel client to send events from buffer immediately
        () => {
          // Anonymous callback which does the redirect
          window.location.href = `edit.php?post_type=aw_workflow#presets`;
        },
      );
    };
    return {
      slug: 'woocommerce_automatewoo',
      title: __('Automations', 'mailpoet'),
      description: __(
        'Convert and retain customers with automated marketing that does the hard work for you. AutomateWoo has the tools you need to grow your store and make more money.',
        'mailpoet',
      ),
      kbLink:
        'https://kb.mailpoet.com/article/408-integration-with-automatewoo',
      action: (
        <Button
          automationId="woocommerce_automatewoo"
          onClick={redirectToAutomateWoo}
          tabIndex={0}
          onKeyDown={(event): void => {
            if (
              ['keydown', 'keypress'].includes(event.type) &&
              ['Enter', ' '].includes(event.key)
            ) {
              event.preventDefault();
              redirectToAutomateWoo();
            }
          }}
        >
          {__('Set up', 'mailpoet')}
        </Button>
      ),
    };
  };

  const getAdditionalTypes = (): Record<string, unknown>[] => {
    const show = MailPoet.isWoocommerceActive;
    if (!show) {
      return [];
    }
    const additionalTypes = [
      {
        slug: 'wc_transactional',
        title: __('WooCommerce Emails Customizer', 'mailpoet'),
        description: __(
          "Customize the template used for your WooCommerce emails using MailPoet's editor. Example of WooCommerce email: Order processing notification, Order failed notification, ...",
          'mailpoet',
        ),
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
            {__('Customize', 'mailpoet')}
          </Button>
        ),
      },
    ];
    if (MailPoet.hideAutomations) {
      additionalTypes.push(getRedirectToAutomateWooType());
    }
    return additionalTypes;
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
          {email.slug === 'woocommerce' && !MailPoet.hideAutomations && (
            <AutomaticEmailEventsList email={email} history={history} />
          )}
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
        subject: __('Subject', 'mailpoet'),
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

  const standardAction = isNewEmailEditorEnabled ? (
    <ButtonGroup className="mailpoet-dropdown-button-group">
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
        {__('Create', 'mailpoet')}
      </Button>
      <Dropdown
        focusOnMount={false}
        className="mailpoet-dropdown-button"
        contentClassName="mailpoet-dropdown-button-content"
        popoverProps={{ placement: 'bottom-end' }}
        renderToggle={({ isOpen, onToggle }) => (
          <Button
            className="mailpoet-button-with-wordpress-icon"
            onClick={onToggle}
            aria-expanded={isOpen}
          >
            <Icon icon={chevronDown} size={24} />
          </Button>
        )}
        renderContent={() => (
          <MenuItem
            variant="tertiary"
            onClick={() => setIsSelectEditorModalOpen(true)}
          >
            {__('Create using new editor (Beta)', 'mailpoet')}
          </MenuItem>
        )}
      />
    </ButtonGroup>
  ) : (
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
      {__('Create', 'mailpoet')}
    </Button>
  );
  const defaultTypes = [
    {
      slug: 'standard',
      title: __('Newsletter', 'mailpoet'),
      description: __(
        'Send a newsletter with images, buttons, dividers, and social bookmarks. Or, just send a basic text email.',
        'mailpoet',
      ),
      action: standardAction,
    },
    {
      slug: 'welcome',
      title: __('Welcome Email', 'mailpoet'),
      description: __(
        'Automatically send an email (or series of emails) to new subscribers or WordPress users. Send a day, a week, or a month after they sign up.',
        'mailpoet',
      ),
      videoGuide:
        'https://kb.mailpoet.com/article/254-video-guide-to-welcome-emails',
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
          {__('Set up', 'mailpoet')}
        </Button>
      ),
    },
    {
      slug: 'notification',
      title: __('Latest Post Notifications', 'mailpoet'),
      description: __(
        'Let MailPoet email your subscribers with your latest content. You can send daily, weekly, monthly, or even immediately after publication.',
        'mailpoet',
      ),
      videoGuide:
        'https://kb.mailpoet.com/article/210-video-guide-to-post-notifications',
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
          {__('Set up', 'mailpoet')}
        </Button>
      ),
    },
    {
      slug: 're_engagement',
      title: __('Re-engagement Emails', 'mailpoet'),
      description: __(
        'Automatically email and win back subscribers who have recently lost interest and stopped engaging with your emails.',
        'mailpoet',
      ),
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
          {__('Set up', 'mailpoet')}
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
              {modalCloseIcon}
            </button>
          </div>
        )}

        {types.map((type) => renderType(type), this)}

        {getAutomaticEmails()}
      </div>

      <link rel="prefetch" href={templatesGETUrl} as="fetch" />
      <EditorSelectModal
        onClose={() => setIsSelectEditorModalOpen(false)}
        isModalOpen={isSelectEditorModalOpen}
      />
    </>
  );
}

NewsletterTypesComponent.defaultProps = {
  filter: null,
  hideScreenOptions: true,
  hideClosingButton: false,
};

export const NewsletterTypes = withRouter(
  NewsletterTypesComponent as ComponentType<RouteComponentProps>,
);
