/**
 * Created by MIC on 2015/5/24.
 */

var ButtonCmd = function () {
    // 因为全局有可能已经声明了 setAttr，重复声明会导致冲突，button 无法响应
    if (typeof setAttr == 'undefined') {
        var setAttr = function (obj, attrName, attrValue) {
            // IE8 及以下不支持 object.setAttribute(name, value) 方法，需要用普通的键值对设置
            // 不知道这一段<script>开始就用 Object.prototype 的话是否能注入成功，暂且先用保守的伪面向对象调用方法吧。
            if (!obj) return;
            if (obj.setAttribute) {
                obj.setAttribute(attrName.toString(), attrValue);
            }
            else {
                obj[attrName.toString()] = attrValue;
            }
        }
    }

    this.renderButton = function (focusItem, state) {
        if (typeof(state) != 'undefined' && (state == 'hover' || state == 'fakehover')) {
            setAttr(focusItem, 'class', 'aptr aa_visited_hover');
        } else if (typeof(state) != 'undefined' && state == 'pressed') {
            setAttr(focusItem, 'class', 'aptr aa_visited');
        } else {
            setAttr(focusItem, 'class', 'aptr aa_normal');
        }
    };

    this.clickSelect = function (focusItem) {
        this.renderButton(focusItem, 'fakehover');
    };

    this.mousedownSelect = function (focusItem) {
        this.renderButton(focusItem, 'pressed');
    };
};

var buttonCmd = new ButtonCmd();
