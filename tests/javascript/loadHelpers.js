var fs = require('fs');
module.exports = {
  loadFileToContainer: function (path, window, containerTagName, opts) {
    var contents = fs.readFileSync(path),
      container = window.document.createElement(containerTagName);
    var options = opts || {};
    container.innerHTML = contents;

    if (options.type) {
      container.type = options.type;
    }
    if (options.id) {
      container.id = options.id;
    }
    global.window.document.body.appendChild(container);
  },
  loadScript: function (scriptPath, window, options) {
    this.loadFileToContainer(scriptPath, window, 'script', options);
  },
  loadTemplate: function (path, window, opts) {
    var w = window || global.window;
    var options = opts || {};
    options.type = "text/x-handlebars-template";

    this.loadScript("views/newsletter/templates/" + path, w, options);
  }
};
