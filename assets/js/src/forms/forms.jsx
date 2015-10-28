import React from 'react'
import ReactDOM from 'react-dom'
import { Router, Route, IndexRoute, Link } from 'react-router'
import FormList from 'forms/list.jsx'
import FormForm from 'forms/form.jsx'
import createHashHistory from 'history/lib/createHashHistory'

let history = createHashHistory({ queryKey: false })

const App = React.createClass({
  render() {
    return this.props.children
  }
});

let container = document.getElementById('forms_container');

if(container) {
  ReactDOM.render((
    <Router history={ history }>
      <Route path="/" component={ App }>
        <IndexRoute component={ FormList } />
        <Route path="new" component={ FormForm } />
        <Route path="edit/:id" component={ FormForm } />
        <Route path="*" component={ FormList } />
      </Route>
    </Router>
  ), container);
}