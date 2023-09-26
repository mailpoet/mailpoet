import { expect } from 'chai';
import { mapFormDataBeforeSaving as map } from '../../../../assets/js/src/form_editor/store/map_form_data_before_saving.jsx';

const data = {
  id: '1',
  name: 'My First Form',
  settings: {
    segments: ['3'],
    on_success: 'message',
    success_message:
      'Check your inbox or spam folder to confirm your subscription.',
    success_page: '5',
    segments_selected_by: 'admin',
    formPlacement: {
      belowPosts: {
        enabled: true,
        posts: {
          all: false,
          selected: ['3'],
        },
        pages: {
          all: true,
        },
      },
      fixedBar: {
        animation: 'slideright',
      },
      popup: {},
    },
  },
  styles: 'styles definition',
  created_at: '2020-01-15 07:39:15',
  updated_at: '2020-01-28 10:28:02',
  deleted_at: null,
};

describe('Form Data Save Mapper', () => {
  it('Returns ID', () => {
    expect(map(data)).to.have.property('id', '1');
  });

  it('Returns name', () => {
    expect(map(data)).to.have.property('name', 'My First Form');
  });

  it('Returns styles', () => {
    expect(map(data)).to.have.property('styles', 'styles definition');
  });

  it('Returns dates', () => {
    expect(map(data)).to.have.property('created_at', '2020-01-15 07:39:15');
    expect(map(data)).to.have.property('updated_at', '2020-01-28 10:28:02');
    expect(map(data)).to.have.property('deleted_at').to.be.equal(null);
  });

  describe('Settings', () => {
    it('Maps settings', () => {
      expect(map(data)).to.have.property('settings').that.is.an('object');
    });

    it('Maps segments', () => {
      expect(map(data).settings)
        .to.have.property('segments')
        .that.deep.eq(['3']);
    });

    it('Maps Success', () => {
      expect(map(data).settings).to.have.property('on_success', 'message');
      expect(map(data).settings).to.have.property(
        'success_message',
        'Check your inbox or spam folder to confirm your subscription.',
      );
      expect(map(data).settings).to.have.property('success_page', '5');
    });

    it('maps placement', () => {
      const result = map(data).settings;
      expect(result)
        .to.have.property('form_placement')
        .that.is.an('object')
        .that.have.property('below_posts')
        .that.is.an('object');
      expect(result.form_placement.below_posts).to.have.property(
        'enabled',
        '1',
      );
      expect(result.form_placement.below_posts)
        .to.have.property('posts')
        .that.is.an('object')
        .that.have.property('all', '');
      expect(result.form_placement.below_posts)
        .to.have.property('pages')
        .that.is.an('object')
        .that.have.property('all', '1');
      expect(result.form_placement.below_posts)
        .to.have.property('posts')
        .that.is.an('object')
        .that.have.property('selected')
        .that.deep.equal(['3']);
    });

    it('Maps animation', () => {
      expect(map(data).settings.form_placement.fixed_bar)
        .to.have.property('animation')
        .that.eq('slideright');
      expect(map(data).settings.form_placement.popup).to.have.property(
        'animation',
      );
      expect(
        map(data).settings.form_placement.below_posts,
      ).to.not.have.property('animation');
    });
  });
});
