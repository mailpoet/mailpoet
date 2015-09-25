require 'rubygems'
require 'bundler'
require 'minitest/autorun'
require 'minitest/pride'
require 'minitest/spec'
require 'capybara'
require 'capybara/dsl'
require 'capybara/poltergeist'
require 'capybara_minitest_spec'

Bundler.require(:app)

Capybara.configure do |config|
  config.default_driver = :poltergeist
  config.run_server = false
  config.app_host = ENV['URL']
end

Capybara.register_driver :poltergeist do |app|
  Capybara::Poltergeist::Driver.new(app, {
    js_errors: true,
    phantomjs_options: [
      '--ignore-ssl-errors=yes',
      '--ssl-protocol=any'
    ]
  })
end

class MiniTest::Spec
  include Capybara::DSL
end

module Admin
  extend Capybara::DSL

  def self.login
    visit '/wp-admin'
    fill_in('Username', with: ENV['USER'])
    fill_in('Password', with: ENV['PASSWORD'])
    click_button('Log In')
    find('#toplevel_page_mailpoet').hover
    find('#toplevel_page_mailpoet .wp-first-item a').click
  end

  def self.logout
    find('#wp-admin-bar-my-account').hover
    find('#wp-admin-bar-logout a').click
  end
end
