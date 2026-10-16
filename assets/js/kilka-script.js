(function ($) {
    "use strict";

    $.fn.kilkaAccessibleDropDown = function () {
        var el = $(this);

        $("a", el).focus(function () {
            $(this).parents("li").addClass("hover");
        }).blur(function () {
            $(this).parents("li").removeClass("hover");
        });
    };

    $(".menu-close").on("click", function () {
        $("a.slicknav_btn").removeClass("slicknav_open");
        $(".slicknav_nav").css("display", "none");
    });

    $(document).ready(function () {
        // Adapt audited legacy colors without removing their authored hue.
        // Keep original ink as the light/print fallback; CSS handles scheme changes.
        var legacyInkTokens = {
            "black": "ink",
            "rgb(0, 0, 0)": "ink",
            "rgb(28, 28, 28)": "ink",
            "rgb(51, 51, 51)": "ink",
            "darkslategray": "teal",
            "darkslategrey": "teal",
            "rgb(47, 79, 79)": "teal",
            "rgb(17, 85, 204)": "blue",
            "rgb(34, 111, 40)": "green",
            "red": "red",
            "rgb(255, 0, 0)": "red"
        };
        $("article.type-post .entry-content [style]").each(function () {
            var ink = this.style.getPropertyValue("color");
            var token = legacyInkTokens[ink];
            if (token) {
                this.style.setProperty(
                    "color",
                    "var(--kilka-legacy-" + token + ", " + ink + ")",
                    this.style.getPropertyPriority("color")
                );
            }
        });

        // Keep table semantics intact: scroll a wrapper, not a display:block table.
        var tableScrollers = [];
        $(".kilka-main-blog-context .entry-content table").each(function () {
            var table = this;
            var scroller = table.parentElement;
            if (!scroller.classList.contains("wp-block-table")) {
                scroller = document.createElement("div");
                scroller.className = "kilka-table-scroll";
                table.before(scroller);
                scroller.appendChild(table);
            }
            var originalTabindex = scroller.getAttribute("tabindex");
            var updateTableScroll = function () {
                if (scroller.scrollWidth > scroller.clientWidth + 1) {
                    if (originalTabindex === null) scroller.setAttribute("tabindex", "0");
                } else if (originalTabindex === null) {
                    scroller.removeAttribute("tabindex");
                }
            };
            tableScrollers.push(updateTableScroll);
            if (window.ResizeObserver) {
                var observer = new ResizeObserver(updateTableScroll);
                observer.observe(scroller);
                observer.observe(table);
            }
            updateTableScroll();
        });
        if (tableScrollers.length && !window.ResizeObserver) {
            $(window).on("resize.kilkaTables load.kilkaTables", function () {
                tableScrollers.forEach(function (update) { update(); });
            });

        }
        $(".kilka-main-blog-context .entry-content").addClass("kilka-tables-ready");

        var $primaryMenu = $("#primary-menu");
        var $responsiveMenu = $(".kilka-responsive-menu");
        var $backToTop = $(".back-to-top");
        var $exhibition = $(".kilka-exhibition");
        var menuLabel = $responsiveMenu.data("menu-label") || "Menu";

        var menuMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        $primaryMenu.kilkaAccessibleDropDown();
        $primaryMenu.slicknav({
            duration: menuMotion.matches ? 0 : 200,
            allowParentLinks: true,
            label: menuLabel,
            prependTo: ".kilka-responsive-menu",
            nestedParentLinks: false,
            closeOnClick: true
        });

        var $menuButton = $responsiveMenu.find(".slicknav_btn");
        $menuButton.attr("aria-label", menuLabel);
        if ($menuButton.length) {
            $primaryMenu.closest(".mainmenu-area").addClass("kilka-menu-enhanced");
        }
        var updateMenuMotion = function () {
            var menu = $primaryMenu.data("plugin_slicknav");
            if (menu) menu.settings.duration = menuMotion.matches ? 0 : 200;
            if (menuMotion.matches) $responsiveMenu.find("ul").finish();
        };
        if (menuMotion.addEventListener) menuMotion.addEventListener("change", updateMenuMotion);
        else if (menuMotion.addListener) menuMotion.addListener(updateMenuMotion);
        // Handle Escape before SlickNav's own link handler to avoid toggling twice.
        document.addEventListener("keydown", function (event) {
            if (event.key !== "Escape" || !$menuButton.hasClass("slicknav_open") ||
                !$responsiveMenu.get(0) || !$responsiveMenu.get(0).contains(event.target)) return;
            event.preventDefault();
            event.stopImmediatePropagation();
            $primaryMenu.slicknav("close");
            $menuButton.trigger("focus");
        }, true);

        var menuUtilitiesTemplate = $responsiveMenu.find(".kilka-menu-utilities-template").get(0);
        var colorSchemeTemplate = $responsiveMenu.find(".kilka-color-scheme-template").get(0);
        var colorSchemeStorageKey = "kilka-color-scheme";
        var colorSchemeMedia = window.matchMedia ? window.matchMedia("(prefers-color-scheme: dark)") : null;
        var isExhibitionContext = document.documentElement.getAttribute("data-color-scheme-context") === "exhibition";

        if (menuUtilitiesTemplate && menuUtilitiesTemplate.content) {
            $responsiveMenu.find(".slicknav_nav").first().append(menuUtilitiesTemplate.content.cloneNode(true));
            menuUtilitiesTemplate.remove();
        }

        if (colorSchemeTemplate && colorSchemeTemplate.content) {
            $responsiveMenu.find(".slicknav_nav").first().append(colorSchemeTemplate.content.cloneNode(true));
            colorSchemeTemplate.remove();
        }

        var $colorSchemeButtons = $responsiveMenu.find("[data-color-scheme-option]");

        var getColorSchemePreference = function () {
            var preference = document.documentElement.getAttribute("data-color-scheme-preference");

            return preference === "light" || preference === "dark" ? preference : "auto";
        };

        var resolveColorScheme = function (preference) {
            if (isExhibitionContext) {
                return "light";
            }

            if (preference !== "auto") {
                return preference;
            }

            return colorSchemeMedia && colorSchemeMedia.matches ? "dark" : "light";
        };

        var applyColorScheme = function (preference, persist) {
            if (preference !== "light" && preference !== "dark") {
                preference = "auto";
            }

            var resolvedScheme = resolveColorScheme(preference);

            document.documentElement.setAttribute("data-color-scheme-preference", preference);
            document.documentElement.setAttribute("data-color-scheme", resolvedScheme);
            document.documentElement.style.colorScheme = resolvedScheme;
            $colorSchemeButtons.attr("aria-pressed", "false");
            $colorSchemeButtons.filter('[data-color-scheme-option="' + preference + '"]').attr("aria-pressed", "true");

            if (persist) {
                try {
                    if (preference === "auto") {
                        window.localStorage.removeItem(colorSchemeStorageKey);
                    } else {
                        window.localStorage.setItem(colorSchemeStorageKey, preference);
                    }
                } catch (error) {
                    // The selected scheme still applies for this page when storage is unavailable.
                }
            }
        };

        if ($colorSchemeButtons.length) {
            applyColorScheme(getColorSchemePreference(), false);

            $colorSchemeButtons.on("click", function () {
                applyColorScheme($(this).data("color-scheme-option"), true);
            });
        }

        if (colorSchemeMedia) {
            var handleSystemColorSchemeChange = function () {
                if (getColorSchemePreference() === "auto") {
                    applyColorScheme("auto", false);
                }
            };

            if (colorSchemeMedia.addEventListener) {
                colorSchemeMedia.addEventListener("change", handleSystemColorSchemeChange);
            } else if (colorSchemeMedia.addListener) {
                colorSchemeMedia.addListener(handleSystemColorSchemeChange);
            }
        }

        $(window).on("storage.kilkaColorScheme", function (event) {
            var originalEvent = event.originalEvent;

            if (originalEvent && originalEvent.key === colorSchemeStorageKey) {
                applyColorScheme(originalEvent.newValue || "auto", false);
            }
        });

        if ($backToTop.length) {
            var getScrollTop = function () {
                return window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
            };

            var getBackToTopThreshold = function () {
                return window.matchMedia && window.matchMedia("(max-width: 767px)").matches ? 200 : 400;
            };

            var updateBackToTop = function () {
                $backToTop.toggleClass("is-visible", getScrollTop() > getBackToTopThreshold());
            };

            updateBackToTop();
            $(window).on("scroll.kilkaBackToTop resize.kilkaBackToTop pageshow.kilkaBackToTop", updateBackToTop);
            $(document).on("scroll.kilkaBackToTop touchmove.kilkaBackToTop", updateBackToTop);

            $backToTop.on("click", function () {
                var reduceMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

                if (!reduceMotion && "scrollBehavior" in document.documentElement.style) {
                    window.scrollTo({
                        top: 0,
                        behavior: "smooth"
                    });
                } else {
                    window.scrollTo(0, 0);
                }
            });
        }

        if ($exhibition.length) {
            var $information = $exhibition.find(".kilka-exhibition__information");
            var $informationToggle = $exhibition.find(".kilka-exhibition__information-toggle");
            var $informationClose = $exhibition.find(".kilka-exhibition__information-close");
            var lastFocusedElement = null;

            var closeInformation = function () {
                $information.attr("hidden", "hidden");
                $informationToggle.attr("aria-expanded", "false");
                $("body").removeClass("kilka-exhibition-information-open");
                $(document).off("keydown.kilkaExhibitionInformation");

                if (lastFocusedElement) {
                    lastFocusedElement.focus();
                }
            };

            var openInformation = function (event) {
                event.preventDefault();
                lastFocusedElement = document.activeElement;
                $information.removeAttr("hidden");
                $informationToggle.attr("aria-expanded", "true");
                $("body").addClass("kilka-exhibition-information-open");
                $informationClose.focus();

                $(document).on("keydown.kilkaExhibitionInformation", function (event) {
                    var $focusableElements;

                    if (event.key === "Escape") {
                        event.preventDefault();
                        closeInformation();
                    } else if (event.key === "Tab") {
                        $focusableElements = $information.find("a[href], button:not([disabled])").filter(":visible");

                        if (event.shiftKey && document.activeElement === $focusableElements.first()[0]) {
                            event.preventDefault();
                            $focusableElements.last().focus();
                        } else if (!event.shiftKey && document.activeElement === $focusableElements.last()[0]) {
                            event.preventDefault();
                            $focusableElements.first().focus();
                        }
                    }
                });
            };

            $informationToggle.on("click", openInformation);
            $informationClose.on("click", closeInformation);
            $information.on("click", function (event) {
                if (event.target === this) {
                    closeInformation();
                }
            });
            // Keep information readable if this enhancement never runs.
            if ($information.length) {
                $information.attr({ "hidden": "hidden", "role": "dialog", "aria-modal": "true" })
                    .addClass("is-enhanced");
                $informationClose.removeAttr("hidden");
                $informationToggle.attr({ "aria-expanded": "false", "role": "button" });
                $informationToggle.on("keydown", function (event) {
                    if (event.key === " ") openInformation(event);
                });
            }
        }
    });
}(jQuery));
