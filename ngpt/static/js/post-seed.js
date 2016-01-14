if(typeof editmode == 'undefined')
{
    editmode = false;
}
function setAttr(obj, attrName, attrValue)
{
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
//来自于mic的漂亮标签///////////////////////////////////////////////////
var RadioCmd = function () {
    //setAttr会跟全局的冲突?或者不能被识别?..不知道了...
    this.renderRadios = function (focusItem, state) {
        var groupName = focusItem.children[0].name;
        var _as = document.getElementsByName(groupName);
        for (var index = 0; index < _as.length; index++) {
            var t = _as.item(index);
            var pr = t.parentNode;
            if (focusItem == pr && typeof(state) != 'undefined' && (state == 'hover' || state == 'fakehover')) {
                if (t.checked) {
                    setAttr(pr, 'class', 'aptr aa_visited_hover');
                    setAttr('a', 'a', 'a')
                } else {
                    setAttr(pr, 'class', 'aptr aa_hover');
                }
            } else if (focusItem == pr && typeof(state) != 'undefined' && state == 'pressed') {
                setAttr(pr, 'class', 'aptr aa_visited');
            } else {
                if (t.checked) {
                    setAttr(pr, 'class', 'aptr aa_visited');
                } else {
                    setAttr(pr, 'class', 'aptr aa_normal');
                }
            }
        }
    };

    this.clickSelect = function (focusItem) {
        var rb = focusItem.children[0];
        rb.checked = true;
        this.renderRadios(focusItem, 'fakehover');
    };

    this.mousedownSelect = function (focusItem) {
        this.renderRadios(focusItem, 'pressed');
    };

    /**
     *
     * @param parent 父元素
     * @param name 所在组名称（也即是 radio 的名称）
     * @param text 显示的文本
     * @param value radio 实际提交给表单的值，该值会被创建为 ID 的一部分
     * @param clickCallbackName 单击时的回调函数的全名，签名为 (a: <a>元素)。
     */
    this.createRadio = function (parent, name, text, value, clickCallbackName) {
        var a = document.createElement('a');
        setAttr(a, 'href', 'javascript:;');
        setAttr(a, 'class', 'aptr aa_normal');
        setAttr(a, 'onmouseover', 'radioCmd.renderRadios(this, \'hover\');');
        setAttr(a, 'onmouseout', 'radioCmd.renderRadios(this);');
        var cb = (typeof(clickCallbackName) != 'undefined' && clickCallbackName != null && clickCallbackName.toString().length > 0) ? clickCallbackName.toString() + '(this);' : '';
        setAttr(a, 'onclick', 'radioCmd.clickSelect(this);' + cb + 'generateSubject();');
        setAttr(a, 'onmousedown', 'radioCmd.mousedownSelect(this);');
        var radio = document.createElement('input');
        radio.type = 'radio';
        radio.id = name + '_' + value;
        radio.name = name;
        radio.style.display = 'none';
        radio.value = value;
        a.innerHTML = text;
        a.appendChild(radio);
        parent.appendChild(a);
    };

    this.createRadioGroup = function (parent, name, textArray, valueArray, clickCallbackName) {
        if (textArray && valueArray)
        {
            for (var index in textArray) {
                this.createRadio(parent, name, textArray[index], valueArray[index], clickCallbackName);
            }
        }
    };

    this.checkRadio = function (obj, checked) {
        if (obj) {
            obj.checked = !!checked;
            this.renderRadios(obj.parentNode, 'pressed');
        }
    };

    this.checkRadioIndirect = function (groupName, index, checked) {
        index = parseInt(index);
        if (isNaN(index)) {
            index = 0;
        }
        var rs = document.getElementsByName(groupName);
        if (index < rs.length) {
            rs[index].checked = !!checked;
            this.renderRadios(rs[index].parentNode, 'pressed');
        }
    };

    /**
     *
     * @param groupContainer radio的父元素，a数组，的父元素
     * @param name 新建radio的name属性
     * @param textArray 显示的文本数组
     * @param valueArray 实际的值数组
     * @param clickCallbackName 单击时的回调函数的全名，签名为 (a: <a>元素)。
     */
    this.changeDynamicRadioGroup = function (groupContainer, name, textArray, valueArray, clickCallbackName) {
        if (!groupContainer) {
            return;
        }

        // 删除
        var chldr0 = [];
        for (var c in groupContainer.children) {
            if (!isNaN(c)) {
                chldr0 = chldr0.concat(groupContainer.children[c]);
            }
        }
        var chldr = [];
        for (var i in chldr0) {
            var c = chldr0[i];
            if (c.tagName.toLowerCase() == 'a' && c.children.length > 0) {
                var d = c.children[0];
                if (d.tagName.toLowerCase() == 'input' && d.type.toLowerCase() == 'radio') {
                    chldr = chldr.concat(c);
                }
            }
        }
        for (var i in chldr) {
            var c = chldr[i];
            groupContainer.removeChild(c);
        }

        // 重建
        this.createRadioGroup(groupContainer, name, textArray, valueArray, clickCallbackName);
        this.checkRadioIndirect(name, 0, true);
    };

    this.getRadioText = function (name) {
        var rs = document.getElementsByName(name);
        var r = '';
        if (rs.length > 0) {
            for (var index in rs) {
                if (rs[index].checked) {
                    /*
                     var p = rs[index].parentNode;
                     r = p.textContent;
                     // 对于 IE ≤ IE8，需要额外的兼容
                     if (typeof(r) == 'undefined') {
                     r = p.innerText;
                     }
                     // 再没有就没办法了
                     if (typeof(r) == 'undefined') {
                     r = '';
                     }*/
                    r = rs[index].value;
                    break;
                }
            }
        }
        return r;
    };
};

var radioCmd = new RadioCmd();
/////////////////////////////////////////////
// 加载: title formatter
function filteremphases(str, incbracket) {
    incbracket = typeof(incbracket) != typeof(undefined) ? incbracket : true;
    if (!str || !str.length || !(str.length > 0)) {
        return '';
    }
    var empharr = [
            /[【]/g, /[】]/g, /[〖]/g, /[〗]/g, /[『]/g, /[』]/g, /[「]/g, /[」]/g,
        ];
    if (incbracket) {
        empharr = empharr.concat(/[\[]/g, /[\]]/g);
    }
    for (var index in empharr) {
        str = str.replace(empharr[index], ' ');
    }
    str = str.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
    return str;
}
/////////////////////////////////////////////
function generator(index, obj, builder) {
    return function () {
        if(typeof filteremphases == 'undefined')
        {
            scriptLoader.loadScript("source/plugin/ngpt/static/js/titleformat.js");
        }
        ////下面这种使用titlearr的方法,在 提交失败后点击恢复数据后,无法正确生成标题
        //builder.titlearr[index] = obj.value;
        ////开始构造title
        //var sub = $(builder.subject.id);
        //sub.value = '';
        //// TODO: 可选选项应该在为空时不生成（thread表操作已实现）
        //for (var str in builder.titlearr) {
        //    if(strlen(builder.titlearr[str]) != 0)
        //    {
        //        sub.value += '[' +
        //        builder.titlearr[str] +
        //        ']';
        //    }
        //}
        //return true;
        var sub = $(builder.subject.id);
        sub.value = '';
        var formatjson = builder.formjson.subject;
        var index = 0;
        var titlearr = new Array();
        for (var name in formatjson) {
            var id = builder.genName(formatjson[name], '');
            //如果是null,那么可能是radios
            if ($(id) == null) {
                var radios = document.getElementsByName(formatjson[name]);
                var currvalue = '';
                if (radios == null)continue;
                for(var i = 0;i<radios.length;i++)
                {
                    if (radios[i].checked == true) {
                        currvalue = radios[i].value;
                    }
                }
                titlearr.push(currvalue);
                // TODO: 可选选项应该在为空时不生成（thread表操作已实现）
                if(strlen(currvalue) != 0)
                {
                    sub.value += '[' +
                        filteremphases(currvalue) +
                        ']';
                }
                index++;
                continue;
            }
            //这个需要用Change
            else
            {
                titlearr.push($(id).value);
            }
            index++;

            if(strlen($(id).value) != 0) {
                sub.value += '[' +
                    filteremphases($(id).value) +
                    ']';
            }
        }
        _max_title_length = 250;
        strLenCalc(sub, 'checklen', _max_title_length);
    }
}
function SubmitFormGenerator(rootId,subject,formjson,nameprefix) {
    this.root = $(rootId);
    this.subject = $(subject);
    this.formjson = formjson;
    this.prefix = nameprefix;
    this.setAttr = setAttr;
    this.simulateItems = [];
    //多个timer会产生影响，要清除以前设定的timer
    if (document.timer == undefined) {
        document.timer = [];
    }
    if (document.timer != undefined) {
        for (var idx in document.timer) {
            clearInterval(document.timer[idx]);
        }
    }
    this.getAttr = function (obj, attrName) {
        // 同上
        if (!obj) return;
        if (obj.getAttribute) {
            return obj.getAttribute(attrName.toString());
        }
        else {
            return obj[attrName.toString()];
        }
    };
    this.addSheetFile = function (path) {
        var fileref = document.createElement("link")
        fileref.rel = "stylesheet";
        fileref.type = "text/css";
        fileref.href = path;
        fileref.media = "screen";
        var headobj = document.getElementsByTagName('head')[0];
        headobj.appendChild(fileref);
    };
    this.getElem = function (elem) {
        return document.createElement(elem);
    };
    this.buildSubjectGenerator = function () {
        var formatjson = this.formjson.subject;
        this.titletxt = '';
        this.titlearr = new Array();
        var index = 0;
        funcgensubforsel = function (obj) {
            obj.subGen();
        };
        for (var name in formatjson) {
            var id = this.genName(formatjson[name], '');
            //如果是null,那么可能是radios
            if ($(id) == null) {
                var radios = document.getElementsByName(formatjson[name]);
                var currvalue = '';
                if (radios == null)continue;
                for(var i = 0;i<radios.length;i++)
                {
                    radios[i].subGen = generator(index, radios[i], this);
                    radios[i].change = 'funcgensubforsel($("' + radios[i].id + '"))';
                    if (radios[i].checked == true) {
                        currvalue = radios[i].value;
                    }
                }
                this.titlearr.push(currvalue);
                // TODO: 可选选项应该在为空时不生成（thread表操作已实现）
                if (editmode != true) {
                    if(strlen(currvalue) != 0)
                    {
                        $(this.subject.id).value += '[' +
                            currvalue +
                            ']';
                    }
                }
                index++;
                continue;
            }
            //这个需要用Change
            else if ($(id).tagName.toLowerCase() == 'select') {
                $(id).subGen = generator(index, $(id), this);
                // 而且由于 Discuz 的代码使用的是 getAttributes，所以必须优先选用 setAttributes 而不是直接设置属性
                setAttr($(id), 'change', 'funcgensubforsel($("' + id + '"))');
                $(id).change =  'funcgensubforsel($("' + id + '"))';
            }
            else {
                $(id).onchange = generator(index, $(id), this);
                //discuz的showselect出现的菜单改变值输入框值之后并不会出发onchange，做一点trick把
                document.timer.push(setInterval(generator(index, $(id), this), 250));
            }
            index++;

            this.titlearr.push($(id).value);
            // TODO: 可选选项应该在为空时不生成（thread表操作已实现）
            if (editmode != true) {
                if(strlen($(id).value) != 0) {
                    $(this.subject.id).value += '[' +
                        $(id).value +
                        ']';
                }
            }
        }
    };
    this.buildRoot = function () {
        //检查json元信息格式是否正确,
        //生成dl节点,并指导子节点的生成
        for (var i = 0; i < this.formjson.form.length; i++) {
            //TODO:hasOwnProperty倒地该怎么用?这里总有warning
            var item = this.formjson.form[i];
            switch (item.type) {
                case 'text': //文本域
                    this.buildText(item,false);
                    break;
                case 'select'://单选
                    this.buildSelect(item);
                    break;
                case 'multiple'://多选(标签)
                    this.buildMultiple(item);
                    break;
                case 'file'://文件
                    this.buildFile(item);
                    break;
                case 'calendar':
                    this.buildCalendar(item);
                    break;
                case 'radios':
                    this.buildRadios(item);
                    break;
                case 'hidden':
                    this.buildText(item,true);
                default:
                    break;
            }
        }
    };
    this.editmodeFillInput = function()
    {
        //编辑模式
        if(editmode == true)
        {
            var index = 0;
            for(var i = 0;i<this.formjson.form.length && i<12;i++)
            {
                var f = this.formjson.form[i];
                switch(f.type)
                {
                    case 'file':
                        continue;
                    case 'radios':
                        var gameType = fields[index];
                        var radioid = f.name + '_' + gameType;
                        var c = $(radioid);
                        if (c) {
                            radioCmd.checkRadio(c, true);
                        } else {
                            radioCmd.checkRadioIndirect(f.name, 0, true);
                        }
                        index++;
                        break;
                    default:
                        var id = currbuilder.genName(f.name,'');
                        setAttr($(id),'value',fields[index]);
                        index++;
                        break;
                }
            }
        }
    };
    this.build = function () {
        var root = this.getElem('dl');
        var tmp = this.root;
        this.root = root;
        this.buildRoot();
        this.root = tmp;
        this.root.appendChild(root);
        this.addSheetFile('/source/plugin/ngpt/static/styles/form_radios.css');

        //处理select的simulte,因为这个必须添加到document对象之后才能使用
        for(var i in this.simulateItems)
        {
            simulateSelect(this.simulateItems[i]);
        }

        this.editmodeFillInput();
        //这个必须在最后
        this.buildSubjectGenerator();
    };
    this.genName = function (name, surfix) {
        return this.prefix + '_' + name + '_' + surfix;
    };
    /****
     * @param isOptional 是必填项还是选填项?
     * @param title 该项目显示的名字
     * @param name 不解释= =
     * @return dt标签Node
     */
    this.buildTitle = function (isOptional, title, name) //即输入域左边的说明
    {
        var node = this.getElem('dt');
        this.setAttr(node, "id", this.genName(name, 'dt'));
        var tmp = isOptional ? ' ' : '*';
        var html = '<span class="rq">';
        html += tmp +
            '</span>' +
            '<label id="' + this.genName(name, 'text') + '">' +
            title +
            '</label>' +
            ':';
        node.innerHTML = html;
        return node;
    };
    this.buildComment = function (content) {
        if (content == undefined) {
            content = '';
        }
        var node = this.getElem('div');
        this.setAttr(node, "class", "form_comment");
        node.style.fontcolor = 'darkslategrey';
        node.style.fontSize = '0.95em';
        node.style.marginTop = '2px';
        node.innerHTML = content;
        return node;
    };
    //文本框类型dd,dt的生成
    this.buildText = function (jsontext,hidden) {
        var title = this.buildTitle(
            jsontext.optional,
            jsontext.title,
            jsontext.name
        );
        var dd = this.getElem('dd');
        var name = jsontext.name;
        this.setAttr(dd, "id", this.genName(name, 'dd'));
        /*
         <dt id="acgn_season_dt"><span class="rq">*</span><label id="acgn_season_text">季度信息</label>:</dt>
         <dd id="acgn_season_dd">
         <input type="text" id="acgn_season" name="acgn_season"
         class="px"
         style="width: 150px"
         onchange="generateSubject()"/>
         </dd>
         */
        var input = this.getElem('input');
        this.setAttr(input, "type", "text");
        this.setAttr(input, "id", this.genName(name, ''));
        this.setAttr(input, "name", name);
        this.setAttr(input, "class", "px");
        input.style.width = jsontext.width != undefined ? jsontext.width : '';
        dd.appendChild(input);

        if(hidden != undefined && hidden == true)
        {
            this.setAttr(input, "type", "hidden");
            input.value = jsontext.value != undefined ? jsontext.value : '';
        }
        else
        {
            this.root.appendChild(title);
            dd.appendChild(this.buildComment(jsontext.comment));
        }

        this.root.appendChild(dd);
    };
    //上传类型
    this.buildFile = function (jsontext) {
        var title = this.buildTitle(
            jsontext.optional,
            jsontext.title,
            jsontext.name
        );
        /*
         <dt><span class="rq">*</span><label>种子文件</label>:</dt>
         <dd>
         <input type="file" accept="application/x-bittorrent" name="torrent"
         id="torrent" style="width: 250px"/>
         </dd>
         */
        var dd = this.getElem('dd');
        var name = jsontext['name'];
        this.setAttr(dd, "id", this.genName(name, 'dd'));

        var input = this.getElem('input');
        this.setAttr(input, "type", "file");
        this.setAttr(input, "accept", jsontext.accept);
        this.setAttr(input, "name", name);
        this.setAttr(input, "id", this.genName(name, ''));
        input.style.width = jsontext.width != undefined ? jsontext.width : '';

        dd.appendChild(input);
        dd.appendChild(this.buildComment(jsontext.comment));

        J$("#postform").attr("enctype", "multipart/form-data");
        this.root.appendChild(title);
        this.root.appendChild(dd);
    };
    /****
     * @param jsontext optiongroup的数组
     */
    this.buildSelectOptionGroup = function (jsontext, tagname, parent) {
        var group = new Array();
        for (var i = 0; i < jsontext.length; i++) {
            //TODO:Related的实现
            var tmpnode = this.getElem('div');
            var options = jsontext[i].options;
            for (var key in options) {
                /*
                 <option value="0">TV</option>
                 <option value="1">WEB</option>
                 <option value="2">DISK</option>
                 */
                var option = this.getElem(tagname);
                this.setAttr(option, 'value', key.toString());
                option.innerHTML = options[key];
                tmpnode.appendChild(option);
            }
            //判断是否允许自定义,如果允许自定义则添加自定义选项
            group.push(tmpnode.innerHTML);
        }
        return group;
    };
    this.buildSelectUserDefined = function (jsontext) {
        var title = this.buildTitle(
            jsontext.optional,
            jsontext.title,
            jsontext.name
        );
        /*
         <dd class="hasd cl" id="acgn_source_dd">
         <ul id="acgn_source_list" style="display: none">
         </ul>
         <span>
         <input type="text" id="acgn_source" name="acgn_source"
         class="px" style="width: 150px"
         onchange="acgn_source_onchange(); generateSubject()"/>
         </span>
         <a href="javascript:;" class="dpbtn"
         onclick="showselect(this, 'acgn_source', 'acgn_source_list')">^</a>
         </dd>
         */
        var dd = this.getElem('dd');
        var name = jsontext['name'];
        this.setAttr(dd, "id", this.genName(name, 'dd'));
        this.setAttr(dd, "class", this.genName(name, 'hasd cl'));

        var span = this.getElem('span');
        this.setAttr(span, "class", "z");
        span.style.marginRight = '0px';

        var ul = this.getElem('ul');
        this.setAttr(ul, "id", this.genName(name, 'list'));
        ul.style.display = 'none';
        jsontext.optiongroup.contentNode = ul;
        ul.optiongroup = this.buildSelectOptionGroup(jsontext.optiongroup, 'li', ul);
        ul.innerHTML = ul.optiongroup[0];

        var input = this.getElem('input');
        this.setAttr(input, "type", 'text');
        this.setAttr(input, "name", name);
        this.setAttr(input, "id", this.genName(name, ''));
        this.setAttr(input, "class", "px");
        this.setAttr(input, "value", ul.childNodes[0].innerHTML);
        input.style.width = jsontext.width != undefined ? jsontext.width : '';
        if (jsontext.user_defined == false) {
            this.setAttr(input, 'readonly', 'readonly');
        }

        var a = this.getElem('a');
        a.innerHTML = '^';
        this.setAttr(a, 'href', 'javascript::');
        this.setAttr(a, 'class', 'dpbtn');
        this.setAttr(a, 'onclick',
            'showselect(this,"' + input.id + '","' + ul.id + '");');
        a.style.marginLeft = '-4px';


        span.appendChild(input);
        dd.appendChild(ul);
        dd.appendChild(span);
        dd.appendChild(a);

        this.root.appendChild(title);
        this.root.appendChild(dd);
    };
    this.buildSelectSystem = function (jsontext) {
        var title = this.buildTitle(
            jsontext.optional,
            jsontext.title,
            jsontext.name
        );
        /*
         <dd id="acgn_source_type_dd">
         <span class="ftid">
         <select name="acgn_source_type" id="acgn_source_type" class="ps" style="width: 50px"
         onchange="generateSubject()">
         <option value="0">TV</option>
         <option value="1">WEB</option>
         <option value="2">DISK</option>
         </select>
         </span>
         </dd>
         */
        var dd = this.getElem('dd');
        var name = jsontext['name'];
        this.setAttr(dd, "id", this.genName(name, 'dd'));

        var span = this.getElem('span');
        this.setAttr(span, "class", "ftid");

        var select = this.getElem('select');
        this.setAttr(select, "name", name);
        this.setAttr(select, "id", this.genName(name, ''));
        this.setAttr(select, "class", "ps");
        select.style.width = jsontext.width != undefined ? jsontext.width : '';
        select.optiongroup = this.buildSelectOptionGroup(jsontext.optiongroup,
            'option', select);
        select.innerHTML = select.optiongroup[0];

        span.appendChild(select);
        dd.appendChild(span);

        this.root.appendChild(title);
        this.root.appendChild(dd);

        this.simulateItems.push(select.id);
    };
    this.buildSelect = function (jsontext) {
        if (jsontext.user_defined == true) {
            this.buildSelectUserDefined(jsontext);
        }
        else {
            this.buildSelectSystem(jsontext);
        }
    };
    this.buildMutipleTags = function (jsontext, attachObj) {
        var group = new Array();
        for (var i = 0; i < jsontext.length; i++) {
            var listArray = jsontext[i].options;
            var tmpnode = this.getElem('div');
            for (var itemIndex in listArray) {
                var btn = document.createElement('button');
                this.setAttr(btn, 'type', 'button');
                this.setAttr(btn, 'value', listArray[itemIndex]);
                // Discuz 按钮样式
                this.setAttr(btn, 'class', 'pn');
                // 注意 line-height: normal;
                this.setAttr(btn, 'style', 'padding-left: 4px; padding-right: 4px; line-height: normal; margin: 2px;');
                btn.innerHTML = listArray[itemIndex];
                btn.onclick = (function (btn2) {
                    return function (event) {
                        var s = attachObj.value;
                        var sappend = '';
                        // http://www.cnblogs.com/rubylouvre/archive/2009/09/18/1568794.html
                        // 第一个相当于 trim()
                        if (s.replace(/^\s\s*/, '').replace(/\s\s*$/, '').length > 0 && s[s.length - 1] != '/') {
                            sappend = '/';
                        }
                        attachObj.value = attachObj.value + sappend + btn2.value;
                        attachObj.onchange(event);
                    };
                })(btn);
                tmpnode.appendChild(btn);
            }
            group.push(tmpnode);
        }
        return group;
    };
    this.buildMultiple = function (jsontext) {
        var title = this.buildTitle(
            jsontext.optional,
            jsontext.title,
            jsontext.name
        );
        /************
         <dd id="movie_category_dd" class="hasd cl">
         <div style="height: 24px;">
         <input type="text" id="movie_category" name="movie_category"
         class="px"
         style="width: 240px"
         onchange="generateSubject()"/>
         </div>
         <div id="movie_category_options" style="margin-top: 3px;"></div>
         </dd>
         *****/
        var dd = this.getElem('dd');
        var name = jsontext['name'];
        this.setAttr(dd, "id", this.genName(name, 'dd'));
        this.setAttr(dd, 'class', 'hasd cl');

        var divinput = this.getElem('div');
        divinput.style.height = "24px";
        var input = this.getElem('input');
        this.setAttr(input, "type", "text");
        this.setAttr(input, "name", name);
        this.setAttr(input, "id", this.genName(name, ''));
        this.setAttr(input, 'class', 'px');
        input.style.width = jsontext.width != undefined ? jsontext.width : '';
        divinput.style.width = jsontext.width != undefined ? jsontext.width : '';
        divinput.appendChild(input);

        var divoptions = this.getElem('div');
        this.setAttr(divoptions, "id", this.genName(name, 'options'));
        divoptions.style.marginTop = "3px";
        divoptions.optiongroup = this.buildMutipleTags(jsontext.optiongroup, input);

        var len = divoptions.optiongroup[0].childNodes.length;
        var nodes = divoptions.optiongroup[0].childNodes;
        for (var i = 0; i < len; i++) {
            //TODO:有木有更好的方法= =
            var node = nodes[0];
            divoptions.appendChild(node);
        }
        //appendChilde似乎会删掉原来的node,所有记得更换标签组的时候一定要
        //还原optiongroup

        dd.appendChild(divinput);
        dd.appendChild(divoptions);
        this.root.appendChild(title);
        this.root.appendChild(dd);
    };
    this.buildCalendar = function (jsontext) {
        var title = this.buildTitle(
            jsontext.optional,
            jsontext.title,
            jsontext.name
        );
        /*
         <dt id="acgn_pub_date_dt"><label id="acgn_pub_date_text">发行时间</label>:</dt>
         <dd id="acgn_pub_date_dd">
         <input type="text" id="acgn_pub_date" name="acgn_pub_date"
         class="px"
         style="width: 120px"
         onchange="generateSubject()"/>
         <!--onclick="showcalendar(event, this, false)"-->
         <button type="button" class="pn" onclick="setPubDateToToday()"><em>今天</em></button>
         </dd>
         */
        var dd = this.getElem('dd');
        var name = jsontext['name'];
        this.setAttr(dd, "id", this.genName(name, 'dd'));
        this.setAttr(dd, 'class', 'hasd cl');

        var input = this.getElem('input');
        var id  = this.genName(name,'');
        this.setAttr(input, "type", "text");
        this.setAttr(input, "name", name);
        this.setAttr(input, "id", this.genName(name, ''));
        this.setAttr(input, 'class', 'px');
        this.setAttr(input, 'onclick', 'showcalendar(event,this,false,null,null,null' +
            ',function(){$("'+id+'").onchange();})');
        input.style.width = jsontext.width != undefined ? jsontext.width : '';

        var btn = this.getElem('button');
        this.setAttr(btn, 'class', 'pn');
        this.setAttr(btn, 'type', 'button');
        btn.onclick = function (output) {
            return function () {
                var d = new Date();
                var s = d.getFullYear() + '-' + ((d.getMonth() + 1 < 10) ? '0' + (d.getMonth() + 1) : (d.getMonth() + 1)) + '-' +
                    (d.getDate() < 10 ? '0' + d.getDate() : d.getDate());
                output.value = s;
                input.onchange();
            };
        }(input);
        btn.innerHTML = '<em>今天</em>';
        btn.style.marginLeft = "9px";

        dd.appendChild(input);
        dd.appendChild(btn);
        this.root.appendChild(title);
        this.root.appendChild(dd);
    };

    this.buildRadios = function (jsontext) {
        var title = this.buildTitle(
            jsontext.optional,
            jsontext.title,
            jsontext.name
        );
        /********************************
         *
         <dt id="gamevid_game_type_dt"><span class="rq">*</span><label id="gamevid_game_type_text">游戏类型</label>:</dt>
         <dd id="gamevid_game_type_dd" class="hasd cl">
         <div id="gamevid_game_type_radios_container" class="aa_container">
         </div>
         </dd>
         */

        var dd = this.getElem('dd');
        var name = jsontext['name'];
        this.setAttr(dd, "id", this.genName(name, 'dd'));
        this.setAttr(dd, 'class', 'hasd cl');

        var div = this.getElem('div');
        var container_id = this.genName(name, 'container');
        this.setAttr(div, 'id', container_id);
        this.setAttr(div,'class',"aa_container");

        radioCmd.createRadioGroup(div, name, jsontext.optiongroup[0].options,
            jsontext.optiongroup[0].options,"console.log(this.childNodes[1].change());return;");

        dd.appendChild(div);
        this.root.appendChild(title);
        this.root.appendChild(dd);
    };
}

//下面是根据Typeid来变更相应表单的
var currform;
var currbuilder;
function TypeidObserver(json,root,sub)
{
    this.formjson = json;
    //说明不需要管typeid,直接生成表单
    if(json.none != undefined)
    {
        currform = json.none;
        currbuilder = new SubmitFormGenerator(root,sub,json.none,json.none.name_prefix);
        currbuilder.build();
    }
    else
    {
        type_id_onchange = function()
        {
            var obj = $('typeid');
            var value = obj.value;
            $(root).innerHTML = '';

            var index;
            for(index = 0;
                _my_threadtypes[index]!=value&&
                index<_my_threadtypes.length;index++);
            var tar = json[index];
            if (tar == undefined) {
                showError("请首先选择分类");
                return;
            }

            //未选择分类
            if (index >= _my_threadtypes.length) {
                showError("请首先选择分类");
            }

            $(sub).value = '';

            currbuilder = new SubmitFormGenerator(root,sub,tar,tar.name_prefix);
            currbuilder.build();

            currform = tar;
            currbuilder.editmodeFillInput();
        };
        setAttr($('typeid'),'change',"type_id_onchange();");
        type_id_onchange();
    }
}

J$("#subject").css('width',600);
var builder =
    new TypeidObserver(formjson,
        'post_seed_form',
        'subject');

J$("#postform").attr('onsubmit', "");
J$("#postform").submit(function(){
    var input = J$("input[type=file]").prop('files');
    if (input.length == 0 && !editmode) {
        showError("请选择种子文件");
        return false;
    }
    return validate(this);
});
