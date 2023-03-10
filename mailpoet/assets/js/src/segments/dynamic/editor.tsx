import { useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { Link, useRouteMatch } from 'react-router-dom';
import { Background } from 'common/background/background';
import { Heading } from 'common/typography/heading/heading';
import { HideScreenOptions } from 'common/hide_screen_options/hide_screen_options';
import { Form } from './form';

import { createStore } from './store/store';

export function Editor(): JSX.Element {
  const match = useRouteMatch<{ id: string }>();

  createStore();

  const { pageLoaded } = useDispatch('mailpoet-dynamic-segments-form');

  useEffect(() => {
    void pageLoaded(match.params.id);
  }, [match.params.id, pageLoaded]);

  return (
    <>
      <Background color="#fff" />
      <HideScreenOptions />

      <Heading level={1} className="mailpoet-title">
        <span>{__('Segment', 'mailpoet')}</span>
        <Link
          className="mailpoet-button button button-secondary button-small"
          to="/segments"
        >
          {__('Back', 'mailpoet')}
        </Link>
      </Heading>

      <Form segmentId={Number(match.params.id)} />
    </>
  );
}

Editor.displayName = 'SegmentEditor';
