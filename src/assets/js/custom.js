var Pixio = (function () {
    "use strict";
    var e = $(window).width();

    // Defined functions
    var i = function () {
        var e = jQuery("#quik-search-btn"),
            t = jQuery("#quik-search-remove");
        e.on("click", function () {
            jQuery(".dz-quik-search").fadeIn(500).addClass("On");
        });
        t.on("click", function () {
            jQuery(".dz-quik-search").fadeOut(500).removeClass("On");
        });
    };

    var n = function () {
        if ($(".wow").length > 0) {
            var e = new WOW({ boxClass: "wow", animateClass: "animated", offset: 50, mobile: !1 });
            setTimeout(function () { e.init(); }, 120);
        }
    };

    var o = function () {
        var e = parseInt($(".onepage").css("height"), 10);
        $(".scroll").unbind().on("click", function (t) {
            t.preventDefault();
            if (this.hash !== "") {
                var i = $(this.hash).offset().top,
                    n = parseInt($(".onepage").css("height"), 10);
                $("body").scrollspy({ target: ".navbar", offset: n + 2 });
                $("html, body").animate({ scrollTop: i - n }, 800);
            }
        });
        $("body").scrollspy({ target: ".navbar", offset: e + 2 });
    };

    var a = function () {
        if (e <= 991) {
            var t;
            function i(e, t) {
                t.parent("li").has("ul").length > 0 && e.preventDefault(),
                t.parent().hasClass("open")
                    ? t.parent().removeClass("open")
                    : (t.hasClass("sub-menu") || t.parent().parent().find("li").removeClass("open"),
                      t.parent().addClass("open"));
            }
            jQuery(".navbar-nav > li > a, .sub-menu > li > a, .navbar-nav > li > a > i, .sub-menu > li > a > i")
                .unbind()
                .on({
                    click: function (e) { t = jQuery(this), i(e, t); },
                    keypress: function (e) {
                        if ("Enter" !== e.key) return !1;
                        t = jQuery(this), i(e, t);
                    },
                });
            jQuery(".tabindex").attr("tabindex", "0");
        } else {
            jQuery(".tabindex").removeAttr("tabindex");
        }
        jQuery(".menu-btn, .openbtn").on("click", function () {
            jQuery(".contact-sidebar").addClass("active");
        });
        jQuery(".menu-close").on("click", function () {
            jQuery(".contact-sidebar").removeClass("active"), jQuery(".menu-btn").removeClass("open");
        });
        jQuery(".dz-carticon").on("click", function () { jQuery(this).toggleClass("active"); });
        jQuery(".dz-wishicon").on("click", function () { jQuery(this).toggleClass("active"); });
        $(".mega-menu").each(function () {
            $(this).hasClass("menu-center") && $(this).parent().addClass("menu-relative");
        });
    };

    // Return only the functions we have defined
    return {
        init: function () {
            i(); n(); o(); a();
            jQuery(".modal").on("show.bs.modal", function(){}); // placeholder
        },
        load: function () { 
            // placeholder if you have functions f(), d(), B(), u()
        },
        scroll: function () { 
            // placeholder if you have X()
        },
        resize: function () { 
            e = $(window).width(); a(); 
            // placeholder for B(), D() if needed
        },
    };
})();

jQuery(document).ready(function () {
    Pixio.init();

    jQuery("#loading-area").fadeOut(300, function() {
        jQuery(this).remove();
    });

    $('a[data-bs-toggle="tab"]').click(function () {
        $($(this).attr("href")).show().addClass("show active").siblings().hide();
    });

    jQuery(".navicon").on("click", function () {
        $(this).toggleClass("open");
    });

    jQuery(".toggle-btn").on("click", function () {
        $(this).toggleClass("active");
        $(".account-sidebar").toggleClass("show");
    });

    $(".form-toggle").click(function () {
        $(".login-area").hide();
        $(".forget-password-area").slideDown("slow");
    });
});
