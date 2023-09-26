import { Panel, PanelBody } from '@wordpress/components';
import { MailPoet } from 'mailpoet';
import PropTypes from 'prop-types';

import { BelowPages as FormPlacementOptionBelowPages } from './form-placement-options/below-pages';
import { Popup as FormPlacementOptionPopup } from './form-placement-options/popup';
import { FixedBar as FormPlacementOptionFixedBar } from './form-placement-options/fixed-bar';
import { SlideIn as FormPlacementOptionSlideIn } from './form-placement-options/slide-in';
import { Other as FormPlacementOptionOther } from './form-placement-options/other';

function FormPlacementPanel({ onToggle, isOpened }) {
  return (
    <Panel>
      <PanelBody
        title={MailPoet.I18n.t('formPlacement')}
        opened={isOpened}
        onToggle={onToggle}
        className="form-sidebar-form-placement-panel"
      >
        <div className="form-placement-option-list">
          <FormPlacementOptionBelowPages />
          <FormPlacementOptionFixedBar />
          <FormPlacementOptionPopup />
          <FormPlacementOptionSlideIn />
          <FormPlacementOptionOther />
        </div>
      </PanelBody>
    </Panel>
  );
}

FormPlacementPanel.propTypes = {
  onToggle: PropTypes.func.isRequired,
  isOpened: PropTypes.bool.isRequired,
};

export { FormPlacementPanel };
