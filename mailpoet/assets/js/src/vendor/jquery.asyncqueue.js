/*
* This file is part of the jquery plugin "asyncQueue".
*
* (c) Sebastien Roch <roch.sebastien@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
(function($){
    $.AsyncQueue = function() {
        var that = this,
            queue = [],
            failureFunc,
            completeFunc,
            paused = false,
            lastCallbackData,
            _run;

        _run = function() {
            var f = queue.shift();

            if (f) {
                f.apply(that, [that]);
                if (paused === false) {
                    _run();
                }
            } else {
                if(completeFunc){
                    completeFunc.apply(that);
                }
            }
        }

        this.onFailure = function(func) {
            failureFunc = func;
        }

        this.onComplete = function(func) {
            completeFunc = func;
        }

        this.add = function(func) {
            queue.push(func);
            return this;
        }

        this.storeData = function(dataObject) {
            lastCallbackData = dataObject;
            return this;
        }

        this.lastCallbackData = function () {
            return lastCallbackData;
        }

        this.run = function() {
            paused = false;
            _run();
        }

        this.pause = function () {
            paused = true;
            return this;
        }

        this.failure = function() {
            paused = true;
            if (failureFunc) {
                var args = [that];
                for(i = 0; i < arguments.length; i++) {
                    args.push(arguments[i]);
                }
                failureFunc.apply(that, args);
            }
        }

        return this;
    }
})(jQuery);
