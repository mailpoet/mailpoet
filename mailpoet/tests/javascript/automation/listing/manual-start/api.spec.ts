import apiFetch, { APIFetchOptions } from '@wordpress/api-fetch';
import { registerApiErrorHandler } from '../../../../../assets/js/src/automation/listing/api-error-handler';
import {
  previewManualStart,
  startManualStart,
} from '../../../../../assets/js/src/automation/listing/manual-start/api';
import type {
  ManualStartPreview,
  ManualStartResult,
} from '../../../../../assets/js/src/automation/listing/manual-start/types';

const preview: ManualStartPreview = {
  preview_signature: 'signature',
  automation_id: 7,
  segment_id: 11,
  filter_segment_id: null,
  selected_count: 10,
  eligible_count: 4,
  skipped_by_reason: { already_entered: 2 },
  deferred_reason_keys: ['trigger_filter_mismatch'],
  duplicate_in_progress: false,
};

const result: ManualStartResult = {
  task_id: 99,
  automation_id: 7,
  segment_id: 11,
  filter_segment_id: null,
  selected_count: 10,
  eligible_count: 4,
  queued_count: 4,
  skipped_by_reason: { already_entered: 2 },
};

describe('automation manual-start API helpers', () => {
  before(() => {
    registerApiErrorHandler();
  });

  afterEach(() => {
    apiFetch.setFetchHandler(async () => ({}));
  });

  it('normalizes preview responses and sends the abort signal', async () => {
    const controller = new AbortController();
    let capturedOptions: APIFetchOptions | undefined;
    apiFetch.setFetchHandler(async (options) => {
      capturedOptions = options;
      return { data: preview };
    });

    const response = await previewManualStart(
      7,
      { segment_id: 11, filter_segment_id: null },
      controller.signal,
    );

    expect(response).to.deep.equal(preview);
    expect(capturedOptions?.path).to.contain(
      '/automations/7/manual-start/preview',
    );
    expect(capturedOptions?.method).to.equal('POST');
    expect(capturedOptions?.signal).to.equal(controller.signal);
    expect(capturedOptions?.data).to.deep.equal({
      segment_id: 11,
      filter_segment_id: null,
    });
  });

  it('rethrows aborted preview requests without manual-start error wrapping', async () => {
    const controller = new AbortController();
    controller.abort();
    apiFetch.setFetchHandler(async () => undefined);

    try {
      await previewManualStart(
        7,
        { segment_id: 11, filter_segment_id: null },
        controller.signal,
      );
      throw new Error('Expected abort error.');
    } catch (error) {
      expect((error as { name?: string }).name).to.equal('AbortError');
      expect((error as { code?: string }).code).to.equal(undefined);
    }
  });

  it('normalizes start responses and posts the preview signature', async () => {
    let capturedOptions: APIFetchOptions | undefined;
    apiFetch.setFetchHandler(async (options) => {
      capturedOptions = options;
      return { data: result };
    });

    const response = await startManualStart(7, {
      segment_id: 11,
      filter_segment_id: null,
      preview_signature: 'signature',
    });

    expect(response).to.deep.equal(result);
    expect(capturedOptions?.path).to.contain('/automations/7/manual-start');
    expect(capturedOptions?.method).to.equal('POST');
    expect(capturedOptions?.data).to.deep.equal({
      segment_id: 11,
      filter_segment_id: null,
      preview_signature: 'signature',
    });
  });

  it('preserves structured manual-start 4xx errors through middleware', async () => {
    apiFetch.setFetchHandler(async () => {
      throw Object.assign(new Error('Refresh the preview.'), {
        code: 'manual_start_stale_preview',
        message: 'Refresh the preview.',
        data: {
          status: 409,
          preview,
        },
      });
    });

    try {
      await startManualStart(7, {
        segment_id: 11,
        filter_segment_id: null,
        preview_signature: 'old-signature',
      });
      throw new Error('Expected manual-start API error.');
    } catch (error) {
      expect((error as { code?: string }).code).to.equal(
        'manual_start_stale_preview',
      );
      expect(
        (error as { data?: { preview?: ManualStartPreview } }).data?.preview,
      ).to.deep.equal(preview);
    }
  });
});
