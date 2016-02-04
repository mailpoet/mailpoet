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

        MailPoet.Ajax.post({
          endpoint: 'newsletterTemplates',
          action: 'save',
          data: template
        }).done(function(response) {
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
            MailPoet.Notice.error('This template file appears to be malformed. Please try another one.');
          }
        }.bind(this);

        reader.readAsText(file);
      },
      render: function() {
        return (
          <div>
            <h2>Import a template</h2>
            <form onSubmit={this.handleSubmit}>
              <input type="file" placeholder="Select a .json file to upload" ref="templateFile" />

              <p className="submit">
                <input
                  className="button button-primary"
                  type="submit"
                  value="Upload" />
              </p>
            </form>
          </div>
        );
      },
    });

    var NewsletterTemplates = React.createClass({
      mixins: [
        Router.History
      ],
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

        MailPoet.Ajax.post({
          endpoint: 'newsletterTemplates',
          action: 'getAll',
        }).done(function(response) {
          if(this.isMounted()) {

            if(response.length === 0) {
              response = [
                {
                  name:
                    "MailPoet's Guide",
                  description:
                    "This is the standard template that comes with MailPoet.",
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
            'You are about to delete the template named "'+ template.name +'"'
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
                Delete
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
                    Select
                  </a>
                  &nbsp;
                  <a
                    style={ { display: 'none' }}
                    className="button button-secondary"
                    onClick={ this.handlePreviewTemplate.bind(null, template) }
                  >
                    Preview
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
            <h1>Select a template</h1>

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
