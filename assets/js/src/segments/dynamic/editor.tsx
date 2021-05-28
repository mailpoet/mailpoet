import React, { useEffect } from 'react';
import { useDispatch, useSelect } from '@wordpress/data';
import { useRouteMatch, Link } from 'react-router-dom';

import MailPoet from 'mailpoet';
import Background from 'common/background/background';
import Heading from 'common/typography/heading/heading';
import HideScreenOptions from 'common/hide_screen_options/hide_screen_options';
import { Form } from './form';

import APIErrorsNotice from '../../notices/api_errors_notice';

const Editor: React.FunctionComponent = () => {
  const match = useRouteMatch<{id: string}>();

  const errors: string[] = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getErrors(),
    []
  );

  const { pageLoaded, handleSave } = useDispatch('mailpoet-dynamic-segments-form');

  useEffect(() => {
    pageLoaded(match.params.id);
  }, [match.params.id, pageLoaded]);

  function save(e: Event): void {
    e.preventDefault();
    handleSave(match.params.id);
  }

  return (
    <>
      <Background color="#fff" />
      <HideScreenOptions />
      {(errors.length > 0 && (
        <APIErrorsNotice errors={errors.map((error) => ({ message: error }))} />
      ))}

      <Heading level={1} className="mailpoet-title">
        <span>{MailPoet.I18n.t('formPageTitle')}</span>
        <Link className="mailpoet-button mailpoet-button-small" to="/segments">{MailPoet.I18n.t('backToList')}</Link>
      </Heading>

      <Form
        onSave={save}
      />
    </>
  );
};

export default Editor;
