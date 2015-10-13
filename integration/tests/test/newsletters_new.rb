require_relative '../_init'

describe 'newsletters new' do
  before do
    Admin::login
    click_on('Newsletters')
  end

  it 'can create a newsletter' do
    within '#newsletters' do
      click_on 'New'
      fill_in('Subject', with: "1 newsletter")
      click_on 'Save'
    end
    page.must_have_content 'Newsletter succesfully added!'
    page.must_have_content '1 newsletter'
  end

  after do
    click_on('Newsletters')

    find('a', text: '1 newsletter').hover
    find('a', text: 'Trash').click

    Admin::logout
  end
end
