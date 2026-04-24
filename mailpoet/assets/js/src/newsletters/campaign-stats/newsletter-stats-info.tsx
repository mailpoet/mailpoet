import { useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import {
  Button,
  ButtonGroup,
  Dropdown,
  MenuItem as WpMenuItem,
  MenuGroup,
  Modal,
  TextControl,
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

// Menu Item type definition in @wordpress/components is missing variant and isBusy property
const MenuItem = WpMenuItem as React.FC<
  React.ComponentProps<typeof WpMenuItem> & {
    variant?: string;
    isBusy?: boolean;
  }
>;

const redirectToNewsletterHome = () => {
  window.location.href = '?page=mailpoet-newsletters';
};

const getEditorLink = (newsletter: NewsletterType) =>
  MailPoet.getActiveEmailEditorUrl(newsletter);

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
          (response.data as NewsletterType).subject,
        ),
        { static: true },
      );
    })
    .fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.showApiErrorNotice(response, { scroll: true });
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
    action: 'trash',
    data: {
      id: newsletter.id,
    },
  })
    .done(() => {
      MailPoet.Notice.success(
        __('1 email was moved to the trash.', 'mailpoet'),
      );
      redirectToNewsletterHome();
    })
    .fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.showApiErrorNotice(response, { scroll: true });
      }
    })
    .always(() => {
      performActionAfterUpdate();
    });
};

const MIN_RESEND_DELAY_HOURS = 24;
const MAX_RESEND_DELAY_HOURS = 72;

function isResendEligible(newsletter: NewsletterType): {
  eligible: boolean;
  reason?: string;
} {
  if (newsletter.type !== 'standard' || newsletter.status !== 'sent') {
    return { eligible: false };
  }
  const sentAt = newsletter.sent_at;
  if (!sentAt) {
    return {
      eligible: false,
      reason: __('No send date available.', 'mailpoet'),
    };
  }
  const hoursSinceSent =
    (Date.now() - new Date(sentAt).getTime()) / (1000 * 60 * 60);
  if (hoursSinceSent < MIN_RESEND_DELAY_HOURS) {
    const hoursLeft = Math.ceil(MIN_RESEND_DELAY_HOURS - hoursSinceSent);
    return {
      eligible: false,
      reason: sprintf(__('Available in %d hours.', 'mailpoet'), hoursLeft),
    };
  }
  if (hoursSinceSent > MAX_RESEND_DELAY_HOURS) {
    return {
      eligible: false,
      reason: __('Resend window expired (3 days).', 'mailpoet'),
    };
  }
  return { eligible: true };
}

type ResendModalProps = {
  newsletter: NewsletterType;
  onClose: () => void;
};

function ResendToNonOpenersModal({ newsletter, onClose }: ResendModalProps) {
  const defaultSubject = sprintf(
    // translators: %s is the subject of the original newsletter.
    __('Re: %s', 'mailpoet'),
    newsletter.subject,
  ) as string;
  const [subject, setSubject] = useState<string>(defaultSubject);
  const [isSending, setIsSending] = useState(false);

  const handleResend = () => {
    if (!subject.trim()) return;
    setIsSending(true);
    void MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'resendToNonOpeners',
      data: {
        id: newsletter.id,
        subject: subject.trim(),
      },
    })
      .done(() => {
        MailPoet.Notice.success(
          __('A copy of this email is being sent to non-openers.', 'mailpoet'),
          { static: true },
        );
        onClose();
      })
      .fail((response) => {
        setIsSending(false);
        if (response.errors.length > 0) {
          MailPoet.Notice.showApiErrorNotice(response, { scroll: true });
        }
      });
  };

  return (
    <Modal
      title={__('Resend to non-openers', 'mailpoet')}
      onRequestClose={onClose}
    >
      <p>
        {__(
          'This will send a copy of this email to subscribers who haven\u2019t opened it. A different subject line is required.',
          'mailpoet',
        )}
      </p>
      <TextControl
        label={__('New subject line', 'mailpoet')}
        value={subject}
        onChange={setSubject}
        help={__('Must be different from the original subject.', 'mailpoet')}
      />
      <div
        style={{
          display: 'flex',
          justifyContent: 'flex-end',
          gap: '8px',
          marginTop: '16px',
        }}
      >
        <Button variant="tertiary" onClick={onClose} disabled={isSending}>
          {__('Cancel', 'mailpoet')}
        </Button>
        <Button
          variant="primary"
          onClick={handleResend}
          isBusy={isSending}
          disabled={!subject.trim() || isSending}
        >
          {__('Resend', 'mailpoet')}
        </Button>
      </div>
    </Modal>
  );
}

type Props = {
  newsletter: NewsletterType;
};

function NewsletterStatsInfo({ newsletter }: Props) {
  const [isBusy, setIsBusy] = useState(false);
  const [isResendModalOpen, setIsResendModalOpen] = useState(false);
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
                {(() => {
                  const resendStatus = isResendEligible(newsletter);
                  if (
                    newsletter.type !== 'standard' ||
                    newsletter.status !== 'sent'
                  )
                    return null;
                  return (
                    <MenuItem
                      className="mailpoet-no-box-shadow"
                      variant="tertiary"
                      disabled={!resendStatus.eligible}
                      onClick={() => {
                        if (resendStatus.eligible) {
                          setIsResendModalOpen(true);
                        }
                      }}
                      info={resendStatus.reason}
                    >
                      {__('Resend to non-openers', 'mailpoet')}
                    </MenuItem>
                  );
                })()}
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
      {isResendModalOpen && (
        <ResendToNonOpenersModal
          newsletter={newsletter}
          onClose={() => setIsResendModalOpen(false)}
        />
      )}
    </Grid.ThreeColumns>
  );
}

NewsletterStatsInfo.displayName = 'NewsletterStatsInfo';
export { NewsletterStatsInfo };
