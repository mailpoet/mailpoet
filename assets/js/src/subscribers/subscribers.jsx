define(
  'subscribers',
  [
    'react',
    'react-router',
    'subscribers/list.jsx'
  ],
  function(
    React,
    Router,
    List
  ) {

    var DefaultRoute = Router.DefaultRoute;
    var Link = Router.Link;
    var Route = Router.Route;
    var RouteHandler = Router.RouteHandler;

    var App = React.createClass({
      render: function() {
        return (
          <div>
            <header>
              <ul>
                <li>
                  <Link to="list">List</Link>
                </li>
              </ul>
            </header>

            <RouteHandler/>
          </div>
        );
      }
    });

    var routes = (
      <Route name="app" path="/" handler={App}>
        <Route name="list" handler={List} />
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
  });
