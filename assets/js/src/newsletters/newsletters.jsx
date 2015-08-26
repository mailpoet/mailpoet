define(
  'newsletters',
  [
    'react',
    'react-router',
    'newsletters/form.jsx'
  ],
  function(
    React,
    Router,
    Form
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
                  <Link to="listing">Newsletters</Link>
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

    var Listing = React.createClass({
      render: function () {
        return (
          <div>
            <h1>Newsletters</h1>
          </div>
        );
      }
    });

    var routes = (
      <Route name="app" path="/" handler={App}>
        <Route name="listing" handler={Listing}/>
        <Route name="form" handler={Form}/>
        <DefaultRoute handler={Listing}/>
      </Route>
    );

    var hook = document.getElementById('newsletters');
    if (hook) {
      Router.run(routes, function(Handler) {
        React.render(<Handler/>, hook);
      });
    }
  });
