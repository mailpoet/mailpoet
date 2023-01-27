import { expect } from 'chai';
import { mapFormDataAfterLoading as map } from '../../../../assets/js/src/form_editor/store/map_form_data_after_loading.jsx';

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
    form_placement: {
      below_posts: {
        enabled: '1',
        posts: {
          all: '',
          selected: ['1'],
        },
        pages: {
          all: '1',
        },
      },
      fixed_bar: {
        animation: 'slideright',
      },
      slide_in: {
        animation: 'flip',
      },
      popup: {},
    },
  },
  styles: 'styles definition',
  created_at: '2020-01-15 07:39:15',
  updated_at: '2020-01-28 10:28:02',
  deleted_at: null,
};

describe('Form Data Load Mapper', () => {
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

    it('Maps placement', () => {
      const result = map(data).settings;
      expect(result)
        .to.have.property('formPlacement')
        .that.is.an('object')
        .that.have.property('belowPosts')
        .that.is.an('object');
      expect(result.formPlacement.belowPosts).to.have.property('enabled', true);
      expect(result.formPlacement.belowPosts)
        .to.have.property('posts')
        .that.is.an('object')
        .that.have.property('all', false);
      expect(result.formPlacement.belowPosts)
        .to.have.property('pages')
        .that.is.an('object')
        .that.have.property('all', true);
      expect(result.formPlacement.belowPosts)
        .to.have.property('posts')
        .that.is.an('object')
        .that.have.property('selected')
        .that.deep.equal(['1']);
      expect(result.formPlacement.belowPosts)
        .to.have.property('pages')
        .that.is.an('object')
        .that.have.property('selected')
        .that.deep.equal([]);
    });

    it('Sets default form styles', () => {
      expect(map(data).settings).to.have.property('formPadding', 20);
      expect(map(data).settings).to.have.property('inputPadding', 5);
      expect(map(data).settings).to.have.property('alignment', 'left');
      expect(map(data).settings).to.have.property('borderRadius', 0);
      expect(map(data).settings).to.have.property('borderSize', 0);
    });

    it('Maps form and input padding', () => {
      const mapData = {
        ...data,
        settings: {
          ...data.settings,
          form_padding: 50,
          input_padding: 20,
        },
      };
      expect(map(mapData).settings).to.have.property('formPadding', 50);
      expect(map(mapData).settings).to.have.property('inputPadding', 20);
    });

    it('Sets default placements styles', () => {
      expect(map(data).settings.formPlacement.belowPosts)
        .to.have.property('styles')
        .that.deep.eq({ width: { unit: 'percent', value: 100 } });
      expect(map(data).settings.formPlacement.popup)
        .to.have.property('styles')
        .that.deep.eq({ width: { unit: 'pixel', value: 560 } });
      expect(map(data).settings.formPlacement.fixedBar)
        .to.have.property('styles')
        .that.deep.eq({ width: { unit: 'percent', value: 100 } });
      expect(map(data).settings.formPlacement.slideIn)
        .to.have.property('styles')
        .that.deep.eq({ width: { unit: 'pixel', value: 560 } });
      expect(map(data).settings.formPlacement.others)
        .to.have.property('styles')
        .that.deep.eq({ width: { unit: 'percent', value: 100 } });
    });

    it('Sets default delays and positions', () => {
      expect(map(data).settings.formPlacement.popup)
        .to.have.property('delay')
        .eq(15);
      expect(map(data).settings.formPlacement.fixedBar)
        .to.have.property('delay')
        .eq(15);
      expect(map(data).settings.formPlacement.slideIn)
        .to.have.property('delay')
        .eq(15);
      expect(map(data).settings.formPlacement.slideIn)
        .to.have.property('position')
        .eq('right');
      expect(map(data).settings.formPlacement.fixedBar)
        .to.have.property('position')
        .eq('top');
    });

    it('Keeps set placement styles', () => {
      const mapData = {
        ...data,
        settings: {
          ...data.settings,
          form_placement: {
            below_posts: { styles: { width: { unit: 'percent', value: 101 } } },
            popup: { styles: { width: { unit: 'percent', value: 102 } } },
            fixed_bar: { styles: { width: { unit: 'percent', value: 103 } } },
            slide_in: { styles: { width: { unit: 'percent', value: 104 } } },
            others: { styles: { width: { unit: 'percent', value: 105 } } },
          },
        },
      };
      expect(map(mapData).settings.formPlacement.belowPosts)
        .to.have.property('styles')
        .that.deep.eq({ width: { unit: 'percent', value: 101 } });
      expect(map(mapData).settings.formPlacement.popup)
        .to.have.property('styles')
        .that.deep.eq({ width: { unit: 'percent', value: 102 } });
      expect(map(mapData).settings.formPlacement.fixedBar)
        .to.have.property('styles')
        .that.deep.eq({ width: { unit: 'percent', value: 103 } });
      expect(map(mapData).settings.formPlacement.slideIn)
        .to.have.property('styles')
        .that.deep.eq({ width: { unit: 'percent', value: 104 } });
      expect(map(mapData).settings.formPlacement.others)
        .to.have.property('styles')
        .that.deep.eq({ width: { unit: 'percent', value: 105 } });
    });

    it('Maps animation', () => {
      expect(map(data).settings.formPlacement.fixedBar)
        .to.have.property('animation')
        .that.eq('slideright');
      expect(map(data).settings.formPlacement.popup)
        .to.have.property('animation')
        .that.eq('slideup'); // default
      expect(map(data).settings.formPlacement.slideIn)
        .to.have.property('animation')
        .that.eq('flip');
      expect(map(data).settings.formPlacement.belowPosts).to.not.have.property(
        'animation',
      );
    });

    it('It ensures fontSize is an integer', () => {
      const mapData = {
        ...data,
        settings: {
          ...data.settings,
          fontSize: '23',
        },
      };
      expect(map(mapData).settings.fontSize).to.equal(23);

      const mapData2 = {
        ...data,
        settings: {
          ...data.settings,
          fontSize: undefined,
        },
      };
      expect(map(mapData2).settings.fontSize).to.be.equal(undefined);

      const mapData3 = {
        ...data,
        settings: {
          ...data.settings,
          fontSize: 13,
        },
      };
      expect(map(mapData3).settings.fontSize).to.equal(13);

      const mapData4 = {
        ...data,
        settings: {
          ...data.settings,
          fontSize: 13.5,
        },
      };
      expect(map(mapData4).settings.fontSize).to.equal(13);

      mapData.settings.fontSize = 'hello';
      expect(map(mapData).settings.fontSize).to.be.equal(undefined);
    });
  });
});
