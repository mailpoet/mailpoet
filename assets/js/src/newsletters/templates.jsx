define(
  [
    'react',
    'underscore',
    'mailpoet',
    'react-router',
    'classnames',
    'newsletters/breadcrumb.jsx'
  ],
  function(
    React,
    _,
    MailPoet,
    Router,
    classNames,
    Breadcrumb
  ) {

    var ImportTemplate = React.createClass({
      saveTemplate: function(template) {

        // Stringify to enable transmission of primitive non-string value types
        if (!_.isUndefined(template.body)) {
          template.body = JSON.stringify(template.body);
        }

        MailPoet.Modal.loading(true);

        MailPoet.Ajax.post({
          endpoint: 'newsletterTemplates',
          action: 'save',
          data: template
        }).done(function(response) {
          MailPoet.Modal.loading(false);
          if(response.result === true) {
            this.props.onImport(template);
          } else {
            response.map(function(error) {
              MailPoet.Notice.error(error);
            });
          }
        }.bind(this));
      },
      handleSubmit: function(e) {
        e.preventDefault();

        if (_.size(this.refs.templateFile.files) <= 0) return false;

        var file = _.first(this.refs.templateFile.files),
            reader = new FileReader(),
            saveTemplate = this.saveTemplate;

        reader.onload = function(e) {
          try {
            saveTemplate(JSON.parse(e.target.result));
          } catch (err) {
            MailPoet.Notice.error(MailPoet.I18n.t('templateFileMalformedError'));
          }
        }.bind(this);

        reader.readAsText(file);
      },
      render: function() {
        return (
          <div>
            <h2>{MailPoet.I18n.t('importTemplateTitle')}</h2>
            <form onSubmit={this.handleSubmit}>
              <input type="file" placeholder={MailPoet.I18n.t('selectJsonFileToUpload')} ref="templateFile" />

              <p className="submit">
                <input
                  className="button button-primary"
                  type="submit"
                  value={MailPoet.I18n.t('upload')} />
              </p>
            </form>
          </div>
        );
      },
    });

    var NewsletterTemplates = React.createClass({
      getInitialState: function() {
        return {
          loading: false,
          templates: []
        };
      },
      componentDidMount: function() {
        this.getTemplates();
      },
      getTemplates: function() {
        this.setState({ loading: true });

        MailPoet.Modal.loading(true);

        MailPoet.Ajax.post({
          endpoint: 'newsletterTemplates',
          action: 'getAll',
        }).done(function(response) {
          MailPoet.Modal.loading(false);
          if(this.isMounted()) {

            if(response.length === 0) {
              response = [
                {
                  name:
                    MailPoet.I18n.t('mailpoetGuideTemplateTitle'),
                  description:
                    MailPoet.I18n.t('mailpoetGuideTemplateDescription'),
                  readonly: "1"
                }
              ]
            }
            this.setState({
              templates: response,
              loading: false
            });
          }
        }.bind(this));
      },
      handleSelectTemplate: function(template) {
        var body = template.body;

        // Stringify to enable transmission of primitive non-string value types
        if (!_.isUndefined(body)) {
          body = JSON.stringify(body);
        }

        MailPoet.Ajax.post({
          endpoint: 'newsletters',
          action: 'save',
          data: {
            id: this.props.params.id,
            body: body
          }
        }).done(function(response) {
          if(response.result === true) {
            // TODO: Move this URL elsewhere
            window.location = 'admin.php?page=mailpoet-newsletter-editor&id=' + this.props.params.id;
          } else {
            response.errors.map(function(error) {
              MailPoet.Notice.error(error);
            });
          }
        }.bind(this));
      },
      handlePreviewTemplate: function(template) {
        console.log('preview template #'+template.id);
      },
      handleDeleteTemplate: function(template) {
        this.setState({ loading: true });
        if(
          window.confirm(
            (
              MailPoet.I18n.t('confirmTemplateDeletion')
            ).replace("%$1s", template.name)
          )
        ) {
          MailPoet.Ajax.post({
            endpoint: 'newsletterTemplates',
            action: 'delete',
            data: template.id
          }).done(function(response) {
            this.getTemplates();
          }.bind(this));
        } else {
           this.setState({ loading: false });
        }
      },
      handleShowTemplate: function(template) {
        MailPoet.Modal.popup({
          title: template.name,
          template: '<div class="mailpoet_boxes_preview" style="background-color: {{ body.globalStyles.body.backgroundColor }}"><img src="{{ thumbnail }}" /></div>',
          data: template,
        });
      },
      handleTemplateImport: function() {
        this.getTemplates();
      },
      render: function() {
        var templates = this.state.templates.map(function(template, index) {
          var deleteLink = (
            <div className="mailpoet_delete">
              <a
                href="javascript:;"
                onClick={ this.handleDeleteTemplate.bind(null, template) }
              >
                {MailPoet.I18n.t('delete')}
              </a>
            </div>
          ), thumbnail = '';

          if (typeof template.thumbnail === 'string'
              && template.thumbnail.length > 0) {
            thumbnail = (
              <a href="javascript:;" onClick={this.handleShowTemplate.bind(null, template)}>
                <img src={ template.thumbnail } />
                <div className="mailpoet_overlay"></div>
              </a>
            );
          }

          return (
            <li key={ 'template-'+index }>
              <div className="mailpoet_thumbnail">
                { thumbnail }
              </div>

              <div className="mailpoet_description">
                  <h3>{ template.name }</h3>
                  <p>{ template.description }</p>
              </div>

              <div className="mailpoet_actions">
                  <a
                    className="button button-primary"
                    onClick={ this.handleSelectTemplate.bind(null, template) }
                  >
                    {MailPoet.I18n.t('select')}
                  </a>
                  &nbsp;
                  <a
                    className="button button-secondary"
                    onClick={ this.handleShowTemplate.bind(null, template) }
                  >
                    {MailPoet.I18n.t('preview')}
                  </a>
              </div>
              { (template.readonly === "1") ? false : deleteLink }
            </li>
          );
        }.bind(this));

        var boxClasses = classNames(
          'mailpoet_boxes',
          'clearfix',
          { 'mailpoet_boxes_loading': this.state.loading }
        );

        return (
          <div>
            <h1>{MailPoet.I18n.t('selectTemplateTitle')}</h1>

            <Breadcrumb step="template" />

            <ul className={ boxClasses }>
              { templates }
            </ul>

            <ImportTemplate onImport={this.handleTemplateImport} />
          </div>
        );
      }
    });

    return NewsletterTemplates;
  }
);
