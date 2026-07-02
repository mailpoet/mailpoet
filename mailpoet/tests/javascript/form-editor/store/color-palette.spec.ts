import { getFormEditorColorPalette } from '../../../../assets/js/src/form-editor/store/color-palette';

describe('form editor color palette', () => {
  const defaultColors = [
    { name: 'Black', slug: 'black', color: '#000000' },
    { name: 'White', slug: 'white', color: '#ffffff' },
  ];

  it('uses default colors when no theme palette is available', () => {
    expect(getFormEditorColorPalette([], defaultColors)).to.deep.equal(
      defaultColors,
    );
  });

  it('keeps theme colors before defaults and avoids duplicate slugs', () => {
    const defaultColor = defaultColors[0];
    const themeColor = {
      ...defaultColor,
      color: '#123456',
    };
    const palette = getFormEditorColorPalette([themeColor], defaultColors);

    expect(palette[0]).to.deep.equal(themeColor);
    expect(
      palette.filter((color) => color.slug === defaultColor.slug),
    ).to.have.length(1);
    expect(palette).to.have.length(defaultColors.length);
  });
});
