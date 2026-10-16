(function () {
    "use strict";

    var root = document.documentElement;
    var origin = window.location.origin;

    // The stylesheet is only printed in WordPress's embed template.
    if (document.getElementById("kilka-embed-colors-css")) {
        window.addEventListener("message", function (event) {
            var data = event.data;
            if (event.source !== window.parent || event.origin !== origin ||
                !data || data.type !== "kilka-embed-scheme" ||
                (data.scheme !== "light" && data.scheme !== "dark")) {
                return;
            }
            root.setAttribute("data-kilka-embed-scheme", data.scheme);
        });
        if (window.parent !== window) {
            window.parent.postMessage({ type: "kilka-embed-ready" }, origin);
        }
        return;
    }

    function ownEmbeds() {
        return Array.prototype.filter.call(
            document.querySelectorAll(".entry-content iframe.wp-embedded-content"),
            function (frame) {
                try {
                    return new URL(frame.src, document.baseURI).origin === origin;
                } catch (error) {
                    return false;
                }
            }
        );
    }

    function sendScheme(frame) {
        if (frame.contentWindow) {
            // WordPress's sandbox gives the child an opaque origin. Only the
            // public scheme is sent, after checking its configured source URL.
            frame.contentWindow.postMessage({
                type: "kilka-embed-scheme",
                scheme: root.getAttribute("data-color-scheme") === "dark" ? "dark" : "light"
            }, "*");
        }
    }

    // A ready message covers both cached frames and frames loaded before this
    // script. source + configured URL identify our opaque-origin child safely.
    window.addEventListener("message", function (event) {
        if (!event.data || event.data.type !== "kilka-embed-ready") return;
        ownEmbeds().forEach(function (frame) {
            if (event.source === frame.contentWindow) sendScheme(frame);
        });
    });
    document.addEventListener("load", function (event) {
        if (ownEmbeds().indexOf(event.target) !== -1) sendScheme(event.target);
    }, true);
    new MutationObserver(function () {
        ownEmbeds().forEach(sendScheme);
    }).observe(root, { attributes: true, attributeFilter: ["data-color-scheme"] });
    ownEmbeds().forEach(sendScheme);
}());
