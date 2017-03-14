(function() {

  var root = (typeof exports == 'undefined' ? window : exports);

  var config = {
    // Ensure Content-Type is an image before trying to load @2x image
    // https://github.com/imulus/retinajs/pull/45)
    check_mime_type: true
  };



  root.Retina = Retina;

  function Retina() {}

  Retina.configure = function(options) {
    if (options == null) options = {};
    for (var prop in options) config[prop] = options[prop];
  };

  Retina.init = function(context) {
    if (context == null) context = root;

    var existing_onload = context.onload || new Function;

    context.onload = function() {
      var images = document.querySelectorAll(config.retinaImgTagSelector), 
          retinaImages = [], i, image, filter;

      if (typeof config.retinaImgFilterFunc === 'function') {
        filter = config.retinaImgFilterFunc;
        for (i = 0; i < images.length; i++) {
          image = images[i];
          if (filter(image)) {
            retinaImages.push(new RetinaImage(image));
          }
        }
      } else {
        for (i = 0; i < images.length; i++) {
          image = images[i];
          retinaImages.push(new RetinaImage(image));
        }
      }
      existing_onload();
    };
  };

  Retina.isRetina = function(){
    var mediaQuery = "(-webkit-min-device-pixel-ratio: 1.5),\
                      (min--moz-device-pixel-ratio: 1.5),\
                      (-o-min-device-pixel-ratio: 3/2),\
                      (min-resolution: 1.5dppx)";

    if (root.devicePixelRatio > 1)
      return true;

    if (root.matchMedia && root.matchMedia(mediaQuery).matches)
      return true;

    return false;
  };


  root.RetinaImagePath = RetinaImagePath;

  function RetinaImagePath(path) {
    this.path = path;
    this.at_2x_path = path.replace(/\.\w+(\?t=\d+)?$/, function(match) { return "@2x" + match; });
  }

  RetinaImagePath.confirmed_paths = [];

  RetinaImagePath.prototype.is_external = function() {
    return !!(this.path.match(/^https?\:/i) && !this.path.match('//' + document.domain) && !this.path.match(config.image_host) );
  };

  RetinaImagePath.prototype.check_2x_variant = function(callback) {
    var http, that = this;
    if (this.is_external()) {
      return callback(false);
    } else if (this.at_2x_path in RetinaImagePath.confirmed_paths) {
      return callback(true);
    } else {
      http = new XMLHttpRequest;
      http.cache = false;
      http.open('HEAD', this.at_2x_path);
      http.onreadystatechange = function() {
        if (http.readyState != 4) {
          return callback(false);
        }

        if (http.status >= 200 && http.status <= 399) {
          if (config.check_mime_type) {
            var type = http.getResponseHeader('Content-Type');
            if (type == null || !type.match(/^image/i)) {
              return callback(false);
            }
          }

          RetinaImagePath.confirmed_paths.push(that.at_2x_path);
          return callback(true);
        } else {
          return callback(false);
        }
      };
      http.send();
    }
  };

  function RetinaImage(el, force_load) {
    force_load = force_load || false;
    this.el = el;

    var src;
    if (this.el.hasAttribute('src') && this.el.hasAttribute('src') != null) {
      src = this.el.getAttribute('src');
    } else if (this.el.hasAttribute('data-src') && this.el.hasAttribute('src') == null) {
      src = this.el.getAttribute('data-src');
    } else {
        return;
    }

    if (/@2x\./.test(src)) {
      return;
    }

    this.path = new RetinaImagePath(src);
    var that = this;
    this.path.check_2x_variant(function(hasVariant) {
      if (hasVariant) that.swap(force_load);
    });
  }

  root.RetinaImage = RetinaImage;

  RetinaImage.prototype.swap = function(force_load) {
    var that = this, hidden = false, width, height, path = this.path.at_2x_path;

    function load() {
      if (! that.el.complete) {
        setTimeout(load, 5);
      } else {
        if (that.el.getAttribute('data-width') > 0 && that.el.getAttribute('data-height') > 0) {
          width = that.el.getAttribute('data-width');
          height = that.el.getAttribute('data-height');
        } else if (that.el.offsetWidth == 0 && that.el.offsetHeight == 0) {
          width = that.el.naturalWidth;
          height = that.el.naturalHeight;
        } else {
          width = that.el.offsetWidth;
          height = that.el.offsetHeight;
        }

        if (!width || width == "0") {
          // try to get width from the image attribute
          width = that.el.getAttribute('width');
          if (!width || width == "0") {
            hidden = true;
          }
        }

        if (!height || height == "0") {
          // try to get width from the image attribute
          height = that.el.getAttribute('height');
          if (!height || height == "0") {
            hidden = true;
          }
        }

        if (force_load || !hidden) {
          that.el.setAttribute('width', width);
          that.el.setAttribute('height', height);
          if (that.el.hasAttribute('src')) {
            that.el.setAttribute('src', path);
          } else if (that.el.hasAttribute('data-src')) {
            that.el.setAttribute('data-src', path);
          }
        }
      }
    }
    load();
  };

  if (Retina.isRetina()) {
    Retina.init(root);
  }

})();
