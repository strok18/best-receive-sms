/*!
 * jquery.okayNav.js 2.0.4 (https://github.com/VPenkov/okayNav)
 * Author: Vergil Penkov (http://vergilpenkov.com/)
 * MIT license: https://opensource.org/licenses/MIT
 */
!function (e) {
    "function" == typeof define && define.amd ? define(["jquery"], e) : "object" == typeof module && module.exports ? module.exports = function (n, i) {
        return void 0 === i && (i = "undefined" != typeof window ? require("jquery") : require("jquery")(n)), e(i), i
    } : e(jQuery)
}(function (e) {
    function n(n, i) {
        self = this, this.options = e.extend({}, s, i), self.options = this.options, self.navigation = e(n), self.document = e(document), self.window = e(window), "" == this.options.parent ? this.options.parent = self.navigation.parent() : "", self.nav_open = !1, self.parent_full_width = 0, self.radCoef = 180 / Math.PI, self.sTouch = {
            x: 0,
            y: 0
        }, self.cTouch = {
            x: 0,
            y: 0
        }, self.sTime = 0, self.nav_position = 0, self.percent_open = 0, self.nav_moving = !1, self.init()
    }

    var i = "okayNav", s = {
        parent: "",
        toggle_icon_class: "okayNav__menu-toggle",
        toggle_icon_content: "<span /><span /><span />",
        align_right: !0,
        swipe_enabled: !0,
        threshold: 50,
        resize_delay: 10,
        beforeOpen: function () {
        },
        afterOpen: function () {
        },
        beforeClose: function () {
        },
        afterClose: function () {
        },
        itemHidden: function () {
        },
        itemDisplayed: function () {
        }
    };
    n.prototype = {
        init: function () {
            e("body").addClass("okayNav-loaded"), self.navigation.addClass("okayNav loaded").children("ul").addClass("okayNav__nav--visible"), self.options.align_right ? self.navigation.append('<ul class="okayNav__nav--invisible transition-enabled nav-right" />').append('<a href="#" class="' + self.options.toggle_icon_class + ' okay-invisible">' + self.options.toggle_icon_content + "</a>") : self.navigation.prepend('<ul class="okayNav__nav--invisible transition-enabled nav-left" />').prepend('<a href="#" class="' + self.options.toggle_icon_class + ' okay-invisible">' + self.options.toggle_icon_content + "</a>"), self.nav_visible = self.navigation.children(".okayNav__nav--visible"), self.nav_invisible = self.navigation.children(".okayNav__nav--invisible"), self.toggle_icon = self.navigation.children("." + self.options.toggle_icon_class), self.toggle_icon_width = self.toggle_icon.outerWidth(!0), self.default_width = self.getChildrenWidth(self.navigation), self.parent_full_width = e(self.options.parent).outerWidth(!0), self.last_visible_child_width = 0, self.initEvents(), self.nav_visible.contents().filter(function () {
                return this.nodeType = Node.TEXT_NODE && /\S/.test(this.nodeValue) === !1
            }).remove(), 1 == self.options.swipe_enabled && self.initSwipeEvents()
        }, initEvents: function () {
            self.document.on("click.okayNav", function (n) {
                var i = e(n.target);
                self.nav_open === !0 && 0 == i.closest(".okayNav").length && self.closeInvisibleNav(), i.hasClass(self.options.toggle_icon_class) && (n.preventDefault(), self.toggleInvisibleNav())
            });
            var n = self.recalcNav();
            self.options.recalc_delay;
            self.window.on("load.okayNav resize.okayNav", n)
        }, initSwipeEvents: function () {
            self.document.on("touchstart.okayNav", function (n) {
                if (self.nav_invisible.removeClass("transition-enabled"), 1 == n.originalEvent.touches.length) {
                    var i = n.originalEvent.touches[0];
                    (i.pageX < 25 && 0 == self.options.align_right || i.pageX > e(self.options.parent).outerWidth(!0) - 25 && 1 == self.options.align_right || self.nav_open === !0) && (self.sTouch.x = self.cTouch.x = i.pageX, self.sTouch.y = self.cTouch.y = i.pageY, self.sTime = Date.now())
                }
            }).on("touchmove.okayNav", function (e) {
                var n = e.originalEvent.touches[0];
                self._triggerMove(n.pageX, n.pageY), self.nav_moving = !0
            }).on("touchend.okayNav", function (e) {
                self.sTouch = {x: 0, y: 0}, self.cTouch = {
                    x: 0,
                    y: 0
                }, self.sTime = 0, self.percent_open > 100 - self.options.threshold ? (self.nav_position = 0, self.closeInvisibleNav()) : 1 == self.nav_moving && (self.nav_position = self.nav_invisible.width(), self.openInvisibleNav()), self.nav_moving = !1, self.nav_invisible.addClass("transition-enabled")
            })
        }, _getDirection: function (e) {
            return self.options.align_right ? e > 0 ? -1 : 1 : 0 > e ? -1 : 1
        }, _triggerMove: function (e, n) {
            self.cTouch.x = e, self.cTouch.y = n;
            var i = Date.now(), s = self.cTouch.x - self.sTouch.x, l = self.cTouch.y - self.sTouch.y, t = l * l,
                o = Math.sqrt(s * s + t), a = Math.sqrt(t), f = Math.asin(Math.sin(a / o)) * self.radCoef;
            o / (i - self.sTime);
            if (self.sTouch.x = e, self.sTouch.y = n, 20 > f) {
                var v = self._getDirection(s), c = self.nav_position + v * o, r = self.nav_invisible.width(), d = 0;
                0 > c ? d = -c : c > r && (d = r - c);
                var _ = r - (self.nav_position + v * o + d), p = _ / r * 100;
                self.nav_position += v * o + d, self.percent_open = p, self.nav_invisible.css("transform", "translateX(" + (self.options.align_right ? 1 : -1) * p + "%)")
            }
        }, getParent: function () {
            return self.options.parent
        }, getVisibleNav: function () {
            return self.nav_visible
        }, getInvisibleNav: function () {
            return self.nav_invisible
        }, getNavToggleIcon: function () {
            return self.toggle_icon
        }, _debounce: function (e, n, i) {
            var s;
            return function () {
                var l = this, t = arguments, o = function () {
                    s = null, i || e.apply(l, t)
                }, a = i && !s;
                clearTimeout(s), s = setTimeout(o, n), a && e.apply(l, t)
            }
        }, openInvisibleNav: function () {
            self.options.enable_swipe ? "" : self.options.beforeOpen.call(), self.toggle_icon.addClass("icon--active"), self.nav_invisible.addClass("nav-open"), self.nav_open = !0, self.nav_invisible.css({
                "-webkit-transform": "translateX(0%)",
                transform: "translateX(0%)"
            }), self.options.afterOpen.call()
        }, closeInvisibleNav: function () {
            self.options.enable_swipe ? "" : self.options.beforeClose.call(), self.toggle_icon.removeClass("icon--active"), self.nav_invisible.removeClass("nav-open"), self.options.align_right ? self.nav_invisible.css({
                "-webkit-transform": "translateX(100%)",
                transform: "translateX(100%)"
            }) : self.nav_invisible.css({
                "-webkit-transform": "translateX(-100%)",
                transform: "translateX(-100%)"
            }), self.nav_open = !1, self.options.afterClose.call()
        }, toggleInvisibleNav: function () {
            self.nav_open ? self.closeInvisibleNav() : self.openInvisibleNav()
        }, getChildrenWidth: function (n) {
            for (var i = 0, s = e(n).children(), l = 0; l < s.length; l++) i += e(s[l]).outerWidth(!0);
            return i
        }, getVisibleItemCount: function () {
            //console.log('显示个数' + e("li", self.nav_visible).length)
            return e("li", self.nav_visible).length
        }, getHiddenItemCount: function () {
            //console.log('隐藏个数' + e("li", self.nav_invisible).length)
            return e("li", self.nav_invisible).length
        }, recalcNav: function () {
            var n = e(self.options.parent).outerWidth(!0), i = self.getChildrenWidth(self.options.parent),
                s = self.navigation.outerWidth(!0), l = self.getVisibleItemCount(),
                t = self.nav_visible.outerWidth(!0) + self.toggle_icon_width,
                o = i + self.last_visible_child_width + self.toggle_icon_width, a = i - s + self.default_width;
            return n > a ? (self._expandAllItems(), void self.toggle_icon.addClass("okay-invisible")) : (l > 0 && t >= s && o >= n && self._collapseNavItem(), n > o + self.toggle_icon_width + 15 && self._expandNavItem(), void (0 == self.getHiddenItemCount() ? self.toggle_icon.addClass("okay-invisible") : self.toggle_icon.removeClass("okay-invisible")))
        }, _collapseNavItem: function () {
            var n = e("li:last-child", self.nav_visible);
            self.last_visible_child_width = n.outerWidth(!0), self.document.trigger("okayNav:collapseItem", n), n.detach().prependTo(self.nav_invisible), self.options.itemHidden.call(), self.recalcNav()
        }, _expandNavItem: function () {
            var n = e("li:first-child", self.nav_invisible);
            self.document.trigger("okayNav:expandItem", n), n.detach().appendTo(self.nav_visible), self.options.itemDisplayed.call()
        }, _expandAllItems: function () {
            e("li", self.nav_invisible).detach().appendTo(self.nav_visible), self.options.itemDisplayed.call()
        }, _collapseAllItems: function () {
            e("li", self.nav_visible).detach().appendTo(self.nav_invisible), self.options.itemHidden.call()
        }, destroy: function () {
            e("li", self.nav_invisible).appendTo(self.nav_visible), self.nav_invisible.remove(), self.nav_visible.removeClass("okayNav__nav--visible"), self.toggle_icon.remove(), self.document.unbind(".okayNav"), self.window.unbind(".okayNav")
        }
    }, e.fn[i] = function (s) {
        var l = arguments;
        if (void 0 === s || "object" == typeof s) return this.each(function () {
            e.data(this, "plugin_" + i) || e.data(this, "plugin_" + i, new n(this, s))
        });
        if ("string" == typeof s && "_" !== s[0] && "init" !== s) {
            var t;
            return this.each(function () {
                var o = e.data(this, "plugin_" + i);
                o instanceof n && "function" == typeof o[s] && (t = o[s].apply(o, Array.prototype.slice.call(l, 1))), "destroy" === s && e.data(this, "plugin_" + i, null)
            }), void 0 !== t ? t : this
        }
    }
});