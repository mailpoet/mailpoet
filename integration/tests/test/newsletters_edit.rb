require_relative '../_init'

describe 'newsletters edit' do
  before do
    Admin::login
    click_on('Newsletters')

    within '#newsletters' do
      click_on 'New'
      fill_in('Subject', with: "1 newsletter")
      click_on 'Save'
    end
    page.must_have_content 'Newsletter succesfully added!'
    page.must_have_content '1 newsletter'
  end

  it 'can edit a newsletter' do
    find('#newsletters a', text: '1 newsletter').hover
    find('#newsletters a', text: 'Edit').click
    fill_in('Subject', with: "1 newsletter edit")
    click_on 'Save'
    page.must_have_content 'Newsletter succesfully updated!'
  end

  after do
    click_on('Newsletters')

    find('a', text: '1 newsletter').hover
    find('a', text: 'Trash').click

    Admin::logout
  end
end
