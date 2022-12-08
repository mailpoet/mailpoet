import ReactDOM from 'react-dom';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import { TopBarWithBeamer } from 'common/top_bar/top_bar';
import { HomepageNotices } from 'homepage/notices';

function App(): JSX.Element {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <TopBarWithBeamer />
      <HomepageNotices />
    </GlobalContext.Provider>
  );
}

const container = document.getElementById('mailpoet_homepage_container');

if (container) {
  ReactDOM.render(<App />, container);
}
