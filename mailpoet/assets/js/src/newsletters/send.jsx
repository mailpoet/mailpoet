import _ from 'underscore';
import { Component } from 'react';
import jQuery from 'jquery';
import PropTypes from 'prop-types';
import ReactStringReplace from 'react-string-replace';
import slugify from 'slugify';
import { withRouter } from 'react-router-dom';

import { Background } from 'common/background/background';
import { Button } from 'common';
import { Form } from 'form/form.jsx';
import { Grid } from 'common/grid';
import { ListingHeadingStepsRoute } from 'newsletters/listings/heading_steps_route';
import { MailPoet } from 'mailpoet';
import { StandardNewsletterFields } from 'newsletters/send/standard';
import { NotificationNewsletterFields } from 'newsletters/send/notification.jsx';
import { WelcomeNewsletterFields } from 'newsletters/send/welcome.jsx';
import { AutomaticEmailFields } from 'newsletters/send/automatic.jsx';
import { ReEngagementNewsletterFields } from 'newsletters/send/re_engagement';
import { Tooltip } from 'help-tooltip.jsx';
import { fromUrl } from 'common/thumbnail.ts';

import { GlobalContext } from 'context/index.jsx';

const automaticEmails = window.mailpoet_woocommerce_automatic_emails || [];

const generateGaTrackingCampaignName = (id, subject) => {
  const name = slugify(subject, { strict: true, lower: true });
  return `${name || 'email'}-${id}`;
};

function validateNewsletter(newsletter) {
  let body;
  let content;

  if (newsletter && newsletter.body && newsletter.body.content) {
    content = newsletter.body.content;
    body = JSON.stringify(newsletter.body.content);
    if (
      !content.blocks ||
      !Array.isArray(content.blocks) ||
      content.blocks.length === 0
    ) {
      return MailPoet.I18n.t('newsletterIsEmpty');
    }
  }

  if (
    window.mailpoet_mss_active &&
    body.indexOf('[link:subscription_unsubscribe_url]') < 0 &&
    body.indexOf('[link:subscription_unsubscribe]') < 0
  ) {
    return MailPoet.I18n.t('unsubscribeLinkMissing');
  }

  if (
    newsletter.type === 're_engagement' &&
    body.indexOf('[link:subscription_re_engage_url]') < 0
  ) {
    return MailPoet.I18n.t('reEngageLinkMissing');
  }

  if (
    newsletter.type === 'notification' &&
    body.indexOf('"type":"automatedLatestContent"') < 0 &&
    body.indexOf('"type":"automatedLatestContentLayout"') < 0
  ) {
    return MailPoet.I18n.t('automatedLatestContentMissing');
  }

  if (newsletter.type === 'standard' && newsletter.status === 'sent') {
    return MailPoet.I18n.t('emailAlreadySent');
  }

  if (
    newsletter.type === 're_engagement' &&
    !MailPoet.trackingConfig.emailTrackingEnabled
  ) {
    return (
      <span style={{ pointerEvents: 'all' }}>
        {ReactStringReplace(
          MailPoet.I18n.t('reEngagementEmailsDisableIfTrackingIs'),
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

class NewsletterSendComponent extends Component {
  constructor(props) {
    super(props);
    this.state = {
      fields: [],
      item: {},
      loading: true,
      thumbnailPromise: null,
      isSavingDraft: false,
    };
  }

  componentDidMount() {
    this.loadItem(this.props.match.params.id).always(() => {
      this.setState({ loading: false });
    });
    jQuery('#mailpoet_newsletter').parsley();
  }

  componentDidUpdate(prevProps) {
    if (this.props.match.params.id !== prevProps.match.params.id) {
      this.loadItem(this.props.match.params.id).always(() => {
        this.setState({ loading: false });
      });
    }
  }

  getFieldsByNewsletter = (newsletter) => {
    const type = this.getSubtype(newsletter);
    return type.getFields(newsletter);
  };

  getSendButtonOptions = () => {
    const type = this.getSubtype(this.state.item);
    return type.getSendButtonOptions(this.state.item);
  };

  getSubtype = (newsletter) => {
    switch (newsletter.type) {
      case 'notification':
        return NotificationNewsletterFields;
      case 'welcome':
        return WelcomeNewsletterFields;
      case 're_engagement':
        return ReEngagementNewsletterFields;
      case 'automatic':
        if (automaticEmails[newsletter.options.group]) {
          return AutomaticEmailFields;
        }
      // fall through
      default:
        return StandardNewsletterFields;
    }
  };

  getThumbnailPromise = (url) =>
    this.state.thumbnailPromise ? this.state.thumbnailPromise : fromUrl(url);

  isValid = () => jQuery('#mailpoet_newsletter').parsley().isValid();

  isValidFromAddress = async () => {
    if (window.mailpoet_mta_method !== 'MailPoet') {
      return true;
    }
    const addresses = await this.loadAuthorizedEmailAddresses();
    const fromAddress = this.state.item.sender_address;
    return addresses.indexOf(fromAddress) !== -1;
  };

  showInvalidFromAddressError = () => {
    const fromAddress = this.state.item.sender_address;
    let errorMessage = ReactStringReplace(
      MailPoet.I18n.t('newsletterInvalidFromAddress'),
      '%1$s',
      () => fromAddress,
    );
    errorMessage = ReactStringReplace(
      errorMessage,
      /\[link\](.*?)\[\/link\]/g,
      (match) =>
        `<a href="https://account.mailpoet.com/authorization?email=${encodeURIComponent(
          fromAddress,
        )}" target="_blank" class="mailpoet-js-button-authorize-email-and-sender-domain" data-email="${fromAddress}" data-type="email" rel="noopener noreferrer">${match}</a>`,
    );
    jQuery('#field_sender_address')
      .parsley()
      .addError('invalidFromAddress', {
        message: errorMessage.join(''),
        updateClass: true,
      });
    MailPoet.trackEvent('Unauthorized email used', {
      'Unauthorized email source': 'send',
    });
  };

  removeInvalidFromAddressError = () => {
    jQuery('#field_sender_address')
      .parsley()
      .removeError('invalidFromAddress', { updateClass: true });
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
      .done((response) => {
        const thumbnailPromise =
          response.data.status === 'draft'
            ? this.getThumbnailPromise(response.meta.preview_url)
            : null;
        const item = response.data;
        // Automation type emails should redirect
        // to an associated workflow from the send page
        if (item.type === 'automation') {
          const workflowId = item.options?.workflowId;
          const goToUrl = workflowId
            ? `admin.php?page=mailpoet-automation-editor&id=${workflowId}`
            : '/new';
          return this.setState(
            {
              item: {},
            },
            () => {
              this.props.history.push(goToUrl);
            },
          );
        }
        if (!item.ga_campaign) {
          item.ga_campaign = generateGaTrackingCampaignName(
            item.id,
            item.subject,
          );
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
            item: {},
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
        MailPoet.Ajax.post({
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
    return response.data || [];
  };

  handleSend = (e) => {
    e.preventDefault();
    this.removeInvalidFromAddressError();

    if (!this.isValid()) {
      return jQuery('#mailpoet_newsletter').parsley().validate();
    }

    MailPoet.Modal.loading(true);

    return this.isValidFromAddress().then((valid) => {
      if (!valid) {
        this.showInvalidFromAddressError();
        return MailPoet.Modal.loading(false);
      }
      return this.saveNewsletter(e)
        .done(() => {
          this.setState({ loading: true });
        })
        .done((response) => {
          switch (response.data.type) {
            case 'notification':
            case 'welcome':
            case 'automatic':
            case 're_engagement':
              return this.activateNewsletter(response);
            default:
              return this.sendNewsletter(response);
          }
        })
        .fail((err) => {
          this.showError(err);
          this.setState({ loading: false });
          MailPoet.Modal.loading(false);
        });
    });
  };

  sendNewsletter = (newsletter) =>
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
        this.saveTemplate(newsletter, () => {
          if (window.mailpoet_show_congratulate_after_first_newsletter) {
            MailPoet.Modal.loading(false);
            this.props.history.push(`/send/congratulate/${this.state.item.id}`);
            return;
          }
          // redirect to listing based on newsletter type
          this.props.history.push(`/${this.state.item.type || ''}`);
          if (response.data.status === 'scheduled') {
            this.context.notices.success(
              <p>{MailPoet.I18n.t('newsletterHasBeenScheduled')}</p>,
            );
            MailPoet.trackEvent('Emails > Newsletter sent', {
              scheduled: true,
            });
          } else {
            this.context.notices.success(
              <p>{MailPoet.I18n.t('newsletterBeingSent')}</p>,
              { id: 'mailpoet_notice_being_sent' },
            );
            MailPoet.trackEvent('Emails > Newsletter sent', {
              scheduled: false,
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

  activateNewsletter = (newsletter) =>
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
        this.saveTemplate(newsletter, () => {
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
                {MailPoet.I18n.t('automaticEmailActivated').replace(
                  '%1s',
                  automaticEmails[opts.group].title,
                )}
              </p>,
            );
          } else if (response.data.type === 'welcome') {
            this.context.notices.success(
              <p>{MailPoet.I18n.t('welcomeEmailActivated')}</p>,
            );
            MailPoet.trackEvent('Emails > Welcome email activated', {
              'List type': opts.event,
              Delay: `${opts.afterTimeNumber} ${opts.afterTimeType}`,
            });
          } else if (response.data.type === 'notification') {
            this.context.notices.success(
              <p>{MailPoet.I18n.t('postNotificationActivated')}</p>,
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
      this.saveNewsletter(e)
        .done(() => {
          this.setState({ loading: true });
        })
        .done(() => {
          MailPoet.Ajax.post({
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
                <p>{MailPoet.I18n.t('newsletterSendingHasBeenResumed')}</p>,
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

    this.saveNewsletter(e)
      .done(() => {
        this.context.notices.success(
          <p>{MailPoet.I18n.t('newsletterUpdated')}</p>,
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

    this.saveNewsletter(e)
      .done(() => {
        this.context.notices.success(
          <p>{MailPoet.I18n.t('newsletterUpdated')}</p>,
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

    return MailPoet.Ajax.post({
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

  handleFormChange = (e) => {
    const name = e.target.name;
    const value = e.target.value;
    this.setState((prevState) => {
      const item = prevState.item;
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

  handleSaveDraft = () =>
    this.setState({
      isSavingDraft: true,
    });

  disableSegmentsValidation = (field) => {
    if (
      this.state.isSavingDraft &&
      field.name === 'segments' &&
      field.validation &&
      field.validation['data-parsley-required']
    ) {
      return {
        ...field,
        validation: {
          ...field.validation,
          'data-parsley-required': false,
        },
      };
    }

    return field;
  };

  disableSegmentsSelectorWhenPaused = (isPaused) => (field) => {
    if (field.name === 'segments' || field.name === 'options') {
      return { ...field, disabled: isPaused };
    }
    return field;
  };

  getPreparedFields = (isPaused) =>
    this.state.fields
      .map(this.disableSegmentsSelectorWhenPaused(isPaused))
      .map(this.disableSegmentsValidation);

  render() {
    const isPaused =
      this.state.item.status === 'sending' &&
      this.state.item.queue &&
      this.state.item.queue.status === 'paused';

    const sendButtonOptions = this.getSendButtonOptions();
    const fields = this.getPreparedFields(isPaused);

    const sendingDisabled = !!(
      window.mailpoet_subscribers_limit_reached ||
      window.mailpoet_mss_key_pending_approval ||
      this.state.validationError !== undefined
    );

    let emailType = this.state.item.type;
    if (emailType === 'automatic') {
      emailType = this.state.item.options.group || emailType;
    }

    return (
      <div className="mailpoet-form-send-email">
        <Background color="#fff" />
        <ListingHeadingStepsRoute
          emailType={emailType}
          automationId="newsletter_send_heading"
        />

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
              {MailPoet.I18n.t('saveDraftAndClose')}
            </Button>
            {isPaused ? (
              <Button
                type="button"
                onClick={this.handleResume}
                isDisabled={sendingDisabled || this.state.loading}
                automationId="email-resume"
              >
                {MailPoet.I18n.t('resume')}
              </Button>
            ) : (
              <Button
                type="button"
                onClick={this.handleSend}
                {...sendButtonOptions}
                isDisabled={sendingDisabled || this.state.loading}
                automationId="email-submit"
              >
                {sendButtonOptions.value || MailPoet.I18n.t('send')}
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
            {MailPoet.I18n.t('orSimply')}
            &nbsp;
            <a
              className="mailpoet-link"
              href={`?page=mailpoet-newsletter-editor&id=${this.props.match.params.id}`}
              onClick={this.handleRedirectToDesign}
            >
              {MailPoet.I18n.t('goBackToDesign')}
            </a>
            .
          </p>
          {window.mailpoet_mss_key_pending_approval && (
            <div className="mailpoet_error">
              {ReactStringReplace(
                MailPoet.I18n.t('pendingKeyApprovalNotice'),
                /\[link\](.*?)\[\/link\]/g,
                (match) => (
                  <a
                    key="pendingKeyApprovalNoticeLink"
                    href="https://account.mailpoet.com/authorization"
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    {match}
                  </a>
                ),
              )}
            </div>
          )}
        </Form>
      </div>
    );
  }
}

NewsletterSendComponent.contextType = GlobalContext;

NewsletterSendComponent.propTypes = {
  match: PropTypes.shape({
    params: PropTypes.shape({
      id: PropTypes.string,
    }).isRequired,
  }).isRequired,
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
  }).isRequired,
};

export const NewsletterSend = withRouter(NewsletterSendComponent);
