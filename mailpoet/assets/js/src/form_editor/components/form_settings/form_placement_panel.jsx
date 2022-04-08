import { Panel, PanelBody } from '@wordpress/components';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';

import FormPlacementOptionBelowPages from './form_placement_options/below_pages';
import FormPlacementOptionPopup from './form_placement_options/popup';
import FormPlacementOptionFixedBar from './form_placement_options/fixed_bar';
import FormPlacementOptionSlideIn from './form_placement_options/slide_in';
import FormPlacementOptionOther from './form_placement_options/other';

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

export default FormPlacementPanel;
