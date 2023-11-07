import { useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import {
  Button,
  ButtonGroup,
  Dropdown,
  MenuItem,
  MenuGroup,
} from '@wordpress/components';
import { chevronDown, Icon } from '@wordpress/icons';
import { MailPoet } from 'mailpoet';
import { Heading } from 'common/typography/heading/heading';
import { Grid } from 'common/grid';
import {
  confirmAlert,
  FilterSegmentTag,
  SegmentTags,
  Tag,
  getNewsletterStatusString,
} from 'common';
import { NewsletterType } from './newsletter-type';

const redirectToNewsletterHome = () => {
  window.location.href = '?page=mailpoet-newsletters';
};

const getEditorLink = (newsletter: NewsletterType) => {
  let editorHref = `?page=mailpoet-newsletter-editor&id=${newsletter.id}`;
  if (
    MailPoet.FeaturesController.isSupported('gutenberg_email_editor') &&
    newsletter.wp_post_id
  ) {
    editorHref = `admin.php?page=mailpoet-email-editor&postId=${newsletter.wp_post_id}`;
  }

  return editorHref;
};

const editNewsletter = (newsletter: NewsletterType) => {
  const editorHref = getEditorLink(newsletter);

  if (
    !newsletter.queue ||
    newsletter.status !== 'sending' ||
    newsletter.queue.status !== null
  ) {
    window.location.href = editorHref;
  } else {
    confirmAlert({
      message: __(
        'Sending is in progress. Do you want to pause sending and edit the newsletter?',
        'mailpoet',
      ),
      onConfirm: () => {
        window.location.href = `${editorHref}&pauseConfirmed=yes`;
      },
    });
  }
};

const duplicateNewsletter = (
  newsletter: NewsletterType,
  performActionAfterUpdate = () => {},
) => {
  void MailPoet.Ajax.post({
    api_version: window.mailpoet_api_version,
    endpoint: 'newsletters',
    action: 'duplicate',
    data: {
      id: newsletter.id,
    },
  })
    .done((response) => {
      const editorHref = getEditorLink(response.data as NewsletterType);

      MailPoet.Notice.success(
        sprintf(
          __(
            'Email "%s" has been duplicated. New email: <a href="%s"> %s </a>',
            'mailpoet',
          ),
          newsletter.subject,
          editorHref,
          response.data.subject,
        ),
        { static: true },
      );
    })
    .fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map((error) => error.message),
          { scroll: true },
        );
      }
    })
    .always(() => {
      performActionAfterUpdate();
    });
};

const trashNewsletter = (
  newsletter: NewsletterType,
  performActionAfterUpdate = () => {},
) => {
  void MailPoet.Ajax.post({
    api_version: window.mailpoet_api_version,
    endpoint: 'newsletters',
    action: 'delete',
    data: {
      id: newsletter.id,
    },
  })
    .done(() => {
      MailPoet.Notice.success(
        __('Email "%1$s" has been deleted.', 'mailpoet').replace(
          '%1$s',
          newsletter.subject,
        ),
      );
      redirectToNewsletterHome();
    })
    .fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map((error) => error.message),
          { scroll: true },
        );
      }
    })
    .always(() => {
      performActionAfterUpdate();
    });
};

type Props = {
  newsletter: NewsletterType;
};

function NewsletterStatsInfo({ newsletter }: Props) {
  const [isBusy, setIsBusy] = useState(false);
  const newsletterDate =
    newsletter?.queue?.scheduled_at ||
    newsletter?.queue?.created_at ||
    newsletter?.created_at;
  return (
    <Grid.ThreeColumns className="mailpoet-stats-info">
      <div>
        <Heading level={1}>
          {newsletter.campaign_name
            ? newsletter.campaign_name
            : newsletter.subject}
          {newsletter.campaign_name && (
            <span>{` (${newsletter.subject})`}</span>
          )}
        </Heading>
        <div>
          <Tag isInverted={false}>
            {getNewsletterStatusString(newsletter.status)}
          </Tag>
          &nbsp;
          <b>
            {MailPoet.Date.short(newsletterDate)}
            {' • '}
            {MailPoet.Date.time(newsletterDate)}
          </b>
        </div>
        {Array.isArray(newsletter.segments) && newsletter.segments.length && (
          <div>
            <span className="mailpoet-stats-info-key">
              {__('To', 'mailpoet')}
            </span>
            {': '}
            <SegmentTags dimension="large" segments={newsletter.segments} />
            <FilterSegmentTag newsletter={newsletter} dimension="large" />
          </div>
        )}
      </div>
      <div className="mailpoet-stats-info-sender-preview">
        <div>
          <div className="mailpoet-stats-info-key-value">
            <span className="mailpoet-stats-info-key">
              {__('From', 'mailpoet')}
              {': '}
            </span>
            {newsletter.sender_address ? newsletter.sender_address : '-'}
          </div>
          <div className="mailpoet-stats-info-key-value">
            <span className="mailpoet-stats-info-key">
              {__('Reply-to', 'mailpoet')}
              {': '}
            </span>
            {newsletter.reply_to_address ? newsletter.reply_to_address : '-'}
          </div>
          <div className="mailpoet-stats-info-key-value">
            <span className="mailpoet-stats-info-key">
              {__('GA campaign', 'mailpoet')}
              {': '}
            </span>
            {newsletter.ga_campaign ? newsletter.ga_campaign : '-'}
          </div>
        </div>
      </div>
      <div className="mailpoet-stats-button-group">
        <ButtonGroup>
          <Button
            href={newsletter.preview_url}
            target="_blank"
            rel="noopener noreferrer"
            variant="secondary"
          >
            {__('Preview', 'mailpoet')}
          </Button>
          <Dropdown
            className="mailpoet-stats-has-margin-left"
            focusOnMount={false}
            popoverProps={{ placement: 'bottom-end' }}
            renderToggle={({ isOpen, onToggle }) => (
              <ButtonGroup>
                <Button
                  disabled={newsletter.type !== 'standard'}
                  onClick={() => {
                    editNewsletter(newsletter);
                  }}
                  variant="primary"
                >
                  {__('Edit', 'mailpoet')}
                </Button>
                <Button
                  onClick={onToggle}
                  aria-expanded={isOpen}
                  variant="primary"
                >
                  &nbsp;
                  <Icon icon={chevronDown} size={18} />
                </Button>
              </ButtonGroup>
            )}
            renderContent={() => (
              <MenuGroup>
                <MenuItem
                  isBusy={isBusy}
                  className="mailpoet-no-box-shadow"
                  variant="tertiary"
                  disabled={newsletter.type !== 'standard'}
                  onClick={() => {
                    setIsBusy(true);
                    duplicateNewsletter(newsletter, () => {
                      setIsBusy(false);
                    });
                  }}
                >
                  {__('Duplicate', 'mailpoet')}
                </MenuItem>
                <MenuItem
                  isBusy={isBusy}
                  isDestructive
                  onClick={() => {
                    setIsBusy(true);
                    trashNewsletter(newsletter, () => {
                      setIsBusy(false);
                    });
                  }}
                >
                  {__('Move to Trash', 'mailpoet')}
                </MenuItem>
              </MenuGroup>
            )}
          />
        </ButtonGroup>
      </div>
    </Grid.ThreeColumns>
  );
}

NewsletterStatsInfo.displayName = 'NewsletterStatsInfo';
export { NewsletterStatsInfo };
