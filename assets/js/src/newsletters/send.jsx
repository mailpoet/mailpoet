import React from 'react';
import MailPoet from 'mailpoet';
import _ from 'underscore';
import Breadcrumb from 'newsletters/breadcrumb.jsx';
import Form from 'form/form.jsx';
import StandardNewsletterFields from 'newsletters/send/standard.jsx';
import NotificationNewsletterFields from 'newsletters/send/notification.jsx';
import WelcomeNewsletterFields from 'newsletters/send/welcome.jsx';
import HelpTooltip from 'help-tooltip.jsx';
import jQuery from 'jquery';
import { fromUrl } from 'common/thumbnail.jsx';
import Hooks from 'wp-js-hooks';

const NewsletterSend = React.createClass({
  contextTypes: {
    router: React.PropTypes.object.isRequired,
  },
  getInitialState: function getInitialState() {
    return {
      fields: [],
      item: {},
      loading: true,
    };
  },
  getFieldsByNewsletter: function getFieldsByNewsletter(newsletter) {
    const type = this.getSubtype(newsletter);
    return type.getFields(newsletter);
  },
  getSendButtonOptions: function getSendButtonOptions() {
    const type = this.getSubtype(this.state.item);
    return type.getSendButtonOptions(this.state.item);
  },
  getSubtype: function getSubtype(newsletter) {
    switch (newsletter.type) {
      case 'notification': return NotificationNewsletterFields;
      case 'welcome': return WelcomeNewsletterFields;
      default: return Hooks.applyFilters('mailpoet_newsletters_send_newsletter_fields', StandardNewsletterFields, newsletter);
    }
  },
  isValid: function isValid() {
    return jQuery('#mailpoet_newsletter').parsley().isValid();
  },
  componentDidMount: function componentDidMount() {
    this.loadItem(this.props.params.id);
    jQuery('#mailpoet_newsletter').parsley();
  },
  componentWillReceiveProps: function componentWillReceiveProps(props) {
    this.loadItem(props.params.id);
  },
  loadItem: function loadItem(id) {
    this.setState({ loading: true });

    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'get',
      data: {
        id,
      },
    }).done((response) => {
      this.setState({
        loading: false,
        item: response.data,
        fields: this.getFieldsByNewsletter(response.data),
      });
    }).fail(() => {
      this.setState({
        loading: false,
        item: {},
      }, () => {
        this.context.router.push('/new');
      });
    });
  },
  saveTemplate: function saveTemplate(response, done) {
    fromUrl(response.meta.preview_url)
      .then(function saveTemplateAjax(thumbnail) {
        MailPoet.Ajax.post({
          api_version: window.mailpoet_api_version,
          endpoint: 'newsletterTemplates',
          action: 'save',
          data: {
            newsletter_id: response.data.id,
            name: response.data.subject,
            description: response.data.preheader,
            thumbnail,
            body: JSON.stringify(response.data.body),
            categories: '["recent"]',
          },
        }).then(done).fail(this.showError);
      })
      .catch(err => this.showError({ errors: [err] }));
  },
  handleSend: function handleSend(e) {
    e.preventDefault();

    if (!this.isValid()) {
      return jQuery('#mailpoet_newsletter').parsley().validate();
    }

    MailPoet.Modal.loading(true);

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
  },
  sendNewsletter: function sendNewsletter(newsletter) {
    return MailPoet.Ajax.post(
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
        // redirect to listing based on newsletter type
        this.context.router.push(Hooks.applyFilters('mailpoet_newsletters_send_server_request_response_redirect', `/${this.state.item.type || ''}`, this.state.item));
        const customResponse = Hooks.applyFilters('mailpoet_newsletters_send_server_request_response', this.state.item, response);
        if (_.isFunction(customResponse)) {
          customResponse();
        } else if (response.data.status === 'scheduled') {
          MailPoet.Notice.success(
            MailPoet.I18n.t('newsletterHasBeenScheduled')
          );
          MailPoet.trackEvent('Emails > Newsletter sent', {
            scheduled: true,
            'MailPoet Free version': window.mailpoet_version,
          });
        } else {
          MailPoet.Notice.success(
            MailPoet.I18n.t('newsletterBeingSent')
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
  },
  activateNewsletter: function activateEmail(newsletter) {
    return MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'setStatus',
      data: {
        id: this.props.params.id,
        status: 'active',
      },
    }).done((response) => {
      // save template in recently sent category
      this.saveTemplate(newsletter, () => {
        // redirect to listing based on newsletter type
        this.context.router.push(`/${this.state.item.type || ''}`);
        const opts = this.state.item.options;
        // display success message depending on newsletter type
        if (response.data.type === 'welcome') {
          MailPoet.Notice.success(
            MailPoet.I18n.t('welcomeEmailActivated')
          );
          MailPoet.trackEvent('Emails > Welcome email activated', {
            'MailPoet Free version': window.mailpoet_version,
            'List type': opts.event,
            Delay: `${opts.afterTimeNumber} ${opts.afterTimeType}`,
          });
        } else if (response.data.type === 'notification') {
          MailPoet.Notice.success(
            MailPoet.I18n.t('postNotificationActivated')
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
  },
  handleResume: function handleResume(e) {
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
          this.context.router.push(`/${this.state.item.type || ''}`);
          MailPoet.Notice.success(
            MailPoet.I18n.t('newsletterSendingHasBeenResumed')
          );
        }).fail((response) => {
          if (response.errors.length > 0) {
            MailPoet.Notice.error(
              response.errors.map(error => error.message),
              { scroll: true }
            );
          }
        });
      })
      .fail(this.showError)
      .always(() => {
        this.setState({ loading: false });
      });
    }
    return false;
  },
  handleSave: function handleSave(e) {
    e.preventDefault();

    this.saveNewsletter(e).done(() => {
      MailPoet.Notice.success(
        MailPoet.I18n.t('newsletterUpdated')
      );
    }).done(() => {
      this.context.router.push(`/${this.state.item.type || ''}`);
    }).fail(this.showError);
  },
  handleRedirectToDesign: function handleRedirectToDesign(e) {
    e.preventDefault();
    const redirectTo = e.target.href;

    this.saveNewsletter(e).done(() => {
      MailPoet.Notice.success(
        MailPoet.I18n.t('newsletterUpdated')
      );
    }).done(() => {
      window.location = redirectTo;
    }).fail(this.showError);
  },
  saveNewsletter: function saveNewsletter() {
    const data = this.state.item;
    data.queue = undefined;
    this.setState({ loading: true });

    // Store only properties that can be changed on this page
    const IGNORED_NEWSLETTER_PROPERTIES = [
      'preheader', 'body', 'created_at', 'deleted_at', 'hash',
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
    }).always(() => {
      this.setState({ loading: false });
    });
  },
  showError: (response) => {
    if (response.errors.length > 0) {
      MailPoet.Notice.error(
        response.errors.map(error => error.message),
        { scroll: true }
      );
    }
  },
  handleFormChange: function handleFormChange(e) {
    const item = this.state.item;
    const field = e.target.name;

    item[field] = e.target.value;

    this.setState({
      item,
    });
    return true;
  },
  render: function render() {
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
    const breadcrumb = Hooks.applyFilters(
      'mailpoet_newsletters_send_breadcrumb',
      <Breadcrumb step="send" />,
      this.state.item.type,
      'send'
    );

    return (
      <div>
        <h1>{MailPoet.I18n.t('finalNewsletterStep')}</h1>

        {breadcrumb}

        <Form
          id="mailpoet_newsletter"
          fields={fields}
          item={this.state.item}
          loading={this.state.loading}
          onChange={this.handleFormChange}
          onSubmit={this.handleSave}
        >
          <p className="submit">
            {
              isPaused ?
                <input
                  className="button button-primary"
                  type="button"
                  onClick={this.handleResume}
                  value={MailPoet.I18n.t('resume')}
                />
              :
                <input
                  className="button button-primary"
                  type="button"
                  onClick={this.handleSend}
                  value={MailPoet.I18n.t('send')}
                  {...sendButtonOptions}
                />
            }
            &nbsp;
            <input
              className="button button-secondary"
              type="submit"
              value={MailPoet.I18n.t('saveDraftAndClose')}
            />
            &nbsp;{MailPoet.I18n.t('orSimply')}&nbsp;
            <a
              href={
                `?page=mailpoet-newsletter-editor&id=${this.props.params.id}`
              }
              onClick={this.handleRedirectToDesign}
            >
              {MailPoet.I18n.t('goBackToDesign')}
            </a>.
          </p>
          { !isPaused && sendButtonOptions.disabled && sendButtonOptions.disabled === 'disabled' && (
            <HelpTooltip
              tooltip={MailPoet.I18n.t('helpTooltipSendEmail')}
              tooltipId="helpTooltipSendEmail"
            />
          ) }
        </Form>
      </div>
    );
  },
});

module.exports = NewsletterSend;
