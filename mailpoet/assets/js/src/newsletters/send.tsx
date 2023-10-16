import _ from 'lodash';
import { ChangeEvent, Component, ContextType } from 'react';
import jQuery from 'jquery';
import { __, _x, sprintf } from '@wordpress/i18n';
import { History, Location } from 'history';
import ReactStringReplace from 'react-string-replace';
import slugify from 'slugify';
import { match as RouterMatch, withRouter } from 'react-router-dom';

import { Background } from 'common/background/background';
import { Button, ErrorBoundary } from 'common';
import { Form } from 'form/form.jsx';
import { Grid } from 'common/grid';
import { ListingHeadingStepsRoute } from 'newsletters/listings/heading-steps-route';
import { MailPoet } from 'mailpoet';
import { StandardNewsletterFields } from 'newsletters/send/standard';
import { NotificationNewsletterFields } from 'newsletters/send/notification.jsx';
import { WelcomeNewsletterFields } from 'newsletters/send/welcome.jsx';
import { AutomaticEmailFields } from 'newsletters/send/automatic.jsx';
import { ReEngagementNewsletterFields } from 'newsletters/send/re-engagement';
import { Tooltip } from 'help-tooltip.jsx';
import { fromUrl } from 'common/thumbnail';
import { GlobalContext } from 'context';

import { extractEmailDomain } from 'common/functions';
import { NewsLetter, NewsletterType } from 'common/newsletter';
import { mapFilterType } from '../analytics';
import { PremiumModal, premiumValidAndActive } from '../common/premium-modal';
import { PendingNewsletterMessage } from './send/pending-newsletter-message';
import { SendContext, SendContextType } from './send-context';

const automaticEmails = window.mailpoet_woocommerce_automatic_emails || {};

const generateGaTrackingCampaignName = (
  id: NewsLetter['id'],
  subject: NewsLetter['subject'],
): string => {
  const name = slugify(subject, { strict: true, lower: true });
  return `${name || 'email'}-${id}`;
};

type NewsletterSendComponentProps = {
  match: RouterMatch<{
    id: string;
  }>;
  history: History;
  location: Location;
};

type NewsletterSendComponentState = {
  fields: Record<string, unknown>[] | boolean;
  item: NewsLetter;
  loading: boolean;
  thumbnailPromise?: Promise<unknown>;
  showPremiumModal: boolean;
  premiumModalMessage?: string;
  validationError?: string | JSX.Element;
  mssKeyPendingApproval: boolean;
  sendContextValue: SendContextType;
};

const getTimingValueForTracking = (emailOpts: NewsLetter['options']) =>
  emailOpts.afterTimeType === 'immediate'
    ? 'immediate'
    : `${emailOpts.afterTimeNumber} ${emailOpts.afterTimeType}`;

function validateNewsletter(newsletter: NewsLetter) {
  let body;
  let content;

  // Don't validate emails created in the new editor.
  // The editor uses a different data format and will have own validation and also own send panel.
  // We are using the send page for the new editor only temporarily.
  if (
    MailPoet.FeaturesController.isSupported('gutenberg_email_editor') &&
    newsletter.wp_post_id !== null
  ) {
    return undefined;
  }

  if (newsletter && newsletter.body && newsletter.body.content) {
    content = newsletter.body.content;
    body = JSON.stringify(newsletter.body.content);
    if (
      !content.blocks ||
      !Array.isArray(content.blocks) ||
      content.blocks.length === 0
    ) {
      return __(
        'Poet, please add prose to your masterpiece before you send it to your followers.',
        'mailpoet',
      );
    }
  }

  if (
    window.mailpoet_mss_active &&
    body.indexOf('[link:subscription_unsubscribe_url]') < 0 &&
    body.indexOf('[link:subscription_unsubscribe]') < 0
  ) {
    return __(
      'All emails must include an "Unsubscribe" link. Add a footer widget to your email to continue.',
      'mailpoet',
    );
  }

  if (
    newsletter.type === 're_engagement' &&
    body.indexOf('[link:subscription_re_engage_url]') < 0
  ) {
    return __(
      'A re-engagement email must include a link with [link:subscription_re_engage_url] shortcode.',
      'mailpoet',
    );
  }

  if (
    newsletter.type === 'notification' &&
    body.indexOf('"type":"automatedLatestContent"') < 0 &&
    body.indexOf('"type":"automatedLatestContentLayout"') < 0
  ) {
    return _x(
      'Please add an “Automatic Latest Content” widget to the email from the right sidebar.',
      '(Please reuse the current translation used for the string “Automatic Latest Content”) This Error message is displayed when a user tries to send a “Post Notification” email without any “Automatic Latest Content” widget inside',
      'mailpoet',
    );
  }

  if (newsletter.type === 'standard' && newsletter.status === 'sent') {
    return __(
      'This email has already been sent. It can be edited, but not sent again. Duplicate this email if you want to send it again.',
      'mailpoet',
    );
  }

  if (
    newsletter.type === 're_engagement' &&
    !MailPoet.trackingConfig.emailTrackingEnabled
  ) {
    return (
      <span style={{ pointerEvents: 'all' }}>
        {ReactStringReplace(
          __(
            'Re-engagement emails are disabled because [link]open and click tracking[/link] is disabled.',
            'mailpoet',
          ),
          /\[link\](.*?)\[\/link\]/g,
          (match) => (
            <a
              key="advancedSettingsTabLink"
              href="?page=mailpoet-settings#/advanced"
              rel="noopener noreferrer"
            >
              {match}
            </a>
          ),
        )}
      </span>
    );
  }
  return undefined;
}

class NewsletterSendComponent extends Component<
  NewsletterSendComponentProps,
  NewsletterSendComponentState
> {
  // eslint-disable-next-line react/static-property-placement
  declare context: ContextType<typeof GlobalContext>;

  constructor(props: Readonly<NewsletterSendComponentProps>) {
    super(props);
    this.saveDraftNewsletter = this.saveDraftNewsletter.bind(this);
    this.state = {
      fields: [],
      item: {} as NewsLetter,
      loading: true,
      thumbnailPromise: null,
      showPremiumModal: false,
      mssKeyPendingApproval: window.mailpoet_mss_key_pending_approval,
      sendContextValue: { saveDraftNewsletter: this.saveDraftNewsletter },
    };
  }

  componentDidMount() {
    // safe to ignore since even on rejection the state is updated
    void this.loadItem(this.props.match.params.id).always(() => {
      this.setState({ loading: false });
    });
    jQuery('#mailpoet_newsletter').parsley({
      successClass: '', // Disable green inputs for the validation because it's not consistent for select2 and our custom validations
    });
  }

  componentDidUpdate(prevProps) {
    if (this.props.match.params.id !== prevProps.match.params.id) {
      // safe to ignore since even on rejection the state is updated
      void this.loadItem(this.props.match.params.id).always(() => {
        this.setState({ loading: false });
      });
    }
  }

  getFieldsByNewsletter = (newsletter: NewsLetter) => {
    const type = this.getSubtype(newsletter);
    return type.getFields(newsletter);
  };

  getSendButtonOptions = () => {
    const type = this.getSubtype(this.state.item);
    return type.getSendButtonOptions(this.state.item);
  };

  getSubtype = (newsletter: NewsLetter) => {
    if (
      newsletter.type === NewsletterType.Automatic &&
      automaticEmails[newsletter.options.group]
    ) {
      return AutomaticEmailFields;
    }

    switch (newsletter.type) {
      case 'notification':
        return NotificationNewsletterFields;
      case 'welcome':
        return WelcomeNewsletterFields;
      case 're_engagement':
        return ReEngagementNewsletterFields;
      // fall through
      default:
        return StandardNewsletterFields;
    }
  };

  getThumbnailPromise = (url) => this.state?.thumbnailPromise ?? fromUrl(url);

  isValid = () => jQuery('#mailpoet_newsletter').parsley().isValid();

  isValidFromAddress = async () => {
    if (window.mailpoet_mta_method !== 'MailPoet') {
      return true;
    }
    const verifiedDomains = await this.loadVerifiedSenderDomains();
    const senderDomain = extractEmailDomain(this.state.item.sender_address);
    if (verifiedDomains.indexOf(senderDomain) !== -1) {
      // allow user send with any email address from verified domain
      return true;
    }
    const addresses = await this.loadAuthorizedEmailAddresses();
    const fromAddress = this.state.item.sender_address;
    return addresses.indexOf(fromAddress) !== -1;
  };

  loadItem = (id) => {
    this.setState({ loading: true });

    return MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'get',
      data: {
        id,
      },
    })
      .done((response: { data: NewsLetter; meta: { preview_url: string } }) => {
        const thumbnailPromise =
          response.data.status === 'draft'
            ? this.getThumbnailPromise(response.meta.preview_url)
            : null;
        const item = response.data;
        // Automation type emails should redirect
        // to an associated automation from the send page
        if (item.type === NewsletterType.Automation) {
          const automationId = item.options?.automationId;
          const goToUrl = automationId
            ? `admin.php?page=mailpoet-automation-editor&id=${automationId}`
            : '/new';
          return this.setState(
            {
              item: {} as NewsLetter,
            },
            () => {
              this.props.history.push(goToUrl);
            },
          );
        }
        if (!item.ga_campaign && premiumValidAndActive) {
          item.ga_campaign = generateGaTrackingCampaignName(
            item.id,
            item.subject,
          );
        }
        const searchParams = new URLSearchParams(this.props.location.search);
        const filterSegmentId = searchParams.get('filterSegmentId');

        if (filterSegmentId) {
          response.data.options.filterSegmentId = filterSegmentId;
        }

        this.setState({
          item: response.data,
          fields: this.getFieldsByNewsletter(response.data),
          thumbnailPromise,
          validationError: validateNewsletter(response.data),
        });
        return true;
      })
      .fail(() => {
        this.setState(
          {
            item: {} as NewsLetter,
          },
          () => {
            this.props.history.push('/new');
          },
        );
      });
  };

  saveTemplate = (response, done) => {
    const thumbnailPromise = this.getThumbnailPromise(
      response.meta.preview_url,
    );
    thumbnailPromise
      .then((thumbnailData) => {
        void MailPoet.Ajax.post({
          api_version: window.mailpoet_api_version,
          endpoint: 'newsletterTemplates',
          action: 'save',
          data: {
            newsletter_id: response.data.id,
            name: response.data.subject,
            thumbnail_data: thumbnailData,
            body: JSON.stringify(response.data.body),
            categories: '["recent"]',
          },
        }).fail((err) => {
          this.showError(err);
          this.setState({ loading: false });
          MailPoet.Modal.loading(false);
        });
        done();
      })
      .catch((err) => {
        this.showError({ errors: [err] });
      });
  };

  loadAuthorizedEmailAddresses = async () => {
    if (window.mailpoet_mta_method !== 'MailPoet') {
      return [];
    }
    const response = await MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'mailer',
      action: 'getAuthorizedEmailAddresses',
    });
    const authorizedEmails = response.data || [];
    window.mailpoet_authorized_emails = authorizedEmails;
    return authorizedEmails;
  };

  loadVerifiedSenderDomains = async () => {
    if (window.mailpoet_mta_method !== 'MailPoet') {
      return [];
    }
    const response = await MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'mailer',
      action: 'getVerifiedSenderDomains',
    });
    return response.data || [];
  };

  handleSend = (e) => {
    e.preventDefault();

    if (!this.isValid()) {
      return jQuery('#mailpoet_newsletter').parsley().validate();
    }

    MailPoet.Modal.loading(true);

    return this.isValidFromAddress().then((valid) => {
      if (!valid) {
        // handling invalid error message is handled in sender_address_field component
        window.mailpoet_sender_address_field_blur();
        MailPoet.Modal.loading(false);
      } else {
        void this.saveNewsletter()
          .done(() => {
            this.setState({ loading: true });
          })
          .done((response) => {
            switch (response.data.type) {
              case 'notification':
              case 'welcome':
              case 'automatic':
              case 're_engagement':
                void this.activateNewsletter(response);
                break;
              default:
                void this.sendNewsletter(response);
                break;
            }
          })
          .fail((err) => {
            this.showError(err);
            this.setState({ loading: false });
            MailPoet.Modal.loading(false);
          });
      }
    });
  };

  sendNewsletter = (saveResponse: { data: NewsLetter }) =>
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'sendingQueue',
      action: 'add',
      data: {
        newsletter_id: this.state.item.id,
      },
    })
      .done((response) => {
        // save template in recently sent category
        this.saveTemplate(saveResponse, () => {
          if (window.mailpoet_show_congratulate_after_first_newsletter) {
            MailPoet.Modal.loading(false);
            this.props.history.push(`/send/congratulate/${this.state.item.id}`);
            return;
          }
          // redirect to listing based on newsletter type
          this.props.history.push(`/${this.state.item.type || ''}`);
          // prepare segments
          let filters = [];
          saveResponse.data.segments.map((segment) =>
            filters.push(...segment.filters),
          );
          filters = _.uniqWith(
            filters,
            (filterA, filterB) =>
              filterA.action === filterB.action &&
              filterA.type === filterB.type,
          );
          const segments = filters
            .map((filter) => mapFilterType(filter))
            .join(', ');
          const wasScheduled = response.data.status === 'scheduled';
          MailPoet.trackEvent('Emails > Newsletter sent', {
            scheduled: wasScheduled,
            'Segment Applied': !!this.state.item.options.filterSegmentId,
            segments,
          });
          if (wasScheduled) {
            this.context.notices.success(
              <p>{__('The newsletter has been scheduled.', 'mailpoet')}</p>,
            );
          } else {
            this.context.notices.success(
              <p>{__('The newsletter is being sent...', 'mailpoet')}</p>,
              { id: 'mailpoet_notice_being_sent' },
            );
          }
          MailPoet.Modal.loading(false);
        });
      })
      .fail((err) => {
        this.showError(err);
        this.setState({ loading: false });
        MailPoet.Modal.loading(false);
      });

  activateNewsletter = (saveResponse: { data: NewsLetter }) =>
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'setStatus',
      data: {
        id: this.props.match.params.id,
        status: 'active',
      },
    })
      .done((response) => {
        // save template in recently sent category
        this.saveTemplate(saveResponse, () => {
          if (window.mailpoet_show_congratulate_after_first_newsletter) {
            MailPoet.Modal.loading(false);
            this.props.history.push(`/send/congratulate/${this.state.item.id}`);
            return;
          }
          // redirect to listing based on newsletter type
          const opts = this.state.item.options;
          this.props.history.push(
            this.state.item.type === 'automatic'
              ? `/${opts.group}`
              : `/${this.state.item.type || ''}`,
          );
          // display success message depending on newsletter type
          if (
            this.state.item.type === 'automatic' &&
            automaticEmails[opts.group]
          ) {
            this.context.notices.success(
              <p>
                {sprintf(
                  __('Your %1s Automatic Email is now activated!', 'mailpoet'),
                  automaticEmails[opts.group]?.title ?? '',
                )}
              </p>,
            );
            MailPoet.trackEvent('Emails > Automatic email activated', {
              Type: slugify(`${opts.group}-${opts.event}`),
              Delay: getTimingValueForTracking(opts),
            });
          } else if (response.data.type === 'welcome') {
            this.context.notices.success(
              <p>{__('Your Welcome Email is now activated!', 'mailpoet')}</p>,
            );
            MailPoet.trackEvent('Emails > Welcome email activated', {
              'List type': opts.event,
              Delay: getTimingValueForTracking(opts),
            });
          } else if (response.data.type === 're_engagement') {
            this.context.notices.success(
              <p>
                {__('Your Re-engagement Email is now activated!', 'mailpoet')}
              </p>,
            );
            MailPoet.trackEvent('Emails > Re-engagement email activated', {
              Inactivity: getTimingValueForTracking(opts),
            });
          } else if (response.data.type === 'notification') {
            this.context.notices.success(
              <p>{__('Your post notification is now active!', 'mailpoet')}</p>,
            );
            MailPoet.trackEvent('Emails > Post notifications activated', {
              Frequency: opts.intervalType,
            });
          }
          MailPoet.Modal.loading(false);
        });
      })
      .fail((err) => {
        this.showError(err);
        this.setState({ loading: false });
        MailPoet.Modal.loading(false);
      });

  handleResume = (e) => {
    e.preventDefault();
    if (!this.isValid()) {
      jQuery('#mailpoet_newsletter').parsley().validate();
    } else {
      void this.saveNewsletter()
        .done(() => {
          this.setState({ loading: true });
        })
        .done(() => {
          void MailPoet.Ajax.post({
            api_version: window.mailpoet_api_version,
            endpoint: 'sendingQueue',
            action: 'resume',
            data: {
              newsletter_id: this.state.item.id,
            },
          })
            .done(() => {
              this.props.history.push(`/${this.state.item.type || ''}`);
              this.context.notices.success(
                <p>
                  {__('The newsletter sending has been resumed.', 'mailpoet')}
                </p>,
              );
            })
            .fail((response) => {
              this.showError(response);
            });
        })
        .fail((err) => {
          this.showError(err);
        })
        .always(() => {
          this.setState({ loading: false });
        });
    }
    return false;
  };

  handleSave = (e) => {
    e.preventDefault();

    void this.saveNewsletter()
      .done(() => {
        this.context.notices.success(
          <p>{__('Email was updated successfully!', 'mailpoet')}</p>,
        );
      })
      .done(() => {
        const path =
          this.state.item.type === 'automatic'
            ? this.state.item.options.group
            : this.state.item.type;
        this.props.history.push(`/${path || ''}`);
      })
      .fail((err) => {
        this.showError(err);
      });
  };

  handleRedirectToDesign = (e) => {
    e.preventDefault();
    const redirectTo = e.target.href;

    void this.saveNewsletter()
      .done(() => {
        this.context.notices.success(
          <p>{__('Email was updated successfully!', 'mailpoet')}</p>,
        );
      })
      .done(() => {
        window.location = redirectTo;
      })
      .fail((err) => {
        this.showError(err);
      });
  };

  saveNewsletter = () => {
    const data = this.state.item;
    data.queue = undefined;
    this.setState({ loading: true });

    // Store only properties that can be changed on this page
    const IGNORED_NEWSLETTER_PROPERTIES = [
      'body',
      'created_at',
      'deleted_at',
      'hash',
      'status',
      'updated_at',
      'type',
    ];
    const newsletterData = _.omit(data, IGNORED_NEWSLETTER_PROPERTIES);

    return MailPoet.Ajax.post<{ data: NewsLetter }>({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'save',
      data: newsletterData,
    });
  };

  showError = (response) => {
    if (response.errors.length > 0) {
      this.context.notices.error(
        response.errors.map((error) => (
          <p key={error.message}>{error.message}</p>
        )),
        {
          scroll: true,
          timeout: false,
        },
      );
    }
  };

  handleFormChange = (e: ChangeEvent<HTMLFormElement & { value: string }>) => {
    const name = e.target.name;
    const value = e.target.value;
    this.setState((prevState: NewsletterSendComponentState) => {
      const item = { ...prevState.item };
      const oldSubject = item.subject;
      const oldGaCampaign = item.ga_campaign;

      item[name] = value;

      if (name === 'subject') {
        const oldDefaultGaCampaign = generateGaTrackingCampaignName(
          item.id,
          oldSubject,
        );

        // regenerate GA campaign name only if it has default autogenerated value
        if (oldGaCampaign === oldDefaultGaCampaign) {
          item.ga_campaign = generateGaTrackingCampaignName(item.id, value);
        }
      }
      if (name === 'reply_to_address') {
        item[name] = value.toLowerCase();
      }

      return { item };
    });

    return true;
  };

  handleSaveDraft = () => {
    // Disabling all validations when saving a draft
    jQuery('#mailpoet_newsletter').parsley().destroy();
  };

  disableSegmentsSelectorWhenPaused = (isPaused) => (field) => {
    if (field.name === 'segments' || field.name === 'options') {
      return { ...field, disabled: isPaused };
    }
    return field;
  };

  disableGAIfPremiumInactive = () => (field) => {
    if (premiumValidAndActive || field.name !== 'ga_campaign') {
      return field;
    }

    const onWrapperClick = () =>
      this.setState({
        showPremiumModal: true,
        premiumModalMessage: __(
          'Google Analytics tracking is not available in the free version of the MailPoet plugin.',
          'mailpoet',
        ),
      });

    return {
      ...field,
      disabled: true,
      onWrapperClick,
    };
  };

  disableFilterSegmentToggleIfPremiumInactive = () => (field) => {
    // The filter segment is currently only ever a subfield
    if (!Array.isArray(field.fields)) {
      return field;
    }
    const newField = { ...field };
    newField.fields = newField.fields.map((subField) => {
      if (subField.name !== 'filter-segment-toggle' || premiumValidAndActive) {
        return subField;
      }
      const onWrapperClick = (event) => {
        event.preventDefault();
        this.setState({
          showPremiumModal: true,
          premiumModalMessage: __(
            'Filtering by segment is not available in the free version of the MailPoet plugin.',
            'mailpoet',
          ),
        });
      };
      return {
        ...subField,
        disabled: true,
        onWrapperClick,
      };
    });
    return newField;
  };

  getPreparedFields = (isPaused) => {
    if (!Array.isArray(this.state.fields)) {
      return [];
    }
    return this.state.fields
      .map(this.disableSegmentsSelectorWhenPaused(isPaused))
      .map(this.disableGAIfPremiumInactive())
      .map(this.disableFilterSegmentToggleIfPremiumInactive());
  };

  closePremiumModal = () => this.setState({ showPremiumModal: false });

  toggleLoadingState = (loading: boolean): void => this.setState({ loading });

  updatePendingApprovalState = (mssKeyPendingApproval: boolean): void =>
    this.setState({ mssKeyPendingApproval });

  saveDraftNewsletter = async (): Promise<void> => {
    this.handleSaveDraft();
    await this.saveNewsletter();
  };

  render() {
    const {
      showPremiumModal,
      item: { status, queue, type, options, wp_post_id: wpPostId },
      mssKeyPendingApproval,
    } = this.state;
    const isPaused = status === 'sending' && queue && queue.status === 'paused';
    const sendButtonOptions = this.getSendButtonOptions();
    const fields = this.getPreparedFields(isPaused);

    const sendingDisabled = !!(
      window.mailpoet_subscribers_limit_reached ||
      mssKeyPendingApproval ||
      this.state.validationError !== undefined
    );

    let emailType: string = type;
    if (emailType === NewsletterType.Automatic) {
      emailType = options.group || emailType;
    }

    return (
      <div className="mailpoet-form-send-email">
        <Background color="#fff" />
        <ListingHeadingStepsRoute
          emailType={emailType}
          automationId="newsletter_send_heading"
        />
        <ErrorBoundary>
          <SendContext.Provider value={this.state.sendContextValue}>
            <Form
              id="mailpoet_newsletter"
              fields={fields}
              automationId="newsletter_send_form"
              item={this.state.item}
              loading={this.state.loading}
              onChange={this.handleFormChange}
              onSubmit={this.handleSave}
            >
              <Grid.CenteredRow className="send-newsletter-buttons">
                <Button
                  variant="secondary"
                  type="submit"
                  automationId="email-save-draft"
                  onClick={this.handleSaveDraft}
                  isDisabled={this.state.loading}
                >
                  {__('Save as draft and close', 'mailpoet')}
                </Button>
                {isPaused ? (
                  <Button
                    type="button"
                    onClick={this.handleResume}
                    isDisabled={sendingDisabled || this.state.loading}
                    automationId="email-resume"
                  >
                    {__('Resume', 'mailpoet')}
                  </Button>
                ) : (
                  <Button
                    type="button"
                    onClick={this.handleSend}
                    {...sendButtonOptions}
                    isDisabled={sendingDisabled || this.state.loading}
                    automationId="email-submit"
                  >
                    {sendButtonOptions.value || __('Send', 'mailpoet')}
                  </Button>
                )}
                {this.state.validationError !== undefined && (
                  <Tooltip
                    tooltip={<div>{this.state.validationError}</div>}
                    tooltipId="helpTooltipSendEmail"
                  />
                )}
              </Grid.CenteredRow>
              <p>
                {__('or simply', 'mailpoet')}
                &nbsp;
                <a
                  className="mailpoet-link"
                  href={
                    MailPoet.FeaturesController.isSupported(
                      'gutenberg_email_editor',
                    ) && wpPostId
                      ? `admin.php?page=mailpoet-email-editor&postId=${wpPostId}`
                      : `?page=mailpoet-newsletter-editor&id=${this.props.match.params.id}`
                  }
                  onClick={this.handleRedirectToDesign}
                >
                  {__('go back to the Design page', 'mailpoet')}
                </a>
                .
              </p>

              {mssKeyPendingApproval && (
                <PendingNewsletterMessage
                  toggleLoadingState={this.toggleLoadingState}
                  updatePendingState={this.updatePendingApprovalState}
                />
              )}

              {showPremiumModal && (
                <PremiumModal onRequestClose={this.closePremiumModal}>
                  {this.state.premiumModalMessage}
                </PremiumModal>
              )}
            </Form>
          </SendContext.Provider>
        </ErrorBoundary>
      </div>
    );
  }
}

NewsletterSendComponent.contextType = GlobalContext;
export const NewsletterSend = withRouter(NewsletterSendComponent);
