require_relative '../_init'

describe 'newsletters blank list' do
  before do
    Admin::login
    click_on('Newsletters')
  end

  it 'has a title' do
    page.must_have_content 'Newsletters'
  end

  it 'has a new button' do
    page.must_have_content 'New'
  end

  it 'has an all counter' do
    page.must_have_content 'All (0)'
  end

  it 'has a search form' do
    page.must_have_content 'Search'
  end

  it 'has the table columns' do
    page.must_have_content 'Subject'
    page.must_have_content 'Created on'
    page.must_have_content 'Last modified on'
  end

  it 'shows a message if there are no newsletters' do
    page.must_have_content 'No newsletters found'
  end

  after do
    Admin::logout
  end
end
