define(
  [
    'react',
    'react-router',
    'subscribers/list.jsx',
    'subscribers/form.jsx'
  ],
  function(
    React,
    Router,
    List,
    Form
  ) {
    var DefaultRoute = Router.DefaultRoute;
    var Link = Router.Link;
    var Route = Router.Route;
    var RouteHandler = Router.RouteHandler;
    var NotFoundRoute = Router.NotFoundRoute;

    var App = React.createClass({
      render: function() {
        return (
          <RouteHandler />
        );
      }
    });

    var routes = (
      <Route name="app" path="/" handler={App}>
        <Route name="new" path="/new" handler={Form} />
        <Route name="edit" path="/edit/:id" handler={Form} />
        <NotFoundRoute handler={List} />
        <DefaultRoute handler={List} />
      </Route>
    );

    var hook = document.getElementById('subscribers');
    if(hook) {
      Router.run(routes, function(Handler, state) {
        React.render(
          <Handler params={state.params} query={state.query} />,
          hook
        );
      });
    }
  }
);
