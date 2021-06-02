import React, { useEffect } from 'react';
import { useDispatch, useSelect } from '@wordpress/data';
import { useRouteMatch, Link } from 'react-router-dom';

import MailPoet from 'mailpoet';
import Background from 'common/background/background';
import Heading from 'common/typography/heading/heading';
import HideScreenOptions from 'common/hide_screen_options/hide_screen_options';
import { Form } from './form';

import APIErrorsNotice from '../../notices/api_errors_notice';
import { createStore } from './store/store';

const Editor: React.FunctionComponent = () => {
  const match = useRouteMatch<{id: string}>();

  createStore();

  const errors: string[] = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getErrors(),
    []
  );

  const { pageLoaded } = useDispatch('mailpoet-dynamic-segments-form');

  useEffect(() => {
    pageLoaded(match.params.id);
  }, [match.params.id, pageLoaded]);

  return (
    <>
      <Background color="#fff" />
      <HideScreenOptions />
      {(errors.length > 0 && (
        <APIErrorsNotice errors={errors.map((error) => ({ message: error }))} />
      ))}

      <Heading level={1} className="mailpoet-title">
        <span>{MailPoet.I18n.t('formPageTitle')}</span>
        <Link className="mailpoet-button mailpoet-button-small mailpoet-button-secondary button-secondary" to="/segments">{MailPoet.I18n.t('backToList')}</Link>
      </Heading>

      <Form segmentId={Number(match.params.id)} />
    </>
  );
};

export default Editor;
