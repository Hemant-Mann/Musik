(function (window) {

    var Model = (function () {
        function Model(opts) {
            this.api = window.location.origin + '/';
            this.ext = '.json';
        }

        Model.prototype = {
            create: function (opts) {
                var self = this,
                        link = this._clean(this.api) + this._clean(opts.action) + this._clean(this.ext);
                $.ajax({
                    url: link,
                    type: 'POST',
                    data: opts.data,
                }).done(function (data) {
                    if (opts.callback) {
                        opts.callback.call(self, data);
                    }
                }).fail(function () {
                    console.log("error");
                }).always(function () {
                    //console.log("complete");
                });
            },
            read: function (opts) {
                var self = this,
                        link = this._clean(this.api) + this._clean(opts.action) + this._clean(this.ext);
                $.ajax({
                    url: link,
                    type: 'GET',
                    data: opts.data,
                }).done(function (data) {
                    if (opts.callback) {
                        opts.callback.call(self, data);
                    }
                }).fail(function () {
                    console.log("error");
                }).always(function () {
                    //console.log("complete");
                });

            },
            _clean: function (entity) {
                return entity || "";
            }
        };
        return Model;
    }());

    Model.initialize = function (opts) {
        return new Model(opts);
    };

    window.Model = Model;
}(window));