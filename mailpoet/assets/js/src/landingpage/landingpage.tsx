import ReactDOM from 'react-dom';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import { MailPoet } from 'mailpoet';
import { ErrorBoundary } from 'common';

function Landingpage(): JSX.Element {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <h1> {MailPoet.I18n.t('betterEmailWithoutLeavingWordPress')} </h1>
      <h3> {MailPoet.I18n.t('startingOutOrEstablished')} </h3>
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
