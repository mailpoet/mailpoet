require_relative '../_init'

describe 'newsletters order subject' do
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

  it 'shows all newsletters ordered by creation' do
    page.must_have_content '3 newsletter'
    page.must_have_content '2 newsletter'
    page.must_have_content '1 newsletter'

    page_order = all('table tbody tr').map do |row|
      row.first('a').text
    end

    correct_order = [
      '3 newsletter',
      '2 newsletter',
      '1 newsletter'
    ]

    page_order.must_equal correct_order
  end

  it 'can order newsletters by asc or desc subject' do
    page.must_have_content '3 newsletter'
    page.must_have_content '2 newsletter'
    page.must_have_content '1 newsletter'

    first('a', text: 'Subject').click
    sleep 1

    page_order = all('table tbody tr').map do |row|
      row.first('a').text
    end

    correct_order = [
      '1 newsletter',
      '2 newsletter',
      '3 newsletter'
    ]

    page_order.must_equal correct_order

    first('a', text: 'Subject').click
    sleep 1

    page_order = all('table tbody tr').map do |row|
      row.first('a').text
    end

    correct_order = [
      '3 newsletter',
      '2 newsletter',
      '1 newsletter'
    ]

    page_order.must_equal correct_order
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
