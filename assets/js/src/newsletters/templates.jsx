define(
  [
  'react',
  'mailpoet',
  'react-router'
  ],
  function(
    React,
    MailPoet,
    Router
  ) {
    var NewsletterTemplates = React.createClass({
      mixins: [
        Router.Navigation
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
          endpoint: 'newslettertemplates',
          action: 'getAll',
        }).done(function(response) {
          if(this.isMounted()) {
            this.setState({
              templates: response,
              loading: false
            });
          }
        }.bind(this));
      },
      handleSelectTemplate: function(id) {
        console.log('select '+id);
      },
      handlePreviewTemplate: function(id) {
        console.log('preview '+id);
      },
      handleDeleteTemplate: function(id) {
        this.setState({ loading: true });

        MailPoet.Ajax.post({
          endpoint: 'newslettertemplates',
          action: 'delete',
          data: id
        }).done(function(response) {
          this.getTemplates();
        }.bind(this));
      },
      render: function() {
        var templates = this.state.templates.map(function(template, index) {
          return (
            <li key={ 'template-'+index }>
              <div className="mailpoet_thumbnail">
              </div>

              <div className="mailpoet_description">
                  <h3>{ template.name }</h3>
                  <p>{ template.description }</p>
              </div>

              <div className="mailpoet_actions">
                  <a
                    className="button button-primary"
                    onClick={ this.handleSelectTemplate.bind(null, template.id) }
                  >
                    Select
                  </a>
                  &nbsp;
                  <a
                    className="button button-secondary"
                    onClick={ this.handlePreviewTemplate.bind(null, template.id) }
                  >
                    Preview
                  </a>
              </div>
              <div className="mailpoet_delete">
                <a
                  href="javascript:;"
                  onClick={ this.handleDeleteTemplate.bind(null, template.id) }
                >
                  Delete
                </a>
              </div>
            </li>
          );
        }.bind(this));

        return (
          <div>
            <h1>Select a template</h1>

            <div className="mailpoet_breadcrumbs">
              Select type
              &nbsp;&gt;&nbsp;
              <strong>Template</strong>
              &nbsp;&gt;&nbsp;
              Designer&nbsp;&gt;&nbsp;
              Send
            </div>


            <ul className="mailpoet_boxes clearfix">
              { templates }
            </ul>
          </div>
        );
      }
    });

    return NewsletterTemplates;
  }
);