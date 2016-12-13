#!/usr/bin/ruby
path = "/tmp"
Dir.mkdir(path) if !File.exists?(path)
File.open("#{path}/mailpoet-#{Time.now.to_f}.txt", "w") do |f|
    sleep 5
    f.puts ARGV.inspect
    $stdin.each_line { |line| f.puts line }
end
