import React from 'react'
import ReactDOM from 'react-dom'
import { Router, Route, IndexRoute, Link } from 'react-router'
import SegmentList from 'segments/list.jsx'
import SegmentForm from 'segments/form.jsx'
import createHashHistory from 'history/lib/createHashHistory'

let history = createHashHistory({ queryKey: false })

const App = React.createClass({
  render() {
    return this.props.children
  }
});

let container = document.getElementById('segments');

if(container) {
  ReactDOM.render((
    <Router history={ history }>
      <Route path="/" component={ App }>
        <IndexRoute component={ SegmentList } />
        <Route path="new" component={ SegmentForm } />
        <Route path="edit/:id" component={ SegmentForm } />
        <Route path="*" component={ SegmentList } />
      </Route>
    </Router>
  ), container);
}