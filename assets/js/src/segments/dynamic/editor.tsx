import React, { useEffect, useState } from 'react';
import { useSelect, useDispatch } from '@wordpress/data';
import {
  compose,
  prop,
} from 'lodash/fp';
import { useRouteMatch, Link, useHistory } from 'react-router-dom';

import MailPoet from 'mailpoet';
import Background from 'common/background/background';
import Heading from 'common/typography/heading/heading';
import HideScreenOptions from 'common/hide_screen_options/hide_screen_options';
import { Form } from './form';

import {
  AnyFormItem,
} from './types';
import APIErrorsNotice from '../../notices/api_errors_notice';

const messages = {
  onUpdate: (): void => {
    MailPoet.Notice.success(MailPoet.I18n.t('dynamicSegmentUpdated'));
  },
  onCreate: (data): void => {
    MailPoet.Notice.success(MailPoet.I18n.t('dynamicSegmentAdded'));
    MailPoet.trackEvent('Segments > Add new', {
      'MailPoet Free version': MailPoet.version,
      type: data.segmentType || 'unknown type',
      subtype: data.action || data.wordpressRole || 'unknown subtype',
    });
  },
};

const Editor: React.FunctionComponent = () => {
  const [errors, setErrors] = useState([]);
  const match = useRouteMatch<{id: string}>();
  const history = useHistory();

  const segment: AnyFormItem = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSegment(),
    []
  );

  const { pageLoaded } = useDispatch('mailpoet-dynamic-segments-form');

  useEffect(() => {
    pageLoaded(match.params.id);
  }, [match.params.id, pageLoaded]);

  function handleSave(e: Event): void {
    e.preventDefault();
    setErrors([]);
    MailPoet.Ajax.post({
      api_version: MailPoet.apiVersion,
      endpoint: 'dynamic_segments',
      action: 'save',
      data: segment,
    }).done(() => {
      history.push('/segments');

      if (match.params.id !== undefined) {
        messages.onUpdate();
      } else {
        messages.onCreate(segment);
      }
    }).fail(compose([setErrors, prop('errors')]));
  }

  return (
    <>
      <Background color="#fff" />
      <HideScreenOptions />
      {(errors.length > 0 && (
        <APIErrorsNotice errors={errors} />
      ))}

      <Heading level={1} className="mailpoet-title">
        <span>{MailPoet.I18n.t('formPageTitle')}</span>
        <Link className="mailpoet-button mailpoet-button-small" to="/segments">{MailPoet.I18n.t('backToList')}</Link>
      </Heading>

      <Form
        onSave={handleSave}
      />
    </>
  );
};

export default Editor;
