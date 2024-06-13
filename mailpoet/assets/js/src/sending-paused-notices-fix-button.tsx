import jQuery from 'jquery';
import { useState } from 'react';
import { SetFromAddressModal } from 'common/set-from-address-modal';
import { GlobalContext, useGlobalContextValue } from 'context';
import { Notices } from 'notices/notices.jsx';
import { noop } from 'lodash';
import { createRoot } from 'react-dom/client';

type Props = {
  onRequestClose?: () => void;
};

function App({ onRequestClose = noop }: Props) {
  const [showModal, setShowModal] = useState(false);

  // use jQuery since some of the targeted notices are added to the DOM using the old
  // jQuery-based notice implementation which doesn't trigger pure-JS added listeners
  jQuery(($) => {
    $(document).on('click', '.notice .mailpoet-js-button-fix-this', () => {
      setShowModal(true);
    });
  });

  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <Notices />
      {showModal && (
        <SetFromAddressModal
          onRequestClose={() => {
            setShowModal(false);
            onRequestClose();
          }}
        />
      )}
    </GlobalContext.Provider>
  );
}

// nothing is actually rendered to the container because the <Modal> component uses
// ReactDOM.createPortal() but we need an element as a React root on all pages
const container = document.getElementById('mailpoet_set_from_address_modal');
if (container) {
  const root = createRoot(container);
  root.render(
    <App
      onRequestClose={() => {
        // if in Settings, reload page, so the new saved FROM address is loaded
        const isInSettings = window.location.href.includes(
          '?page=mailpoet-settings',
        );
        if (isInSettings) {
          window.location.reload();
        }
      }}
    />,
  );
}
