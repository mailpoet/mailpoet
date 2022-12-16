import ReactDOM from 'react-dom';
import { useEffect, useState } from 'react';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import { TopBarWithBeamer } from 'common/top_bar/top_bar';
import { HomepageNotices } from 'homepage/notices';
import { TaskList } from './components/task-list';
import { Layout } from './components/layout';
import { createStore } from './store/store';

function App(): JSX.Element {
  const [isStoreInitialized, setIsStoreInitialized] = useState(false);
  useEffect(() => {
    createStore();
    setIsStoreInitialized(true);
  }, []);
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <TopBarWithBeamer />
      <HomepageNotices />
      {isStoreInitialized ? (
        <Layout>
          <TaskList />
        </Layout>
      ) : null}
    </GlobalContext.Provider>
  );
}

const container = document.getElementById('mailpoet_homepage_container');
if (container) {
  ReactDOM.render(<App />, container);
}
