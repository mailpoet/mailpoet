import ReactDOM from 'react-dom';
import { useEffect } from 'react';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import { TopBarWithBeamer } from 'common/top_bar/top_bar';
import { HomepageNotices } from 'homepage/notices';
import { TaskList } from './components/task-list';
import { createStore } from './store/store';

function App(): JSX.Element {
  useEffect(() => {
    createStore();
  }, []);
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <TopBarWithBeamer />
      <HomepageNotices />
      <TaskList />
    </GlobalContext.Provider>
  );
}

const container = document.getElementById('mailpoet_homepage_container');

if (container) {
  ReactDOM.render(<App />, container);
}
