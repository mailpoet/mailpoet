define(
  'newsletters',
  [
    'react',
    'react-router',
    'newsletters/form.jsx',
    'newsletters/list.jsx'
  ],
  function(
    React,
    Router,
    Form,
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
                  <Link to="list">Newsletters</Link>
                </li>
                <li>
                  <Link to="form">New</Link>
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
        <Route name="list" handler={List}/>
        <Route name="form" handler={Form}/>
        <DefaultRoute handler={List}/>
      </Route>
    );

    var hook = document.getElementById('newsletters');
    if (hook) {
      Router.run(routes, function(Handler) {
        React.render(<Handler/>, hook);
      });
    }
  });
