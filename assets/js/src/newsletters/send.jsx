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
    'newsletters/breadcrumb.jsx'
  ],
  function (
    React,
    Router,
    _,
    MailPoet,
    Form,
    StandardNewsletterFields,
    NotificationNewsletterFields,
    WelcomeNewsletterFields,
    Breadcrumb
  ) {

    var NewsletterSend = React.createClass({
      contextTypes: {
        router: React.PropTypes.object.isRequired
      },
      getInitialState: function () {
        return {
          fields: [],
          item: {},
          loading: false,
        };
      },
      getFieldsByNewsletter: function (newsletter) {
        var type = this.getSubtype(newsletter);
        return type.getFields(newsletter);
      },
      getSendButtonOptions: function () {
        var type = this.getSubtype(this.state.item);
        return type.getSendButtonOptions(this.state.item);
      },
      getSubtype: function (newsletter) {
        switch(newsletter.type) {
          case 'notification': return NotificationNewsletterFields;
          case 'welcome': return WelcomeNewsletterFields;
          default: return StandardNewsletterFields;
        }
      },
      isValid: function () {
        return jQuery('#mailpoet_newsletter').parsley().isValid();
      },
      componentDidMount: function () {
        if(this.isMounted()) {
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
            id: id
          }
        }).done((response) => {
          this.setState({
            loading: false,
            item: response.data,
            fields: this.getFieldsByNewsletter(response.data)
          });
        }).fail((response) => {
          this.setState({
            loading: false,
            item: {}
          }, () => {
            this.context.router.push('/new');
          });
        });
      },
      handleSend: function (e) {
        e.preventDefault();

        if(!this.isValid()) {
          jQuery('#mailpoet_newsletter').parsley().validate();
        } else {
          this._save(e).done(() => {
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
                    status: 'active'
                  }
                }).done((response) => {
                  // redirect to listing based on newsletter type
                  this.context.router.push(`/${ this.state.item.type || '' }`);

                  // display success message depending on newsletter type
                  if (response.data.type === 'welcome') {
                    MailPoet.Notice.success(
                      MailPoet.I18n.t('welcomeEmailActivated')
                    );
                  } else if (response.data.type === 'notification') {
                    MailPoet.Notice.success(
                      MailPoet.I18n.t('postNotificationActivated')
                    );
                  }
                }).fail(this._showError);
              default:
                return MailPoet.Ajax.post({
                  api_version: window.mailpoet_api_version,
                  endpoint: 'sendingQueue',
                  action: 'add',
                  data: {
                    newsletter_id: this.props.params.id
                  }
                }).done((response) => {
                  // redirect to listing based on newsletter type
                  this.context.router.push(`/${ this.state.item.type || '' }`);

                  if (response.data.status === 'scheduled') {
                    MailPoet.Notice.success(
                      MailPoet.I18n.t('newsletterHasBeenScheduled')
                    );
                  } else {
                    MailPoet.Notice.success(
                      MailPoet.I18n.t('newsletterBeingSent')
                    );
                  }
                }).fail(this._showError);
            }
          }).fail(this._showError).always(() => {
            this.setState({ loading: false });
          });
        }
        return false;
      },
      handleSave: function (e) {
        e.preventDefault();

        this._save(e).done((response) => {
          MailPoet.Notice.success(
            MailPoet.I18n.t('newsletterUpdated')
          );
        }).done(() => {
          this.context.router.push(`/${ this.state.item.type || '' }`);
        }).fail(this._showError);
      },
      handleRedirectToDesign: function (e) {
        e.preventDefault();
        var redirectTo = e.target.href;

        this._save(e).done((response) => {
          MailPoet.Notice.success(
            MailPoet.I18n.t('newsletterUpdated')
          );
        }).done(() => {
          window.location = redirectTo;
        }).fail(this._showError);
      },
      _save: function (e) {
        var data = this.state.item;
        this.setState({ loading: true });

        // Store only properties that can be changed on this page
        const IGNORED_NEWSLETTER_PROPERTIES = [
            'preheader', 'body', 'created_at', 'deleted_at', 'hash',
            'status', 'updated_at', 'type'
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
      _showError: (response) => {
        if (response.errors.length > 0) {
          MailPoet.Notice.error(
            response.errors.map(function (error) { return error.message; }),
            { scroll: true }
          );
        }
      },
      handleFormChange: function (e) {
        var item = this.state.item,
          field = e.target.name;

        item[field] = e.target.value;

        this.setState({
          item: item
        });
        return true;
      },
      render: function () {
        return (
          <div>
            <h1>{MailPoet.I18n.t('finalNewsletterStep')}</h1>

            <Breadcrumb step="send" />

            <Form
              id="mailpoet_newsletter"
              fields={ this.state.fields }
              item={ this.state.item }
              loading={ this.state.loading }
              onChange={this.handleFormChange}
              onSubmit={this.handleSave}
            >
              <p className="submit">
                <input
                  className="button button-primary"
                  type="button"
                  onClick={ this.handleSend }
                  value={MailPoet.I18n.t('send')}
                  {...this.getSendButtonOptions()}
                  />
                &nbsp;
                <input
                  className="button button-secondary"
                  type="submit"
                  value={MailPoet.I18n.t('saveDraftAndClose')} />
                &nbsp;{MailPoet.I18n.t('orSimply')}&nbsp;
                <a
                  href={
                    '?page=mailpoet-newsletter-editor&id='+this.props.params.id
                  }
                  onClick={this.handleRedirectToDesign}>
                  {MailPoet.I18n.t('goBackToDesign')}
                </a>.
              </p>
            </Form>
          </div>
        );
      }
    });

    return NewsletterSend;
  }
);
