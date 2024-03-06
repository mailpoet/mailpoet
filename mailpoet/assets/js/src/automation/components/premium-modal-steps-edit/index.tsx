import { useEffect, useState } from 'react';
import { dispatch } from '@wordpress/data';
import { PremiumModal, PremiumModalProps } from 'common/premium-modal';
import { withBoundary } from 'common/error-boundary';
import { storeName } from '../../editor/store';

type EditProps = Omit<PremiumModalProps, 'onRequestClose'>;

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
    void selectStep(undefined);
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
export { PremiumModalForStepEditWithBoundary as PremiumModalForStepEdit };
