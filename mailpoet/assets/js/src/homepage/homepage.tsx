import ReactDOM from 'react-dom';
import { TopBarWithBeamer } from 'common/top_bar/top_bar';

function App(): JSX.Element {
  return <TopBarWithBeamer />;
}

const container = document.getElementById('mailpoet_homepage_container');

if (container) {
  ReactDOM.render(<App />, container);
}
