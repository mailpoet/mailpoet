import { type Segment } from '../../../assets/js/src/subscribers/api';
import {
  getInitialPickerValue,
  type PickerConfig,
} from '../../../assets/js/src/subscribers/picker-value';

const addToList: PickerConfig = {
  kind: 'segment',
  fieldId: 'add_to_segment',
  endpoint: 'segments',
  filter: (segment) => !!(!segment.deleted_at && segment.type === 'default'),
};

const removeFromList: PickerConfig = {
  kind: 'segment',
  fieldId: 'remove_from_segment',
  endpoint: 'segments',
  filter: (segment) => segment.type === 'default',
};

const addTag: PickerConfig = {
  kind: 'tag',
  fieldId: 'add_tag',
  endpoint: 'tags',
};

type WindowGlobals = typeof globalThis & {
  window?: Partial<Window> & typeof globalThis;
};

function setWindow(data: {
  segments?: Segment[];
  tags?: { id: number; name: string }[];
}): void {
  const globals = global as WindowGlobals;
  globals.window = {
    mailpoet_segments: data.segments,
    mailpoet_tags: data.tags,
  } as unknown as Window & typeof globalThis;
}

describe('getInitialPickerValue', () => {
  afterEach(() => {
    delete (global as WindowGlobals).window;
  });

  it('seeds the first selectable list so Apply is enabled on open', () => {
    setWindow({
      segments: [
        { id: '7', name: 'Newsletter', subscribers: '3', type: 'default' },
        { id: '8', name: 'Updates', subscribers: '1', type: 'default' },
      ],
    });
    expect(getInitialPickerValue(addToList)).to.equal(7);
  });

  it('skips lists removed by the filter when picking the first value', () => {
    setWindow({
      segments: [
        {
          id: '2',
          name: 'Deleted',
          subscribers: '0',
          type: 'default',
          deleted_at: '2026-01-01 00:00:00',
        },
        { id: '5', name: 'Newsletter', subscribers: '3', type: 'default' },
      ],
    });
    expect(getInitialPickerValue(addToList)).to.equal(5);
  });

  it('applies each picker filter, so remove-from-list keeps deleted lists that add-to-list skips', () => {
    setWindow({
      segments: [
        {
          id: '2',
          name: 'Deleted',
          subscribers: '0',
          type: 'default',
          deleted_at: '2026-01-01 00:00:00',
        },
        { id: '5', name: 'Newsletter', subscribers: '3', type: 'default' },
      ],
    });
    // removeFromList filters on type only (no deleted_at check), so the deleted
    // list stays selectable and remains the first option.
    expect(getInitialPickerValue(removeFromList)).to.equal(2);
  });

  it('returns 0 when no list is selectable', () => {
    setWindow({ segments: [] });
    expect(getInitialPickerValue(addToList)).to.equal(0);
  });

  it('seeds the first tag for tag pickers', () => {
    setWindow({
      tags: [
        { id: 3, name: 'VIP' },
        { id: 4, name: 'Lead' },
      ],
    });
    expect(getInitialPickerValue(addTag)).to.equal(3);
  });

  it('returns 0 when no tags exist', () => {
    setWindow({});
    expect(getInitialPickerValue(addTag)).to.equal(0);
  });
});
