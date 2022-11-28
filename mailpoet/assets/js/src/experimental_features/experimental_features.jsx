import { useEffect, useState } from 'react';
import ReactDOM from 'react-dom';
import { MailPoet } from 'mailpoet';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import { Notices } from 'notices/notices.jsx';
import { ErrorBoundary } from 'common';

function ExperimentalFeatures() {
  const [flags, setFlags] = useState(null);
  const contextValue = useGlobalContextValue(window);
  const showError = contextValue.notices.error;

  useEffect(() => {
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'featureFlags',
      action: 'getAll',
    })
      .done((response) => {
        const flagsMap = response.data.reduce(
          (obj, item) => ({ ...obj, [item.name]: item }),
          {},
        );
        setFlags(flagsMap);
      })
      .fail((response) => {
        if (response.errors.length > 0) {
          showError(
            <>
              {response.errors.map((error) => (
                <p>{error.message}</p>
              ))}
            </>,
            { scroll: true },
          );
        }
      });
  }, [showError]);

  function handleChange(event) {
    const name = event.target.name;
    const value = event.target.checked;

    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'featureFlags',
      action: 'set',
      data: {
        [name]: value ? 1 : 0,
      },
    })
      .done(() => {
        const flag = flags[name];
        flag.value = value;
        setFlags({ ...flags, [name]: flag });
        const message = `Feature '${name}' was ${
          value ? 'enabled' : 'disabled'
        }.`;
        contextValue.notices.success(<p>{message}</p>);
      })
      .fail((response) => {
        if (response.errors.length > 0) {
          showError(
            response.errors.map((error) => (
              <p key={error.message}>{error.message}</p>
            )),
            { scroll: true },
          );
        }
      });
  }

  if (flags === null) {
    return <p>Loading experimental features...</p>;
  }

  if (Object.values(flags).length === 0) {
    return <p>There are no experimental features at the moment.</p>;
  }

  return (
    <GlobalContext.Provider value={contextValue}>
      <>
        <Notices />
        {Object.values(flags).map((flag) => {
          const id = `experimental-feature-${flag.name}`;
          return (
            <div key={flag.name}>
              <label htmlFor={id}>
                <input
                  id={id}
                  type="checkbox"
                  name={flag.name}
                  defaultChecked={flag.value}
                  onChange={handleChange}
                />{' '}
                {flag.name}
              </label>
            </div>
          );
        })}
      </>
    </GlobalContext.Provider>
  );
}

const experimentalFeaturesContainer = document.getElementById(
  'experimental_features_container',
);

ExperimentalFeatures.displayName = 'ExperimentalFeatures';

if (experimentalFeaturesContainer) {
  ReactDOM.render(
    <ErrorBoundary>
      <ExperimentalFeatures />
    </ErrorBoundary>,
    experimentalFeaturesContainer,
  );
}
