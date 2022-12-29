import ReactDOM from 'react-dom';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import { ErrorBoundary } from 'common';
import { Background } from 'common/background/background';
import { Header } from './header';
import { Footer } from './footer';

function Landingpage() {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <Background color="#fff" />

      <Header />

      <div className="mailpoet-gap" />

      <br />
      <br />
      <br />
      <br />
      <br />

      <div className="mailpoet-gap" />

      <Footer />
    </GlobalContext.Provider>
  );
}
Landingpage.displayName = 'Landingpage';

const landingpageContainer = document.getElementById(
  'mailpoet_landingpage_container',
);

if (landingpageContainer) {
  ReactDOM.render(
    <ErrorBoundary>
      <Landingpage />
    </ErrorBoundary>,
    landingpageContainer,
  );
}
