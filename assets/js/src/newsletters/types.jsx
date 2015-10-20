define(
  [
    'react',
    'mailpoet',
    'react-router',
    'newsletters/breadcrumb.jsx'
  ],
  function(
    React,
    MailPoet,
    Router,
    Breadcrumb
  ) {
    var NewsletterTypes = React.createClass({
      mixins: [
        Router.History
      ],
      handleSelectType: function(type) {
        if(type !== undefined) {
          this.history.pushState(null, `/new/${type}`);
        }
      },
      render: function() {
        return (
          <div>
            <h1>Pick a type of campaign</h1>

            <Breadcrumb step="type" />

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