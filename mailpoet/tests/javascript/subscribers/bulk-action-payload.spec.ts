import { buildBulkActionPayload } from '../../../assets/js/src/subscribers/bulk-action-payload';

describe('Subscribers bulk-action payload', () => {
  const scope = {
    group: 'all',
    filter: {},
    search: '',
    selection: [1, 2, 3],
  };

  it('sends the explicit selection when selectAll is falsy', () => {
    const payload = buildBulkActionPayload('trash', scope);
    expect(payload.select_all).to.equal(false);
    expect(payload.selection).to.deep.equal([1, 2, 3]);
    expect(payload.action).to.equal('trash');
  });

  it('clears the selection and sets select_all when selectAll is true', () => {
    const payload = buildBulkActionPayload('trash', {
      ...scope,
      selectAll: true,
    });
    expect(payload.select_all).to.equal(true);
    expect(payload.selection).to.deep.equal([]);
  });

  it('merges extra params and forwards scope fields', () => {
    const payload = buildBulkActionPayload(
      'moveToList',
      { ...scope, group: 'subscribed', search: 'foo', selectAll: true },
      { segment_id: 5, trigger_automations: true },
    );
    expect(payload.segment_id).to.equal(5);
    expect(payload.trigger_automations).to.equal(true);
    expect(payload.select_all).to.equal(true);
    expect(payload.group).to.equal('subscribed');
    expect(payload.search).to.equal('foo');
  });

  it('forwards the automation opt-in for supported actions', () => {
    const addToListPayload = buildBulkActionPayload('addToList', scope, {
      segment_id: 5,
      trigger_automations: false,
    });
    const addTagPayload = buildBulkActionPayload('addTag', scope, {
      tag_id: 7,
      trigger_automations: true,
    });
    const removeTagPayload = buildBulkActionPayload('removeTag', scope, {
      tag_id: 7,
      trigger_automations: false,
    });

    expect(addToListPayload.trigger_automations).to.equal(false);
    expect(addTagPayload.trigger_automations).to.equal(true);
    expect(removeTagPayload.trigger_automations).to.equal(false);
  });

  it('does not forward the automation opt-in for unsupported actions', () => {
    const payload = buildBulkActionPayload('removeFromList', scope, {
      segment_id: 5,
      trigger_automations: true,
    });

    expect(payload).not.to.have.property('trigger_automations');
  });
});
