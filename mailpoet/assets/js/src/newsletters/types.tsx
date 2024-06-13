import {
  Button,
  ButtonGroup,
  Dropdown,
  MenuItem as WpMenuItem,
} from '@wordpress/components';
import { ComponentType, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { chevronDown, Icon } from '@wordpress/icons';
import { MailPoet } from 'mailpoet';
import { Hooks } from 'wp-js-hooks';
import _ from 'underscore';
import { RouteComponentProps, withRouter } from 'react-router-dom';
import { Heading } from 'common/typography/heading/heading';
import { EditorSelectModal } from 'newsletters/editor-select-modal';
import { HideScreenOptions } from 'common/hide-screen-options/hide-screen-options';
import { APIErrorsNotice } from '../notices/api-errors-notice';
import { Info } from './types/info';

interface Props {
  filter?: () => void;
  history: RouteComponentProps['history'];
  hideScreenOptions?: boolean;
}

// Menu Item type definition in @wordpress/components is missing variant property
const MenuItem = WpMenuItem as React.FC<
  React.ComponentProps<typeof WpMenuItem> & { variant: string }
>;

function NewsletterTypesComponent({
  history,
  filter = null,
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
  const createReEngagementNewsletter = _.partial(
    setupNewsletter,
    're-engagement',
  );
  const createAutomation = () => {
    setIsCreating(true);
    window.location.href = 'admin.php?page=mailpoet-automation-templates';
  };

  const standardAction = isNewEmailEditorEnabled ? (
    <ButtonGroup className="mailpoet-dropdown-button-group">
      <Button
        variant="secondary"
        onClick={createStandardNewsletter}
        isBusy={isCreating}
        data-automation-id="create_standard"
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
            variant="secondary"
            className="mailpoet-button-with-wordpress-icon"
            onClick={onToggle}
            aria-expanded={isOpen}
            data-automation-id="create_standard_email_dropdown"
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
      variant="secondary"
      onClick={createStandardNewsletter}
      isBusy={isCreating}
      data-automation-id="create_standard"
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
      slug: 'automations',
      title: (
        <>
          {__('Automations', 'mailpoet')}{' '}
          <Info>
            {__(
              'Automations such as Welcome emails and WooCommerce emails can be found here, alongside more automation templates powered by our new editor.',
              'mailpoet',
            )}
          </Info>
        </>
      ),
      description: __(
        'Set up automated emails like welcome emails, abandoned cart reminders or one of our many automation templates to inform, engage and reward your audience.',
        'mailpoet',
      ),
      action: (
        <Button
          variant="secondary"
          onClick={createAutomation}
          isBusy={isCreating}
          data-automation-id="create_automation"
        >
          {__('Create', 'mailpoet')}
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
          variant="secondary"
          onClick={createNotificationNewsletter}
          isBusy={isCreating}
          data-automation-id="create_notification"
        >
          {__('Create', 'mailpoet')}
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
          variant="secondary"
          onClick={createReEngagementNewsletter}
          isBusy={isCreating}
          data-automation-id="create_notification"
        >
          {__('Create', 'mailpoet')}
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
        {types.map((type) => renderType(type), this)}
      </div>

      <link rel="prefetch" href={templatesGETUrl} as="fetch" />
      <EditorSelectModal
        onClose={() => setIsSelectEditorModalOpen(false)}
        isModalOpen={isSelectEditorModalOpen}
      />
    </>
  );
}

export const NewsletterTypes = withRouter(
  NewsletterTypesComponent as ComponentType<RouteComponentProps>,
);
