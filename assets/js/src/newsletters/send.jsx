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

    var messages = {
      onUpdate: function() {
        MailPoet.Notice.success(MailPoet.I18n.t('newsletterUpdated'));
      },
      onCreate: function() {
        MailPoet.Notice.success(MailPoet.I18n.t('newsletterAdded'));
      }
    };

    var NewsletterSend = React.createClass({
      mixins: [
        Router.History
      ],
      getInitialState: function() {
        return {
          fields: [],
          errors: [],
          item: {},
          loading: false,
        };
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
              this.history.pushState(null, '/new');
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
      getFieldsByNewsletter: function(newsletter) {
        switch(newsletter.type) {
          case 'notification': return NotificationNewsletterFields;
          case 'welcome': return WelcomeNewsletterFields;
          default: return StandardNewsletterFields;
        }
      },
      isValid: function() {
        return jQuery('#mailpoet_newsletter').parsley().isValid();
      },
      isAutomatedNewsletter: function() {
        return this.state.item.type !== 'standard';
      },
      handleSend: function() {
        if(!this.isValid()) {
          jQuery('#mailpoet_newsletter').parsley().validate();
        } else {

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
            if(response.result === true) {
              this.history.pushState(null, '/');
              MailPoet.Notice.success(
                MailPoet.I18n.t('newsletterIsBeingSent')
              );
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

        MailPoet.Ajax.post({
          endpoint: 'newsletters',
          action: 'save',
          data: this.state.item,
        }).done((response) => {
          this.setState({ loading: false });

          if(response.result === true) {
            this.history.pushState(null, '/');
            messages.onUpdate();
          } else {
            if(response.errors.length > 0) {
              this.setState({ errors: response.errors });
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
      getParams: function() {
        return {};
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
              params={ this.getParams() }
              errors= { this.state.errors }
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
