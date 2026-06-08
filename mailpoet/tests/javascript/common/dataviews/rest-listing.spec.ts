import { buildRestApiPath } from '../../../../assets/js/src/common/dataviews/rest-listing';

describe('DataViews REST listing API', () => {
  describe('buildRestApiPath', () => {
    it('builds a request path for pretty permalinks', () => {
      const path = buildRestApiPath(
        'https://example.com/wp-json',
        '/mailpoet/v1/subscribers?page=1&per_page=20',
      );

      expect(path).to.equal(
        'https://example.com/wp-json/mailpoet/v1/subscribers?page=1&per_page=20',
      );
    });

    it('keeps query arguments outside rest_route for plain permalinks', () => {
      const path = buildRestApiPath(
        'https://example.com/index.php?rest_route=',
        '/mailpoet/v1/subscribers?page=1&per_page=20',
      );

      expect(path).to.equal(
        'https://example.com/index.php?rest_route=/mailpoet/v1/subscribers&page=1&per_page=20',
      );
    });
  });
});
