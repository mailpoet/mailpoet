import React from 'react';
import MailPoet from 'mailpoet';
import _ from 'underscore';
import ListingHeadingStepsRoute from 'newsletters/listings/heading_steps_route.jsx';
import { Button } from 'common';
import Form from 'form/form.jsx';
import StandardNewsletterFields from 'newsletters/send/standard.jsx';
import NotificationNewsletterFields from 'newsletters/send/notification.jsx';
import WelcomeNewsletterFields from 'newsletters/send/welcome.jsx';
import HelpTooltip from 'help-tooltip.jsx';
import jQuery from 'jquery';
import Background from 'common/background/background';
import { fromUrl } from 'common/thumbnail.ts';
import Hooks from 'wp-js-hooks';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router-dom';
import ReactStringReplace from 'react-string-replace';
import SubscribersLimitNotice from 'notices/subscribers_limit_notice.jsx';
import InvalidMssKeyNotice from 'notices/invalid_mss_key_notice';
import slugify from 'slugify';
import { GlobalContext } from 'context/index.jsx';

const generateGaTrackingCampaignName = (id, subject) => {
  const name = slugify(subject, { lower: true })
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/-$/, '');
  return `${name || 'newsletter'}_${id}`;
};

class NewsletterSend extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      fields: [],
      item: {},
      loading: true,
      thumbnailPromise: null,
    };
  }

  componentDidMount() {
    this.loadItem(this.props.match.params.id)
      .always(() => {
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
      case 'notification': return NotificationNewsletterFields;
      case 'welcome': return WelcomeNewsletterFields;
      default: return Hooks.applyFilters('mailpoet_newsletters_send_newsletter_fields', StandardNewsletterFields, newsletter);
    }
  };

  getThumbnailPromise = (url) => (
    this.state.thumbnailPromise ? this.state.thumbnailPromise : fromUrl(url)
  );

  isValid = () => jQuery('#mailpoet_newsletter').parsley().isValid();

  isValidFromAddress = async () => {
    if (window.mailpoet_mta_method !== 'MailPoet') {
      return true;
    }
    const addresses = await this.loadAuthorizedEmailAddresses();
    const fromAddress = this.state.item.sender_address;
    return addresses.indexOf(fromAddress) !== -1;
  }

  showInvalidFromAddressError = () => {
    let errorMessage = ReactStringReplace(
      MailPoet.I18n.t('newsletterInvalidFromAddress'),
      '%$1s',
      () => this.state.item.sender_address
    );
    errorMessage = ReactStringReplace(
      errorMessage,
      /\[link\](.*?)\[\/link\]/g,
      (match) => `<a href="https://account.mailpoet.com/authorization" target="_blank" rel="noopener noreferrer">${match}</a>`
    );
    jQuery('#field_sender_address')
      .parsley()
      .addError(
        'invalidFromAddress',
        { message: errorMessage.join(''), updateClass: true }
      );
  };

  removeInvalidFromAddressError = () => {
    jQuery('#field_sender_address')
      .parsley()
      .removeError(
        'invalidFromAddress',
        { updateClass: true }
      );
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
    }).done((response) => {
      const thumbnailPromise = response.data.status === 'draft' ? this.getThumbnailPromise(response.meta.preview_url) : null;
      const item = response.data;
      if (!item.ga_campaign) {
        item.ga_campaign = generateGaTrackingCampaignName(item.id, item.subject);
      }
      this.setState({
        item: response.data,
        fields: this.getFieldsByNewsletter(response.data),
        thumbnailPromise,
      });
    }).fail(() => {
      this.setState({
        item: {},
      }, () => {
        this.props.history.push('/new');
      });
    });
  };

  saveTemplate = (response, done) => {
    const thumbnailPromise = this.getThumbnailPromise(response.meta.preview_url);
    thumbnailPromise
      .then((thumbnail) => {
        MailPoet.Ajax.post({
          api_version: window.mailpoet_api_version,
          endpoint: 'newsletterTemplates',
          action: 'save',
          data: {
            newsletter_id: response.data.id,
            name: response.data.subject,
            thumbnail,
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
      return this.saveNewsletter(e).done(() => {
        this.setState({ loading: true });
      })
        .done((response) => {
          switch (response.data.type) {
            case 'notification':
            case 'welcome':
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

  sendNewsletter = (newsletter) => MailPoet.Ajax.post(
    Hooks.applyFilters(
      'mailpoet_newsletters_send_server_request_parameters',
      {
        api_version: window.mailpoet_api_version,
        endpoint: 'sendingQueue',
        action: 'add',
        data: {
          newsletter_id: this.state.item.id,
        },
      },
      this.state.item
    )
  ).done((response) => {
    // save template in recently sent category
    this.saveTemplate(newsletter, () => {
      if (window.mailpoet_show_congratulate_after_first_newsletter) {
        MailPoet.Modal.loading(false);
        this.props.history.push(`/send/congratulate/${this.state.item.id}`);
        return;
      }
      // redirect to listing based on newsletter type
      this.props.history.push(Hooks.applyFilters('mailpoet_newsletters_send_server_request_response_redirect', `/${this.state.item.type || ''}`, this.state.item));
      const customResponse = Hooks.applyFilters('mailpoet_newsletters_send_server_request_response', this.state.item, response);
      if (_.isFunction(customResponse)) {
        customResponse();
      } else if (response.data.status === 'scheduled') {
        this.context.notices.success(
          <p>{MailPoet.I18n.t('newsletterHasBeenScheduled')}</p>
        );
        MailPoet.trackEvent('Emails > Newsletter sent', {
          scheduled: true,
          'MailPoet Free version': window.mailpoet_version,
        });
      } else {
        this.context.notices.success(
          <p>{MailPoet.I18n.t('newsletterBeingSent')}</p>,
          { id: 'mailpoet_notice_being_sent' }
        );
        MailPoet.trackEvent('Emails > Newsletter sent', {
          scheduled: false,
          'MailPoet Free version': window.mailpoet_version,
        });
      }
      MailPoet.Modal.loading(false);
    });
  }).fail((err) => {
    this.showError(err);
    this.setState({ loading: false });
    MailPoet.Modal.loading(false);
  });

  activateNewsletter = (newsletter) => MailPoet.Ajax.post({
    api_version: window.mailpoet_api_version,
    endpoint: 'newsletters',
    action: 'setStatus',
    data: {
      id: this.props.match.params.id,
      status: 'active',
    },
  }).done((response) => {
    // save template in recently sent category
    this.saveTemplate(newsletter, () => {
      if (window.mailpoet_show_congratulate_after_first_newsletter) {
        MailPoet.Modal.loading(false);
        this.props.history.push(`/send/congratulate/${this.state.item.id}`);
        return;
      }
      // redirect to listing based on newsletter type
      this.props.history.push(`/${this.state.item.type || ''}`);
      const opts = this.state.item.options;
      // display success message depending on newsletter type
      if (response.data.type === 'welcome') {
        this.context.notices.success(
          <p>{MailPoet.I18n.t('welcomeEmailActivated')}</p>
        );
        MailPoet.trackEvent('Emails > Welcome email activated', {
          'MailPoet Free version': window.mailpoet_version,
          'List type': opts.event,
          Delay: `${opts.afterTimeNumber} ${opts.afterTimeType}`,
        });
      } else if (response.data.type === 'notification') {
        this.context.notices.success(
          <p>{MailPoet.I18n.t('postNotificationActivated')}</p>
        );
        MailPoet.trackEvent('Emails > Post notifications activated', {
          'MailPoet Free version': window.mailpoet_version,
          Frequency: opts.intervalType,
        });
      }
      MailPoet.Modal.loading(false);
    });
  }).fail((err) => {
    this.showError(err);
    this.setState({ loading: false });
    MailPoet.Modal.loading(false);
  });

  handleResume = (e) => {
    e.preventDefault();
    if (!this.isValid()) {
      jQuery('#mailpoet_newsletter').parsley().validate();
    } else {
      this.saveNewsletter(e).done(() => {
        this.setState({ loading: true });
      }).done(() => {
        MailPoet.Ajax.post({
          api_version: window.mailpoet_api_version,
          endpoint: 'sendingQueue',
          action: 'resume',
          data: {
            newsletter_id: this.state.item.id,
          },
        }).done(() => {
          this.props.history.push(`/${this.state.item.type || ''}`);
          this.context.notices.success(
            <p>{MailPoet.I18n.t('newsletterSendingHasBeenResumed')}</p>
          );
        }).fail((response) => {
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

    this.saveNewsletter(e).done(() => {
      this.context.notices.success(
        <p>{MailPoet.I18n.t('newsletterUpdated')}</p>
      );
    }).done(() => {
      const path = this.state.item.type === 'automatic' ? this.state.item.options.group : this.state.item.type;
      this.props.history.push(`/${path || ''}`);
    }).fail((err) => {
      this.showError(err);
    });
  };

  handleRedirectToDesign = (e) => {
    e.preventDefault();
    const redirectTo = e.target.href;

    this.saveNewsletter(e).done(() => {
      this.context.notices.success(
        <p>{MailPoet.I18n.t('newsletterUpdated')}</p>
      );
    }).done(() => {
      window.location = redirectTo;
    }).fail((err) => {
      this.showError(err);
    });
  };

  saveNewsletter = () => {
    const data = this.state.item;
    data.queue = undefined;
    this.setState({ loading: true });

    // Store only properties that can be changed on this page
    const IGNORED_NEWSLETTER_PROPERTIES = [
      'body', 'created_at', 'deleted_at', 'hash',
      'status', 'updated_at', 'type',
    ];
    const newsletterData = _.omit(
      data,
      IGNORED_NEWSLETTER_PROPERTIES
    );

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
        response.errors.map((error) => <p key={error.message}>{error.message}</p>),
        { scroll: true }
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
        const oldDefaultGaCampaign = generateGaTrackingCampaignName(item.id, oldSubject);

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

  render() {
    const isPaused = this.state.item.status === 'sending'
      && this.state.item.queue
      && this.state.item.queue.status === 'paused';
    const fields = this.state.fields.map((field) => {
      const newField = field;
      if (field.name === 'segments' || field.name === 'options') {
        newField.disabled = isPaused;
      }
      return newField;
    });
    const sendButtonOptions = this.getSendButtonOptions();

    const sendingDisabled = !!(window.mailpoet_subscribers_limit_reached
      || window.mailpoet_mss_key_pending_approval);

    let emailType = this.state.item.type;
    if (emailType === 'automatic') {
      emailType = this.state.item.options.group || emailType;
    }

    return (
      <div>
        <Background color="#fff" />
        <ListingHeadingStepsRoute emailType={emailType} automationId="newsletter_send_heading" />

        <Form
          id="mailpoet_newsletter"
          fields={fields}
          automationId="newsletter_send_form"
          item={this.state.item}
          loading={this.state.loading}
          onChange={this.handleFormChange}
          onSubmit={this.handleSave}
        >
          <SubscribersLimitNotice />
          <InvalidMssKeyNotice
            mssKeyInvalid={window.mailpoet_mss_key_invalid}
            subscribersCount={window.mailpoet_subscribers_count}
          />
          <p>
            <Button variant="light" type="submit">
              {MailPoet.I18n.t('saveDraftAndClose')}
            </Button>
            {
              isPaused
                ? (
                  <Button
                    type="button"
                    onClick={this.handleResume}
                    isDisabled={sendingDisabled}
                  >
                    {MailPoet.I18n.t('resume')}
                  </Button>
                )
                : (
                  <Button
                    type="button"
                    onClick={this.handleSend}
                    {...sendButtonOptions} // eslint-disable-line react/jsx-props-no-spreading
                    isDisabled={sendingDisabled}
                  >
                    {MailPoet.I18n.t('send')}
                  </Button>
                )
            }
          </p>
          <p>
            {MailPoet.I18n.t('orSimply')}
            &nbsp;
            <a
              className="mailpoet-link"
              href={
                `?page=mailpoet-newsletter-editor&id=${this.props.match.params.id}`
              }
              onClick={this.handleRedirectToDesign}
            >
              {MailPoet.I18n.t('goBackToDesign')}
            </a>
            .
          </p>
          { !isPaused && sendButtonOptions.disabled && sendButtonOptions.disabled === 'disabled' && (
            <HelpTooltip
              tooltip={MailPoet.I18n.t('helpTooltipSendEmail')}
              tooltipId="helpTooltipSendEmail"
            />
          ) }
          { window.mailpoet_mss_key_pending_approval && (
            <div className="mailpoet_error">
              {
                ReactStringReplace(
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
                  )
                )
              }
            </div>
          ) }
        </Form>
      </div>
    );
  }
}

NewsletterSend.contextType = GlobalContext;

NewsletterSend.propTypes = {
  match: PropTypes.shape({
    params: PropTypes.shape({
      id: PropTypes.string,
    }).isRequired,
  }).isRequired,
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
  }).isRequired,
};

export default withRouter(NewsletterSend);
