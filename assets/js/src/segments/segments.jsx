define(
  'segments',
  [
    'react',
    'react-router',
    'segments/list.jsx',
    'segments/form.jsx'
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

    var App = React.createClass({
      render: function() {
        return (
          <div>
            <h1>
              { MailPoetI18n.pageTitle }
              &nbsp;
              <Link className="add-new-h2" to="form">New</Link>
            </h1>

            <RouteHandler/>
          </div>
        );
      }
    });

    var routes = (
      <Route name="app" path="/" handler={App}>
        <Route name="list" handler={List} />
        <Route name="form" handler={Form} />
        <DefaultRoute handler={List} />
      </Route>
    );

    var hook = document.getElementById('segments');
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
