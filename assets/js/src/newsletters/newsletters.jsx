define(
  [
    'react',
    'react-router',
    'newsletters/list.jsx',
    'newsletters/types.jsx',
    'newsletters/templates.jsx'
  ],
  function(
    React,
    Router,
    NewsletterList,
    NewsletterTypes,
    NewsletterTemplates
  ) {
    var DefaultRoute = Router.DefaultRoute;
    var Link = Router.Link;
    var Route = Router.Route;
    var RouteHandler = Router.RouteHandler;
    var NotFoundRoute = Router.NotFoundRoute;

    var App = React.createClass({
      render: function() {
        return (
          <RouteHandler/>
        );
      }
    });

    var routes = (
      <Route name="app" path="/" handler={App}>
        <Route name="new" path="/new" handler={ NewsletterTypes } />
        <Route name="template" path="/new/:type" handler={ NewsletterTemplates } />
        <NotFoundRoute handler={ NewsletterList } />
        <DefaultRoute handler={ NewsletterList } />
      </Route>
    );

    var hook = document.getElementById('newsletters');
    if(hook) {
      Router.run(routes, function(Handler, state) {
        React.render(
          <Handler params={ state.params } query={ state.query } />,
          hook
        );
      });
    }
  }
);
