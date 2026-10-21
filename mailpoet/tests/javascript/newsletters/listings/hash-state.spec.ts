import {
  buildHash,
  parseFilter,
  parseHash,
} from '../../../../assets/js/src/newsletters/listings/hash-state';

const defaults = { sort: 'sent_at', order: 'desc' as const, perPage: 20 };

describe('newsletter listing hash state', () => {
  describe('parseHash', () => {
    it('returns empty state when the hash does not match the base url', () => {
      expect(
        parseHash('#/notification/group[trash]', 'standard', ['all', 'trash']),
      ).to.deep.equal({});
    });

    it('parses group, page, sort and search', () => {
      const state = parseHash(
        '#/standard/group[trash]/page[2]/sort_by[subject]/sort_order[asc]/search[hello%20world]',
        'standard',
        ['all', 'trash'],
      );
      expect(state.group).to.equal('trash');
      expect(state.page).to.equal(2);
      expect(state.orderby).to.equal('subject');
      expect(state.order).to.equal('asc');
      expect(state.search).to.equal('hello world');
    });

    it('ignores groups that are not supported', () => {
      const state = parseHash('#/standard/group[bogus]', 'standard', [
        'all',
        'trash',
      ]);
      expect(state.group).to.equal(undefined);
    });

    it('accepts the legacy paged/orderby/order/limit aliases', () => {
      const state = parseHash(
        '#/standard/paged[3]/orderby[sent_at]/order[desc]/limit[50]',
        'standard',
        ['all'],
      );
      expect(state.page).to.equal(3);
      expect(state.orderby).to.equal('sent_at');
      expect(state.order).to.equal('desc');
      expect(state.perPage).to.equal(50);
    });

    it('parses filters into a record', () => {
      const state = parseHash('#/standard/filter[segment=5]', 'standard', [
        'all',
      ]);
      expect(state.filter).to.deep.equal({ segment: '5' });
    });

    it('matches a nested base url for notification history', () => {
      const state = parseHash(
        '#/notification/history/12/group[trash]',
        'notification/history/12',
        ['all', 'trash'],
      );
      expect(state.group).to.equal('trash');
    });

    describe('legacy status groups', () => {
      const legacy = { key: 'status', values: ['draft', 'sent'] };
      const parse = (hash: string) =>
        parseHash(hash, 'standard', ['all', 'trash'], legacy);

      it('turns a legacy status group into the all tab and a status filter', () => {
        const state = parse('#/standard/group[sent]');
        expect(state.group).to.equal(undefined);
        expect(state.filter).to.deep.equal({ status: 'sent' });
      });

      it('lets an explicit filter win over the legacy group', () => {
        expect(
          parse('#/standard/group[sent]/filter[status=draft]').filter,
        ).to.deep.equal({ status: 'draft' });
        expect(
          parse('#/standard/filter[status=draft]/group[sent]').filter,
        ).to.deep.equal({ status: 'draft' });
      });

      it('keeps other filters next to the legacy status', () => {
        expect(
          parse('#/standard/group[draft]/filter[segment=3]').filter,
        ).to.deep.equal({ segment: '3', status: 'draft' });
      });

      it('keeps the trash tab and ignores unknown groups', () => {
        const trash = parse('#/standard/group[trash]');
        expect(trash.group).to.equal('trash');
        expect(trash.filter).to.equal(undefined);
        const unknown = parse('#/standard/group[bogus]');
        expect(unknown.group).to.equal(undefined);
        expect(unknown.filter).to.equal(undefined);
      });
    });
  });

  describe('buildHash', () => {
    it('omits values that match the defaults', () => {
      const view = {
        page: 1,
        perPage: 20,
        sort: { field: 'sent_at', direction: 'desc' as const },
      };
      expect(buildHash('standard', 'all', view, {}, defaults)).to.equal(
        '#/standard',
      );
    });

    it('encodes group, page, sort and search', () => {
      const view = {
        page: 2,
        perPage: 20,
        search: 'hello world',
        sort: { field: 'subject', direction: 'asc' as const },
      };
      expect(buildHash('standard', 'trash', view, {}, defaults)).to.equal(
        '#/standard/group[trash]/search[hello%20world]/page[2]/sort_by[subject]/sort_order[asc]',
      );
    });

    it('round-trips through parseHash', () => {
      const view = {
        page: 4,
        perPage: 50,
        search: 'newsletter',
        sort: { field: 'subject', direction: 'asc' as const },
      };
      const hash = buildHash(
        'standard',
        'trash',
        view,
        { segment: '7' },
        defaults,
      );
      const parsed = parseHash(hash, 'standard', ['all', 'trash']);
      expect(parsed.group).to.equal('trash');
      expect(parsed.page).to.equal(4);
      expect(parsed.perPage).to.equal(50);
      expect(parsed.search).to.equal('newsletter');
      expect(parsed.orderby).to.equal('subject');
      expect(parsed.order).to.equal('asc');
      expect(parsed.filter).to.deep.equal({ segment: '7' });
    });
  });

  describe('comma-joined filter values', () => {
    it('round-trips a list value through buildHash and parseHash', () => {
      const hash = buildHash(
        'standard',
        'all',
        {},
        { status: 'draft,sent', segment: '1,2' },
        defaults,
      );
      expect(hash).to.contain('%2C');
      expect(parseHash(hash, 'standard', ['all']).filter).to.deep.equal({
        status: 'draft,sent',
        segment: '1,2',
      });
    });
  });

  describe('parseFilter', () => {
    it('drops empty filter values', () => {
      expect(parseFilter('segment=5&type=')).to.deep.equal({ segment: '5' });
    });
  });
});
