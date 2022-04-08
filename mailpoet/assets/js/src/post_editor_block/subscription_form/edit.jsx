/* eslint-disable react/react-in-jsx-scope */
import PropTypes from 'prop-types';
import Icon from './icon.jsx';

const wp = window.wp;
const { Placeholder, PanelBody } = wp.components;
const { BlockIcon, InspectorControls } = wp.blockEditor;
const ServerSideRender = wp.serverSideRender;

const allForms = window.mailpoet_forms;

function Edit({ attributes, setAttributes }) {
  function displayFormsSelect() {
    if (!Array.isArray(allForms)) return null;
    if (allForms.length === 0) return null;
    return (
      <select
        onChange={(event) => {
          setAttributes({
            formId: parseInt(event.target.value, 10),
          });
        }}
        className="mailpoet-block-create-forms-list"
        value={attributes.formId}
      >
        <option value="" disabled selected>
          {window.locale.selectForm}
        </option>
        {allForms.map((form) => (
          <option value={form.id}>
            {form.name +
              (form.status === 'disabled'
                ? ` (${window.locale.inactive})`
                : '')}
          </option>
        ))}
      </select>
    );
  }

  function renderForm() {
    return (
      <ServerSideRender
        block="mailpoet/subscription-form-block-render"
        attributes={{ formId: attributes.formId }}
      />
    );
  }

  function selectFormSettings() {
    return (
      <div className="mailpoet-block-create-new-content">
        <a
          href="admin.php?page=mailpoet-form-editor-template-selection"
          target="_blank"
          className="mailpoet-block-create-new-link"
        >
          {window.locale.createForm}
        </a>
        {displayFormsSelect()}
      </div>
    );
  }

  return (
    <>
      <InspectorControls>
        <PanelBody title="MailPoet Subscription Form" initialOpen>
          {selectFormSettings()}
        </PanelBody>
      </InspectorControls>
      <div className="mailpoet-block-div">
        {attributes.formId === null && (
          <Placeholder
            className="mailpoet-block-create-new"
            icon={<BlockIcon icon={Icon} showColors />}
            label={window.locale.subscriptionForm}
          >
            {selectFormSettings()}
          </Placeholder>
        )}
        {attributes.formId !== null && renderForm()}
      </div>
    </>
  );
}

Edit.propTypes = {
  attributes: PropTypes.shape({
    formId: PropTypes.number,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
};

export default Edit;
