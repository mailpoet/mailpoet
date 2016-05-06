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
  function(
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
      getInitialState: function() {
        return {
          fields: [],
          item: {},
          loading: false,
        };
      },
      getFieldsByNewsletter: function(newsletter) {
        switch(newsletter.type) {
          case 'notification': return NotificationNewsletterFields(newsletter);
          case 'welcome': return WelcomeNewsletterFields(newsletter);
          default: return StandardNewsletterFields(newsletter);
        }
      },
      isAutomatedNewsletter: function() {
        return this.state.item.type !== 'standard';
      },
      isValid: function() {
        return jQuery('#mailpoet_newsletter').parsley().isValid();
      },
      componentDidMount: function() {
        if(this.isMounted()) {
          this.loadItem(this.props.params.id);
        }
        jQuery('#mailpoet_newsletter').parsley();
      },
      componentWillReceiveProps: function(props) {
        this.loadItem(props.params.id);
      },
      loadItem: function(id) {
        this.setState({ loading: true });

        MailPoet.Ajax.post({
          endpoint: 'newsletters',
          action: 'get',
          data: id
        }).done((response) => {
          if(response === false) {
            this.setState({
              loading: false,
              item: {},
            }, function() {
              this.context.router.push('/new');
            }.bind(this));
          } else {
            this.setState({
              loading: false,
              item: response,
              fields: this.getFieldsByNewsletter(response),
            });
          }
        });
      },
      handleSend: function(e) {
        e.preventDefault();

        if(!this.isValid()) {
          jQuery('#mailpoet_newsletter').parsley().validate();
        } else {
          this.setState({ loading: true });

          MailPoet.Ajax.post({
            endpoint: 'newsletters',
            action: 'save',
            data: this.state.item,
          }).then((response) => {
            if (response.result === true) {
              return MailPoet.Ajax.post({
                endpoint: 'sendingQueue',
                action: 'add',
                data: _.extend({}, this.state.item, {
                  newsletter_id: this.props.params.id,
                }),
              });
            } else {
              return response;
            }
          }).done((response) => {
            this.setState({ loading: false });
            if(response.result === true) {
              this.context.router.push('/');
              MailPoet.Notice.success(response.data.message);
            } else {
              if(response.errors) {
                MailPoet.Notice.error(response.errors);
              } else {
                MailPoet.Notice.error(
                  MailPoet.I18n.t('newsletterSendingError').replace("%$1s", '?page=mailpoet-settings')
                );
              }
            }
          });
        }
        return false;
      },
      handleSave: function(e) {
        e.preventDefault();
        this.setState({ loading: true });

        MailPoet.Ajax.post({
          endpoint: 'newsletters',
          action: 'save',
          data: this.state.item,
        }).done((response) => {
          this.setState({ loading: false });

          if(response.result === true) {
            this.context.router.push('/');
            MailPoet.Notice.success(
              MailPoet.I18n.t('newsletterUpdated')
            );
          } else {
            if(response.errors) {
              MailPoet.Notice.error(response.errors);
            }
          }
        });
      },
      handleFormChange: function(e) {
        var item = this.state.item,
          field = e.target.name;

        item[field] = e.target.value;

        this.setState({
          item: item
        });
        return true;
      },
      render: function() {
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
                  value={
                    this.isAutomatedNewsletter()
                    ? MailPoet.I18n.t('activate')
                    : MailPoet.I18n.t('send')} />
                &nbsp;
                <input
                  className="button button-secondary"
                  type="submit"
                  value={MailPoet.I18n.t('saveDraftAndClose')} />
                &nbsp;{MailPoet.I18n.t('orSimply')}&nbsp;
                <a
                  href={
                    '?page=mailpoet-newsletter-editor&id='+this.props.params.id
                  }>
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
