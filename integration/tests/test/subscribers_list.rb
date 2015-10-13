require_relative '../_init'

describe 'subscribers list' do
  before do
    Admin::login
    click_on('Subscribers')

    3.times do |n|
      page.must_have_content 'New'
      within '#subscribers' do
        click_on 'New'
        fill_in('E-mail', with: "test#{n}@mailpoet.com")
        fill_in('Firstname', with: "First #{n}")
        fill_in('Lastname', with: "Last #{n}")
        select('Unconfirmed', from: 'field_status')
        click_on 'Save'
      end
      click_on('Subscribers')
    end
  end

  it 'shows all subscribers' do
    3.times do |n|
      page.must_have_content "test#{n}@mailpoet.com"
    end
  end

  it 'has a counter' do
    page.must_have_content '3 item(s)'
  end

  after do
    3.times do |n|
      page.must_have_content "test#{n}@mailpoet.com"
      find('a', text: "test#{n}@mailpoet.com").hover
      find('a', text: 'Trash').click
    end
    Admin::logout
  end
end
