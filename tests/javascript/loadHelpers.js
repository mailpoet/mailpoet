var fs = require('fs');
module.exports = {
  loadFileToContainer: function (path, window, containerTagName, options) {
    var contents = fs.readFileSync(path),
      container = window.document.createElement(containerTagName);
    options = options || {};
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
  loadTemplate: function (path, window, options) {
    var w = window || global.window;
    options = options || {};
    options.type = "text/x-handlebars-template";

    this.loadScript("views/newsletter/templates/" + path, w, options);
  }
};
