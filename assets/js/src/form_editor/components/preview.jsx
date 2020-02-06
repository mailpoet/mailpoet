import React, { useEffect, useState } from 'react';
import { Spinner } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

import Preview from '../../common/preview.jsx';
import Modal from '../../common/modal/modal.jsx';

const FormPreview = () => {
  const [form, setForm] = useState(null);

  const { hidePreview } = useDispatch('mailpoet-form-editor');
  const isPreview = useSelect(
    (select) => select('mailpoet-form-editor').getIsPreviewShown(),
    []
  );

  function loadFormPreviewFromServer() {

  }

  useEffect(() => {
    if (isPreview) {
      loadFormPreviewFromServer();
    }
  }, [isPreview]);

  if (!isPreview) return null;

  return (
    <Modal
      title="Form Preview"
      onRequestClose={hidePreview}
    >
      <Preview>
        {form === null && (
          <Spinner />
        )}
        {form !== null && (
          <div dangerouslySetInnerHTML={form} />
        )}
      </Preview>
    </Modal>
  );
};

export default FormPreview;
