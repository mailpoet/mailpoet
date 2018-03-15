define(
  [
    'react',
    'react-router',
    'underscore',
    'mailpoet',
    'form/form.jsx',
    'newsletters/send/standard.jsx',
    'newsletters/send/notification.jsx',
    'newsletters/send/welcome.jsx',
    'newsletters/breadcrumb.jsx',
    'help-tooltip.jsx',
    'jquery',
    'common/thumbnail.jsx',
  ],
  (
    React,
    Router,
    _,
    MailPoet,
    Form,
    StandardNewsletterFields,
    NotificationNewsletterFields,
    WelcomeNewsletterFields,
    Breadcrumb,
    HelpTooltip,
    jQuery,
    Thumbnail
  ) => {
    const NewsletterSend = React.createClass({
      contextTypes: {
        router: React.PropTypes.object.isRequired,
      },
      getInitialState: function () {
        return {
          fields: [],
          item: {},
          loading: false,
        };
      },
      getFieldsByNewsletter: function (newsletter) {
        const type = this.getSubtype(newsletter);
        return type.getFields(newsletter);
      },
      getSendButtonOptions: function () {
        const type = this.getSubtype(this.state.item);
        return type.getSendButtonOptions(this.state.item);
      },
      getSubtype: function (newsletter) {
        switch (newsletter.type) {
          case 'notification': return NotificationNewsletterFields;
          case 'welcome': return WelcomeNewsletterFields;
          default: return StandardNewsletterFields;
        }
      },
      isValid: function () {
        return jQuery('#mailpoet_newsletter').parsley().isValid();
      },
      componentDidMount: function () {
        if (this.isMounted()) {
          this.loadItem(this.props.params.id);
        }
        jQuery('#mailpoet_newsletter').parsley();
      },
      componentWillReceiveProps: function (props) {
        this.loadItem(props.params.id);
      },
      loadItem: function (id) {
        this.setState({ loading: true });

        MailPoet.Ajax.post({
          api_version: window.mailpoet_api_version,
          endpoint: 'newsletters',
          action: 'get',
          data: {
            id: id,
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
      saveTemplate: function (response, done) {
        Thumbnail.fromUrl(response.meta.preview_url)
          .then(function (thumbnail) {
            MailPoet.Ajax.post({
              api_version: window.mailpoet_api_version,
              endpoint: 'newsletterTemplates',
              action: 'save',
              data: {
                newsletter_id: response.data.id,
                name: response.data.subject,
                description: response.data.preheader,
                thumbnail: thumbnail,
                body: JSON.stringify(response.data.body),
                categories: '["recent"]',
              },
            }).then(done).fail(this.showError);
          })
          .catch(err => this.showError({ errors: [err] }));
      },
      handleSend: function (e) {
        e.preventDefault();

        if (!this.isValid()) {
          jQuery('#mailpoet_newsletter').parsley().validate();
        } else {
          MailPoet.Modal.loading(true);
          this.saveNewsletter(e).done(() => {
            this.setState({ loading: true });
          }).done((response) => {
            switch (response.data.type) {
              case 'notification':
              case 'welcome':
                return MailPoet.Ajax.post({
                  api_version: window.mailpoet_api_version,
                  endpoint: 'newsletters',
                  action: 'setStatus',
                  data: {
                    id: this.props.params.id,
                    status: 'active',
                  },
                }).done((response2) => {
                  // save template in recently sent category
                  this.saveTemplate(response, () => {
                    // redirect to listing based on newsletter type
                    this.context.router.push(`/${this.state.item.type || ''}`);
                    const opts = this.state.item.options;
                    // display success message depending on newsletter type
                    if (response2.data.type === 'welcome') {
                      MailPoet.Notice.success(
                        MailPoet.I18n.t('welcomeEmailActivated')
                      );
                      MailPoet.trackEvent('Emails > Welcome email activated', {
                        'MailPoet Free version': window.mailpoet_version,
                        'List type': opts.event,
                        Delay: `${opts.afterTimeNumber} ${opts.afterTimeType}`,
                      });
                    } else if (response2.data.type === 'notification') {
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
              default:
                return MailPoet.Ajax.post({
                  api_version: window.mailpoet_api_version,
                  endpoint: 'sendingQueue',
                  action: 'add',
                  data: {
                    newsletter_id: this.props.params.id,
                  },
                }).done((response2) => {
                  // save template in recently sent category
                  this.saveTemplate(response, () => {
                    // redirect to listing based on newsletter type
                    this.context.router.push(`/${this.state.item.type || ''}`);

                    if (response2.data.status === 'scheduled') {
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
                })
                .fail((err) => {
                  this.showError(err);
                  this.setState({ loading: false });
                  MailPoet.Modal.loading(false);
                });
            }
          })
          .fail((err) => {
            this.showError(err);
            this.setState({ loading: false });
            MailPoet.Modal.loading(false);
          });
        }
        return false;
      },
      handleResume: function (e) {
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
      handleSave: function (e) {
        e.preventDefault();

        this.saveNewsletter(e).done(() => {
          MailPoet.Notice.success(
            MailPoet.I18n.t('newsletterUpdated')
          );
        }).done(() => {
          this.context.router.push(`/${this.state.item.type || ''}`);
        }).fail(this.showError);
      },
      handleRedirectToDesign: function (e) {
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
      saveNewsletter: function () {
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
      handleFormChange: function (e) {
        const item = this.state.item;
        const field = e.target.name;

        item[field] = e.target.value;

        this.setState({
          item: item,
        });
        return true;
      },
      render: function () {
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
        return (
          <div>
            <h1>{MailPoet.I18n.t('finalNewsletterStep')}</h1>

            <Breadcrumb step="send" />

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

    return NewsletterSend;
  }
);
