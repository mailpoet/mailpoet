import ReactDOM from 'react-dom';
import { GlobalContext, useGlobalContextValue } from 'context';
import { ErrorBoundary, registerTranslations } from 'common';
import { Background } from 'common/background/background';
import { HideScreenOptions } from 'common/hide-screen-options/hide-screen-options';
import { TopBarWithBeamer } from 'common/top-bar/top-bar';
import { Header } from './header';
import { Footer } from './footer';
import { Faq } from './faq';
import { Content } from './content';

function Landingpage() {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <main>
        <HideScreenOptions />
        <TopBarWithBeamer />

        <Background color="#fff" />

        <Header />

        <div className="mailpoet-gap" />

        <Content />

        <div className="mailpoet-gap" />

        <Faq />

        <Footer />
      </main>
    </GlobalContext.Provider>
  );
}

Landingpage.displayName = 'Landingpage';

const landingpageContainer = document.getElementById(
  'mailpoet_landingpage_container',
);

if (landingpageContainer) {
  registerTranslations();
  ReactDOM.render(
    <ErrorBoundary>
      <Landingpage />
    </ErrorBoundary>,
    landingpageContainer,
  );
}
