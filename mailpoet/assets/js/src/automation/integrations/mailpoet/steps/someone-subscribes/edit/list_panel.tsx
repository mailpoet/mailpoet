import { useEffect, useState } from 'react';
import { PanelBody, Spinner } from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { storeName } from '../../../../../editor/store';
import { MailPoetAjax } from '../../../../../../ajax';
import { RawSegment, Segment } from './segment';
import { PlainBodyTitle } from '../../../../../editor/components/panel';
import {
  FormTokenItem,
  FormTokenField,
} from '../../../components/form-token-field';

export function ListPanel(): JSX.Element {
  const { selectedStep } = useSelect(
    (select) => ({
      selectedStep: select(storeName).getSelectedStep(),
    }),
    [],
  );

  const [segments, setSegments] = useState<FormTokenItem[]>([]);

  useEffect(() => {
    const getData = async () => {
      const data = await MailPoetAjax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'segments',
        action: 'listing',
      });
      if (data?.data) {
        const loadedSegments: FormTokenItem[] = data.data
          .map((segment: RawSegment) => {
            const sanitizedSegment: Segment = {
              ...segment,
              id: parseInt(segment.id, 10),
            };
            return sanitizedSegment;
          })
          .filter(
            (segment: Segment): boolean =>
              !['wp_users', 'woocommerce_users'].includes(segment.type),
          );
        setSegments(loadedSegments);
      }
    };

    void getData();
  }, []);

  const rawSelected = selectedStep.args?.segment_ids
    ? (selectedStep.args.segment_ids as number[])
    : [];
  const selected = segments.filter((segment): boolean =>
    rawSelected.includes(segment.id as number),
  );
  return (
    <PanelBody opened>
      <PlainBodyTitle title={__('Trigger settings', 'mailpoet')} />
      {segments.length > 0 && (
        <FormTokenField
          label={__(
            'When someone subscribers to the following list(s):',
            'mailpoet',
          )}
          placeholder={__('Any list', 'mailpoet')}
          selected={selected}
          suggestions={segments}
          onChange={(values) => {
            dispatch(storeName).updateStepArgs(
              selectedStep.id,
              'segment_ids',
              values.map((item) => item.id),
            );
          }}
        />
      )}
      {segments.length === 0 && <Spinner />}
    </PanelBody>
  );
}
