define(
  'newsletters',
  [
    'react',
    'react-router',
    'mailpoet'
  ],
  function(
    React,
    Router,
    MailPoet
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
                  <Link to="app">Dashboard</Link>
                </li>
                <li>
                  <Link to="inbox">Inbox</Link>
                </li>
                <li>
                  <Link to="calendar">Calendar</Link>
                </li>
              </ul>
              Logged in as Marco
            </header>

            <RouteHandler/>
          </div>
        );
      }
    });

    var Dashboard = React.createClass({
      render: function () {
        return (
          <div>
            <h1>Dashboard</h1>
          </div>
        );
      }
    });

    var Inbox = React.createClass({
      render: function () {
        return (
          <div>
            <h1>Inbox</h1>
          </div>
        );
      }
    });

    var Calendar = React.createClass({
      render: function () {
        return (
          <div>
            <h1>Calendar</h1>
          </div>
        );
      }
    });

    var routes = (
      <Route name="app" path="/" handler={App}>
        <Route name="inbox" handler={Inbox}/>
        <Route name="calendar" handler={Calendar}/>
        <DefaultRoute handler={Dashboard}/>
      </Route>
    );

    var hook = document.getElementById('newsletters');
    if (hook) {
      Router.run(routes, function(Handler) {
        React.render(<Handler/>, hook);
      });
    }
  });
