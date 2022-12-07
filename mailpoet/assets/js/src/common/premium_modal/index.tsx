import {
  ComponentProps,
  EventHandler,
  FocusEvent,
  KeyboardEvent,
  MouseEvent,
  useCallback,
  useEffect,
  useState,
} from 'react';
import {
  __experimentalText as Text,
  Button,
  Modal,
} from '@wordpress/components';
import { dispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import {
  premiumFeaturesEnabled,
  UpgradeInfo,
  useUpgradeInfo,
  UtmParams,
} from './upgrade_info';
import { storeName } from '../../automation/editor/store';
import { withBoundary } from '../error_boundary';

export const premiumValidAndActive =
  premiumFeaturesEnabled && MailPoet.premiumActive;

type Props = Omit<ComponentProps<typeof Modal>, 'title' | 'onRequestClose'> & {
  // Fix type from "@types/wordpress__components" where it is defined as a union of event
  // handlers, resulting in a function requiring intersection of all of the event types.
  onRequestClose: EventHandler<KeyboardEvent | MouseEvent | FocusEvent>;
} & {
  tracking?: UtmParams;
};

type State = undefined | 'busy' | 'success' | 'error';

const getCta = (state: State, upgradeInfo: UpgradeInfo): string => {
  const { action, cta } = upgradeInfo;
  if (typeof action === 'string') {
    return cta;
  }
  if (state === 'busy') {
    return action.busy;
  }
  if (state === 'success') {
    return action.success;
  }
  return cta;
};

function PremiumModal({ children, tracking, ...props }: Props): JSX.Element {
  const [state, setState] = useState<State>();
  const upgradeInfo = useUpgradeInfo(tracking);

  //
  useEffect(() => {
    setState(undefined);
  }, [upgradeInfo]);

  const handleClick = useCallback(async () => {
    if (typeof upgradeInfo.action === 'string') {
      return;
    }

    if (state === 'success') {
      upgradeInfo.action.successHandler();
      return;
    }

    setState('busy');
    try {
      await upgradeInfo.action.handler();
      setState('success');
    } catch (_) {
      setState('error');
    }
  }, [state, upgradeInfo.action]);

  return (
    <Modal
      className="mailpoet-premium-modal"
      title={upgradeInfo.title}
      closeButtonLabel={__('Cancel', 'mailpoet')}
      {...props}
    >
      <div>
        {!premiumValidAndActive && children} {upgradeInfo.info}
      </div>
      <div className="mailpoet-premium-modal-footer">
        <Button variant="tertiary" onClick={props.onRequestClose}>
          {__('Cancel', 'mailpoet')}
        </Button>

        {typeof upgradeInfo.action === 'string' ? (
          <Button
            variant="primary"
            href={upgradeInfo.action}
            target="_blank"
            rel="noopener noreferrer"
          >
            {upgradeInfo.cta}
          </Button>
        ) : (
          <Button
            variant="primary"
            isBusy={state === 'busy'}
            disabled={state === 'busy'}
            onClick={handleClick}
          >
            {getCta(state, upgradeInfo)}
          </Button>
        )}
      </div>
      {typeof upgradeInfo.action !== 'string' && state === 'error' && (
        <div className="mailpoet-premium-modal-error">
          <Text isDestructive>
            {upgradeInfo.action.error} {__('Please try again.', 'mailpoet')}
          </Text>
        </div>
      )}
    </Modal>
  );
}

PremiumModal.displayName = 'PremiumModal';

type EditProps = Omit<Props, 'onRequestClose'>;

function PremiumModalForStepEdit({
  children,
  ...props
}: EditProps): JSX.Element {
  const [showModal, setShowModal] = useState(true);
  useEffect(() => {
    if (showModal) {
      return;
    }
    const { selectStep } = dispatch(storeName);
    selectStep(undefined);
    setShowModal(true);
  }, [showModal]);
  if (!showModal) {
    return null;
  }
  return (
    <PremiumModal
      onRequestClose={() => {
        setShowModal(false);
      }}
      {...props}
    >
      {children}
    </PremiumModal>
  );
}

PremiumModalForStepEdit.displayName = 'PremiumModalForStepEdit';
const PremiumModalForStepEditWithBoundary = withBoundary(
  PremiumModalForStepEdit,
);
export {
  PremiumModal,
  PremiumModalForStepEditWithBoundary as PremiumModalForStepEdit,
};
