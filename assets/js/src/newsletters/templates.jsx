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
      handleSelectTemplate: function(template) {
        console.log('select '+template);
      },
      handlePreviewTemplate: function(template) {
        console.log('preview '+template);
      },
      handleDeleteTemplate: function(template) {
        console.log('delete '+template);
      },
      render: function() {
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
              <li>
                <div className="mailpoet_thumbnail">
                </div>

                <div className="mailpoet_description">
                    <h3>MailPoet&#39;s Guide</h3>

                    <p>This is the standard template that comes with MailPoet.</p>
                </div>

                <div className="mailpoet_actions">
                    <a
                      className="button button-primary"
                      onClick={ this.handleSelectTemplate.bind(null, 'default') }
                    >
                      Select
                    </a>
                    &nbsp;
                    <a
                      className="button button-secondary"
                      onClick={ this.handlePreviewTemplate.bind(null, 'default') }
                    >
                      Preview
                    </a>
                </div>
                <div className="mailpoet_delete">
                  <a onClick={ this.handleDeleteTemplate.bind(null, 'default') }
                  >
                    Delete
                  </a>
                </div>
              </li>
            </ul>
          </div>
        );
      }
    });

    return NewsletterTemplates;
  }
);