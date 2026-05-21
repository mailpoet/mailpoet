import { AutomationStatus } from '../../../../../assets/js/src/automation/listing/automation';
import {
  canConfirmManualStart,
  getManualStartDefaultListOptions,
  getManualStartDynamicSegmentOptions,
  getManualStartErrorState,
  getSegmentIdNumber,
  isBlockingManualStartError,
  isManualStartApiPath,
  isManualStartSupported,
  normalizeManualStartError,
  normalizeSegmentId,
  previewMatchesSelections,
} from '../../../../../assets/js/src/automation/listing/manual-start/helpers';
import type { AutomationItem } from '../../../../../assets/js/src/automation/listing/store/types';
import type {
  MailPoetSegment,
  ManualStartMetadata,
  ManualStartPreview,
} from '../../../../../assets/js/src/automation/listing/manual-start/types';

const metadata: ManualStartMetadata = {
  supported: true,
  trigger_key: 'mailpoet:someone-subscribes',
  segment_ids: null,
};

const automation = (overrides: Partial<AutomationItem> = {}): AutomationItem =>
  ({
    id: 1,
    name: 'Automation',
    status: AutomationStatus.ACTIVE,
    stats: {
      totals: {
        entered: 0,
        in_progress: 0,
        exited: 0,
      },
    },
    manual_start: metadata,
    ...overrides,
  } as AutomationItem);

const preview = (
  overrides: Partial<ManualStartPreview> = {},
): ManualStartPreview => ({
  preview_signature: 'signature',
  automation_id: 1,
  segment_id: 1,
  filter_segment_id: null,
  selected_count: 10,
  eligible_count: 5,
  skipped_by_reason: {},
  deferred_reason_keys: [],
  duplicate_in_progress: false,
  ...overrides,
});

const segments: MailPoetSegment[] = [
  { id: '1', name: 'Newsletter', subscribers: '10', type: 'default' },
  { id: '02', name: 'Customers', subscribers: '20', type: 'default' },
  { id: '3', name: 'Engaged', subscribers: '5', type: 'dynamic' },
  {
    id: '4',
    name: 'Deleted',
    subscribers: '0',
    type: 'default',
    deleted_at: '2026-05-21 10:00:00',
  },
  { id: 'invalid', name: 'Invalid', subscribers: '0', type: 'default' },
];

describe('automation manual-start helpers', () => {
  it('supports only active non-legacy automations with manual-start metadata', () => {
    expect(isManualStartSupported(automation())).to.equal(true);
    expect(
      isManualStartSupported(automation({ status: AutomationStatus.DRAFT })),
    ).to.equal(false);
    expect(isManualStartSupported(automation({ isLegacy: true }))).to.equal(
      false,
    );
    expect(
      isManualStartSupported(
        automation({ manual_start: { ...metadata, supported: false } }),
      ),
    ).to.equal(false);
  });

  it('normalizes segment IDs before comparison and API conversion', () => {
    expect(normalizeSegmentId('02')).to.equal('2');
    expect(normalizeSegmentId(3)).to.equal('3');
    expect(normalizeSegmentId('not-a-number')).to.equal('');
    expect(getSegmentIdNumber('02')).to.equal(2);
    expect(getSegmentIdNumber('')).to.equal(null);
  });

  it('returns default-list options restricted by manual-start metadata', () => {
    expect(getManualStartDefaultListOptions(segments, metadata)).to.deep.equal([
      { id: '1', name: 'Newsletter', subscribers: '10' },
      { id: '2', name: 'Customers', subscribers: '20' },
    ]);

    expect(
      getManualStartDefaultListOptions(segments, {
        ...metadata,
        segment_ids: [2],
      }),
    ).to.deep.equal([{ id: '2', name: 'Customers', subscribers: '20' }]);

    expect(
      getManualStartDefaultListOptions(segments, {
        ...metadata,
        segment_ids: [],
      }).map((segment) => segment.id),
    ).to.deep.equal(['1', '2']);
  });

  it('returns only available dynamic segments for optional filters', () => {
    expect(getManualStartDynamicSegmentOptions(segments)).to.deep.equal([
      { id: '3', name: 'Engaged', subscribers: '5' },
    ]);
  });

  it('matches previews to the current list and filter selections', () => {
    expect(previewMatchesSelections(preview(), '1', '')).to.equal(true);
    expect(
      previewMatchesSelections(preview({ filter_segment_id: 3 }), '1', '03'),
    ).to.equal(true);
    expect(previewMatchesSelections(preview(), '2', '')).to.equal(false);
    expect(previewMatchesSelections(preview(), '1', '3')).to.equal(false);
  });

  it('allows confirmation only for current previews with eligible subscribers', () => {
    expect(canConfirmManualStart(preview(), '1', '')).to.equal(true);
    expect(
      canConfirmManualStart(preview({ eligible_count: 0 }), '1', ''),
    ).to.equal(false);
    expect(
      canConfirmManualStart(preview({ duplicate_in_progress: true }), '1', ''),
    ).to.equal(false);
    expect(canConfirmManualStart(preview(), '2', '')).to.equal(false);
  });

  it('detects manual-start API paths for middleware error handling', () => {
    expect(
      isManualStartApiPath('/automations/12/manual-start/preview'),
    ).to.equal(true);
    expect(isManualStartApiPath('/automations/12/manual-start')).to.equal(true);
    expect(
      isManualStartApiPath('/mailpoet/v1/automations/12/manual-start'),
    ).to.equal(true);
    expect(isManualStartApiPath('/automations/12/duplicate')).to.equal(false);
  });

  it('preserves structured manual-start errors', () => {
    const stalePreview = preview({ eligible_count: 6 });
    const error = normalizeManualStartError({
      code: 'manual_start_stale_preview',
      message: 'Refresh the preview.',
      data: {
        status: 409,
        preview: stalePreview,
      },
    });

    expect(error.code).to.equal('manual_start_stale_preview');
    expect(error.message).to.equal('Refresh the preview.');
    expect(error.data.preview).to.deep.equal(stalePreview);
    expect(getManualStartErrorState(error)).to.equal('stale-preview');
    expect(isBlockingManualStartError(error)).to.equal(true);
  });

  it('maps known error codes to modal states', () => {
    expect(
      getManualStartErrorState({
        code: 'manual_start_zero_eligible',
        message: '',
        data: { status: 422 },
      }),
    ).to.equal('zero-eligible');
    expect(
      getManualStartErrorState({
        code: 'manual_start_in_progress',
        message: '',
        data: { status: 409 },
      }),
    ).to.equal('duplicate-in-progress');
    expect(
      getManualStartErrorState({
        code: 'manual_start_invalid_segment',
        message: '',
        data: { status: 400 },
      }),
    ).to.equal('validation');
    expect(getManualStartErrorState(normalizeManualStartError(null))).to.equal(
      'unknown',
    );
  });
});
