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
    var NewsletterTypes = React.createClass({
      mixins: [
        Router.Navigation
      ],
      handleSelectType: function(type) {
        if(type !== undefined) {
          this.transitionTo('/new/'+type);
        }
      },
      render: function() {
        return (
          <div>
            <h1>Pick a type of campaign</h1>

            <div className="mailpoet_breadcrumbs">
              <strong>Select type</strong>
              &nbsp;&gt;&nbsp;
              Template
              &nbsp;&gt;&nbsp;
              Designer&nbsp;&gt;&nbsp;
              Send
            </div>

            <ul className="mailpoet_boxes clearfix">
              <li data-type="standard">
                <div className="mailpoet_thumbnail"></div>

                <div className="mailpoet_description">
                  <h3>Newsletter</h3>
                  <p>
                    Send a newsletter with images, buttons, dividers,
                    and social bookmarks. Or a simple email.
                  </p>
                </div>

                <div className="mailpoet_actions">
                  <a
                    className="button button-primary"
                    onClick={ this.handleSelectType.bind(null, 'standard') }
                  >
                    Create
                  </a>
                </div>
              </li>
            </ul>
          </div>
        );
      }
    });

    return NewsletterTypes;
  }
);