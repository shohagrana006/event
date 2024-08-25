!(function (t) {
  "object" == typeof exports && "undefined" != typeof module
    ? (module.exports = t())
    : "function" == typeof define && define.amd
    ? define([], t)
    : (("undefined" != typeof window
        ? window
        : "undefined" != typeof global
        ? global
        : "undefined" != typeof self
        ? self
        : this
      ).tracardi = t());
})(function () {
  return (function r(o, i, a) {
    function c(e, t) {
      if (!i[e]) {
        if (!o[e]) {
          var n = "function" == typeof require && require;
          if (!t && n) return n(e, !0);
          if (d) return d(e, !0);
          throw (
            (((n = new Error("Cannot find module '" + e + "'")).code =
              "MODULE_NOT_FOUND"),
            n)
          );
        }
        (n = i[e] = { exports: {} }),
          o[e][0].call(
            n.exports,
            function (t) {
              return c(o[e][1][t] || t);
            },
            n,
            n.exports,
            r,
            o,
            i,
            a
          );
      }
      return i[e].exports;
    }
    for (
      var d = "function" == typeof require && require, t = 0;
      t < a.length;
      t++
    )
      c(a[t]);
    return c;
  })(
    {
      1: [
        function (t, e, n) {
          "use strict";
          !(function (t) {
            t = t || window;
            var n = [],
              r = !1,
              o = !1;
            function i() {
              if (!r) {
                r = !0;
                for (var t = 0; t < n.length; t++)
                  n[t].fn.call(window, n[t].ctx);
                n = [];
              }
            }
            function a() {
              "complete" === document.readyState && i();
            }
            t.documentReady = function (t, e) {
              if ("function" != typeof t)
                throw new TypeError(
                  "callback for documentReady(fn) must be a function"
                );
              r
                ? setTimeout(function () {
                    t(e);
                  }, 1)
                : (n.push({ fn: t, ctx: e }),
                  "complete" === document.readyState ||
                  (!document.attachEvent &&
                    "interactive" === document.readyState)
                    ? setTimeout(i, 1)
                    : o ||
                      (document.addEventListener
                        ? (document.addEventListener("DOMContentLoaded", i, !1),
                          window.addEventListener("load", i, !1))
                        : (document.attachEvent("onreadystatechange", a),
                          window.attachEvent("onload", i)),
                      (o = !0)));
            };
          })(window);
          var r = [];
          window.tracker || (window.tracker = {}),
            window.response || (window.response = { context: {} }),
            (window.onTracardiReady = {
              bind: function (t) {
                "function" == typeof t && r.push(t);
              },
              call: function (e) {
                r.forEach(function (t) {
                  t(e);
                });
              },
            }),
            (function () {
              for (
                var n = [], r = "liliput.min.js", t = ["track"], e = 0;
                e < t.length;
                e++
              ) {
                var o = t[e];
                window.tracker[o] = (function (e) {
                  return function () {
                    var t = Array.prototype.slice.call(arguments);
                    return t.unshift(e), n.push(t), window.tracker;
                  };
                })(o);
              }
              function i() {
                if (
                  (console.debug("[Loader] Rerun callbacks."),
                  void 0 !== window.tracardi.default)
                )
                  if (
                    window.tracardi.default.getState().plugins.tracardi
                      .initialized
                  )
                    for (
                      window.tracker = window.tracardi.default;
                      0 < n.length;

                    ) {
                      var t = n.shift(),
                        e = t.shift();
                      tracker[e] && tracker[e].apply(tracker, t);
                    }
                  else
                    console.error(
                      "[Loader] Callbacks stopped. Tracker not initialized."
                    );
                else
                  console.error(
                    "[Loader] Callbacks stopped. Tracker not initialized. Is script url correct?"
                  );
              }
              documentReady(function () {
                var t, e, n;
                "1" !== navigator.doNotTrack ||
                !0 !==
                  (null === (t = options) ||
                  void 0 === t ||
                  null === (e = t.tracker) ||
                  void 0 === e ||
                  null === (n = e.settings) ||
                  void 0 === n
                    ? void 0
                    : n.respectDoNotTrack)
                  ? (((e = document.createElement("script")).type =
                      "text/javascript"),
                    (e.async = !0),
                    void 0 !== options.tracker ||
                    void 0 !== options.tracker.url ||
                    void 0 !== options.tracker.url.script
                      ? (null !== options.tracker.url.script
                          ? options.tracker.url.script.startsWith("http") ||
                            options.tracker.url.script.startsWith("//")
                            ? (e.src = options.tracker.url.script + "/" + r)
                            : (e.src = options.tracker.url.script)
                          : (e.src = r),
                        console.debug("[Loader] Loading: " + e.src),
                        e.addEventListener
                          ? e.addEventListener(
                              "load",
                              function (t) {
                                i();
                              },
                              !1
                            )
                          : (e.onreadystatechange = function () {
                              ("complete" !== this.readyState &&
                                "loaded" !== this.readyState) ||
                                i(window.event);
                            }),
                        (n =
                          document.getElementsByTagName(
                            "script"
                          )[0]).parentNode.insertBefore(e, n))
                      : console.error(
                          "[Loader] Undefined options.tracker.url.script. This url defines location of tracker code."
                        ))
                  : console.log(
                      "We are respecting do not track setting. Tracardi disabled."
                    );
              });
            })();
        },
        {},
      ],
    },
    {},
    [1]
  )(1);
});

var options = {
  tracker: {
    url: {
      // This is url to tracardi backend. Please mind the correct port.
      script: "https://stg-bdp-api.keos.co/tracker",
      api: "https://stg-bdp-api.keos.co",
    },
    source: {
      id: "0bfb6914-bd7a-4d46-a41a-8592e9ef3520",
    },
    // Please see the documentation for more settings: http://docs.tracardi.com/integration/js-integration/
  },
};
