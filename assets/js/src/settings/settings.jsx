define(
  [
    'react',
    'react-router',
    'classnames',
    'settings/basics.jsx',
    'settings/signup.jsx',
    'settings/mta.jsx',
    'settings/advanced.jsx',
    'settings/bounce.jsx'
  ],
  function(
    React,
    Router,
    classNames,
    Basics,
    Signup,
    Mta,
    Advanced,
    Bounce
  ) {
    var DefaultRoute = Router.DefaultRoute;
    var Link = Router.Link;
    var Route = Router.Route;
    var RouteHandler = Router.RouteHandler;
    var NotFoundRoute = Router.NotFoundRoute;

    var settings = mailpoet_settings || {};

    var Tabs = React.createClass({
      render: function() {
        var current_tab = window.location.hash.split('/')[1];
         var tabs = this.props.tabs.map(function(tab, index) {
          var tabClasses = classNames(
            'nav-tab',
            { 'nav-tab-active': (current_tab === tab.to) }
          );
          return (
            <Link
              key={ 'tab-' + index }
              to={ tab.to }
              className={ tabClasses }
            >
              { tab.label }
            </Link>
          );
        });

        return (
          <h2
            id="mailpoet_settings_tabs"
            className="nav-tab-wrapper"
          >
            { tabs }
          </h2>
        );
      }
    });

    var App = React.createClass({
      getInitialState: function() {
        return {
          loading: false
        }
      },
      render: function() {
        return (
          <div>
            <h1>{ MailPoetI18n.pageTitle }</h1>

            <Tabs tabs={ [
              { to: 'basics', label: 'Basics'},
              { to: 'signup', label: 'Signup Confirmation'},
              { to: 'mta', label: 'Send With...'},
              { to: 'advanced', label: 'Advanced'},
              { to: 'bounce', label: 'Bounce Handling'}
            ]} />

            <form
              id="mailpoet_settings_form"
              name="mailpoet_settings_form"
              autoComplete="off"
              noValidate
            >
              <RouteHandler settings={ settings } />

              <input
                className="button button-primary"
                type="submit"
                value="Save"
                disabled={this.state.loading} />
            </form>
          </div>
        );
      }
    });

    var routes = (
      <Route name="app" path="/" handler={App}>
        <Route name="basics" path="/basics" handler={Basics} />
        <Route name="signup" path="/signup" handler={Signup} />
        <Route name="mta" path="/mta" handler={Mta} />
        <Route name="advanced" path="/advanced" handler={Advanced} />
        <Route name="bounce" path="/bounce" handler={Bounce} />
        <NotFoundRoute handler={Basics} />
        <DefaultRoute handler={Basics} />
      </Route>
    );

    var hook = document.getElementById('mailpoet_settings');
    if(hook) {
      Router.run(routes, function(Handler, state) {
        React.render(
          <Handler
            params={state.params}
            query={state.query} />,
          hook
        );
      });
    }
  }
);
