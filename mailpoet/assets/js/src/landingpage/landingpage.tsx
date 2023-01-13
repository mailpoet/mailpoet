import ReactDOM from 'react-dom';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import { ErrorBoundary } from 'common';
import { Background } from 'common/background/background';
import { Header } from './header';
import { Footer } from './footer';
import { Faq } from './faq';
import { Content } from './content';

function Landingpage() {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <Background color="#fff" />

      <Header />

      <div className="mailpoet-gap" />

      <Content />

      <div className="mailpoet-gap" />

      <Faq />

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
