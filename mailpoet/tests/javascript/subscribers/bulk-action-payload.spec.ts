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
      { segment_id: 5 },
    );
    expect(payload.segment_id).to.equal(5);
    expect(payload.select_all).to.equal(true);
    expect(payload.group).to.equal('subscribed');
    expect(payload.search).to.equal('foo');
  });
});
