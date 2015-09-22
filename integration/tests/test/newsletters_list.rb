require_relative '../_init'

describe 'newsletters list' do
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


    within '#newsletters' do
      click_on 'New'
      fill_in('Subject', with: "3 newsletter")
      click_on 'Save'
    end
    click_on('Newsletters')
  end

  it 'shows all newsletters' do
    page.must_have_content '1 newsletter'
    page.must_have_content '2 newsletter'
    page.must_have_content '3 newsletter'
  end

  it 'has a counter' do
    page.must_have_content '3 item(s)'
  end

  after do
    find('a', text: '1 newsletter').hover
    find('a', text: 'Trash').click

    find('a', text: '2 newsletter').hover
    find('a', text: 'Trash').click

    find('a', text: '3 newsletter').hover
    find('a', text: 'Trash').click

    Admin::logout
  end
end
