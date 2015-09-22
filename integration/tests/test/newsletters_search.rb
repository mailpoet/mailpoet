require_relative '../_init'

describe 'newsletters search' do
  before do
    Admin::login
    click_on('Newsletters')

    within '#newsletters' do
      click_on 'New'
      fill_in('Subject', with: "1 newsletter")
      click_on 'Save'
    end
    click_on('Newsletters')

    within '#newsletters' do
      click_on 'New'
      fill_in('Subject', with: "2 newsletter")
      click_on 'Save'
    end
    click_on('Newsletters')
  end

  it 'shows all newsletters' do
    page.must_have_content '1 newsletter'
    page.must_have_content '2 newsletter'
  end

  it 'can search for a newsletter' do
    fill_in('search_input', with: '1')
    click_button 'Search'
    page.must_have_content '1 newsletter'
    page.wont_have_content '2 newsletter'
  end

  after do
    click_on('Newsletters')

    find('a', text: '1 newsletter').hover
    find('a', text: 'Trash').click

    find('a', text: '2 newsletter').hover
    find('a', text: 'Trash').click

    Admin::logout
  end
end
