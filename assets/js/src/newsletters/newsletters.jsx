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
            <h1>
            { MailPoetI18n.pageTitle }
              <span>
                <Link className="add-new-h2" to="list">Newsletters</Link>
              </span>
              <span>
                <Link className="add-new-h2" to="form">New newsletter</Link>
              </span>
            </h1>

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
      Router.run(routes, function(Handler, state) {
        React.render(
          <Handler params={state.params} query={state.query} />,
          hook
        );
      });
    }
  });
