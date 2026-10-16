import {
  buildSubscribersHash,
  parseSubscribersHash,
  type SubscribersHashState,
} from '../../../assets/js/src/subscribers/hash-state';

const defaults = {
  orderby: 'created_at',
  order: 'desc' as const,
  perPage: 20,
};

describe('Subscribers hash state: parse', () => {
  it('swaps a reversed score range from the URL', () => {
    expect(
      parseSubscribersHash('#/filter[score_min=90&score_max=10]').filter,
    ).to.deep.equal({ score_min: 10, score_max: 90 });
  });

  it('parses the Lists page link', () => {
    expect(parseSubscribersHash('#/filter[segment=5]')).to.deep.equal({
      group: 'all',
      filter: { segment_in: [5] },
    });
  });

  it('parses the Tags page link', () => {
    expect(parseSubscribersHash('#/filter[tag=8]').filter).to.deep.equal({
      tag_in: [8],
    });
  });

  it('parses the homepage link', () => {
    expect(
      parseSubscribersHash(
        '#/page[1]/sort_by[created_at]/sort_order[desc]/group[all]/filter[segment=3]',
      ),
    ).to.deep.equal({
      group: 'all',
      page: 1,
      orderby: 'created_at',
      order: 'desc',
      filter: { segment_in: [3] },
    });
  });

  it('parses group all with limit 1', () => {
    expect(
      parseSubscribersHash('#/group[all]/filter[segment=3]/limit[1]'),
    ).to.deep.equal({
      group: 'all',
      perPage: 1,
      filter: { segment_in: [3] },
    });
  });

  it('turns a legacy status group into status_in', () => {
    [
      'subscribed',
      'unconfirmed',
      'unsubscribed',
      'inactive',
      'bounced',
    ].forEach((status) => {
      expect(parseSubscribersHash(`#/group[${status}]`)).to.deep.equal({
        group: 'all',
        filter: { status_in: [status] },
      });
    });
  });

  it('lets an explicit status_in beat the legacy group', () => {
    expect(
      parseSubscribersHash('#/group[bounced]/filter[status_in=subscribed]'),
    ).to.deep.equal({ group: 'all', filter: { status_in: ['subscribed'] } });
  });

  it('keeps group trash and falls back to all for unknown groups', () => {
    expect(parseSubscribersHash('#/group[trash]').group).to.equal('trash');
    expect(parseSubscribersHash('#/group[nonsense]')).to.deep.equal({
      group: 'all',
      filter: {},
    });
  });

  it('returns defaults for an empty hash', () => {
    expect(parseSubscribersHash('')).to.deep.equal({
      group: 'all',
      filter: {},
    });
    expect(parseSubscribersHash('#/')).to.deep.equal({
      group: 'all',
      filter: {},
    });
  });

  it('parses search, paging and sorting aliases', () => {
    expect(
      parseSubscribersHash(
        '#/search[a%40b.com]/paged[3]/per_page[50]/orderby[email]/order[asc]',
      ),
    ).to.deep.equal({
      group: 'all',
      search: 'a@b.com',
      page: 3,
      perPage: 50,
      orderby: 'email',
      order: 'asc',
      filter: {},
    });
  });

  it('maps sort_by engagement_score to the statistics field', () => {
    expect(
      parseSubscribersHash('#/sort_by[engagement_score]').orderby,
    ).to.equal('statistics');
  });

  it('decodes values containing separators', () => {
    const hash = `#/filter[segment_in=${encodeURIComponent(
      '1,2',
    )},3&source_in=${encodeURIComponent('a/b[c]&d=e')}]`;
    expect(parseSubscribersHash(hash).filter).to.deep.equal({
      segment_in: [3],
    });
  });

  it('decodes percent-encoded filter values', () => {
    expect(
      parseSubscribersHash(
        '#/filter[source_in=%66orm&created_from=2025%2D03%2D01]',
      ).filter,
    ).to.deep.equal({ source_in: ['form'], created_from: '2025-03-01' });
  });

  it('parses multi-value sets, scores and dates', () => {
    expect(
      parseSubscribersHash(
        '#/filter[status_in=subscribed,bounced&status_not_in=inactive&segment_in=1,without-list&tag_not_in=2&source_in=form&score_gt=1.5&score_max=4&created_from=2025-03-01&subscribed_to=2025-03-31]',
      ).filter,
    ).to.deep.equal({
      status_in: ['subscribed', 'bounced'],
      status_not_in: ['inactive'],
      segment_in: [1, 'without-list'],
      tag_not_in: [2],
      source_in: ['form'],
      score_gt: 1.5,
      score_max: 4,
      created_from: '2025-03-01',
      subscribed_to: '2025-03-31',
    });
  });

  it('drops invalid dates, scores and ids', () => {
    expect(
      parseSubscribersHash(
        '#/filter[created_from=2025-02-30&created_to=bogus&score_min=abc&tag_in=x,0&segment_in=-1]',
      ).filter,
    ).to.deep.equal({});
  });
});

describe('Subscribers hash state: build', () => {
  it('writes sort_by only when the sort field differs from the default', () => {
    const base: SubscribersHashState = { group: 'all', filter: {} };
    expect(
      buildSubscribersHash({ ...base, orderby: 'created_at' }, defaults),
    ).to.not.contain('sort_by');
    expect(
      buildSubscribersHash({ ...base, orderby: 'email' }, defaults),
    ).to.contain('sort_by[email]');
    expect(
      buildSubscribersHash({ ...base, orderby: 'statistics' }, defaults),
    ).to.contain('sort_by[engagement_score]');
  });

  it('writes non-default order, page and limit only', () => {
    expect(
      buildSubscribersHash(
        { group: 'all', filter: {}, order: 'asc', page: 2, perPage: 50 },
        defaults,
      ),
    ).to.equal('#/group[all]/page[2]/limit[50]/sort_order[asc]');
    expect(
      buildSubscribersHash(
        { group: 'all', filter: {}, order: 'desc', page: 1, perPage: 20 },
        defaults,
      ),
    ).to.equal('#/group[all]');
  });

  it('encodes each value so separators cannot break the grammar', () => {
    const hash = buildSubscribersHash(
      { group: 'all', filter: { source_in: ['a,b/c[d]&e=f'] } },
      defaults,
    );
    expect(hash).to.equal(
      `#/group[all]/filter[source_in=${encodeURIComponent('a,b/c[d]&e=f')}]`,
    );
  });

  it('round trips a full state', () => {
    const state: SubscribersHashState = {
      group: 'trash',
      page: 3,
      perPage: 50,
      order: 'asc',
      orderby: 'statistics',
      search: 'a b/c[d]&é',
      filter: {
        status_in: ['subscribed', 'bounced'],
        status_not_in: ['inactive'],
        segment_in: [1, 'without-list'],
        segment_not_in: [4],
        tag_in: [2],
        tag_not_in: [5],
        source_in: ['form'],
        source_not_in: ['api'],
        score_gt: 1.5,
        score_lt: 9,
        score_min: 2,
        score_max: 8,
        created_from: '2025-03-01',
        created_to: '2025-03-31',
        subscribed_from: '2025-01-01',
        subscribed_to: '2025-01-31',
      },
    };
    expect(
      parseSubscribersHash(buildSubscribersHash(state, defaults)),
    ).to.deep.equal(state);
  });

  it('round trips a minimal state', () => {
    const state: SubscribersHashState = { group: 'all', filter: {} };
    expect(
      parseSubscribersHash(buildSubscribersHash(state, defaults)),
    ).to.deep.equal(state);
  });
});
