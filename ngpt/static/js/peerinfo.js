/**
 * Created by sunsijie on 5/15/15.
 */
currentjson = all;
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
//返回一个用index做排序的函数
function getSortFunc(index,desc)
{
    if(desc == true)
    {
        return function(a,b)
        {
            var vala = parseFloat(a[index]);
            var valb = parseFloat(b[index]);
            if(isNaN(vala) || isNaN(vala))
            {
                return a[index]<b[index];
            }
            else
            {
                return vala<valb;
            }
        }
    }
    else
    {
        return function(a,b)
        {
            var vala = parseFloat(a[index]);
            var valb = parseFloat(b[index]);
            if(isNaN(vala) || isNaN(vala))
            {
                return a[index]>b[index];
            }
            else
            {
                return vala>valb;
            }
        }
    }
}
function table_sort(obj,index)
{
    //判断该升序还是降序
    var pbuilder = obj.pbuilder;
    var originflag = obj.descsort;
    pbuilder.json.sort(getSortFunc(index,originflag));
    pbuilder.UpdateRoot();
    //update之后dom元素会变,需要重新获取
    obj = $(obj.id);
    if (obj) {
        obj.descsort = !originflag;

        //显示升序/降序的obj
        var ctl = $(obj.id);
        ctl && (ctl = ctl.childNodes[0]);
        ctl && (ctl = ctl.childNodes[1]);
        //ctl && (ctl.innerHTML = !originflag ? "&UpArrow;" : "&DownArrow;");
        ctl && (ctl.innerHTML = !originflag ? "↗" : "↘");
        /*
        if (ctl) {
            if (ctl.childNodes.length > 0) {
                ctl.removeChild(ctl.childNodes[0]);
            }
            var n = document.createElement('img');
            n.src = ngpt_img_root + (!originflag ? 'peerlistup.png' : 'peerlistdown.png');
            ctl.appendChild(n);
        }
        */
    }
}
//format 为 数据库列名称:显示名称
function BuildPeerListTable(json,format,prefix,root)
{
    this.json = json;
    this.format = format;
    this.prefix = prefix;
    this.root = root;
    this.GenTableHead = function()
    {
        var str = '';
        var tr = document.createElement('tr');
        tr.className = this.prefix + "headtr sictrt";
        for(var index in format)
        {
            var th = document.createElement('th');
            th.id = this.prefix + index + "head";
            th.className = this.prefix + "headtd";
            th.pbuilder = this;
            th.descsort = true; //默认降序
            setAttr(th,'onclick','table_sort(this,"'+ index +'")');

            //内容
            var a = document.createElement('a');
            a.innerHTML =  format[index][0];
            J$(a).css('color', 'blue');
            th.appendChild(a);
            //升序降序箭头
            var span = document.createElement('span');
            J$(span).css('color', 'blue');
            // 图标居中
            span.style.verticalAlign = 'middle';
            a.appendChild(span);

            tr.appendChild(th);
        }
        return tr;
    };
    this.GenPeerRow = function(peerjson)
    {
        var str = '';
        var tr = document.createElement('tr');
        tr.className = this.prefix + "peerrow sictr";
        for(index in format)
        {
            var content = peerjson[index];
            var func = format[index][1];
            if(func!=null)
            {
                content = func(content);
            }
            var td = document.createElement('td');
            td.className = this.prefix + index+"info";
            td.innerHTML = content;
            tr.appendChild(td);
        }
        return tr;
    };
    this.GenPeerTable = function()
    {
        var str = [];
        for(peer in this.json)
        {
            str.push(this.GenPeerRow(this.json[peer]));
        }
        return str;
    };
    this.UpdateRoot = function()
    {
        var peerrow = this.GenPeerTable();
        var peerhead = this.GenTableHead();
        var table = $(this.root);
        //为了IE8的兼容改成这样
        while(table.firstChild) //判断是否有子节点
            table.removeChild(table.firstChild);
        table.appendChild(peerhead);
        for(i in peerrow)
        {
            table.appendChild(peerrow[i]);
        }
        return peerrow.length;
    }
}
function formatSizeUnit(size)
{
    var u = ['Byte','KB','MB',"GB","TB","PB","EB"];
    var index = 0;
    size = parseFloat(size);
    while(size>=1024)
    {
        size /= 1024;
        index++;
    }
    return Math.round(size*100)/100 + u[index];
}
//格式:  表名:[显示名称,内容预处理函数]
var peerlistformat = {
    "user_id" : ["UID",null],
    "update_time":["最后更新",function(time){
        return new Date(parseInt(time)*1000).toLocaleString();
    }],
    "real_up":["真实上传",formatSizeUnit],
    "real_down":["真实下载",formatSizeUnit],
    "ipv4_addr":["ipv4地址",null],
    "ipv4_port":["ipv4端口",null],
    "ipv6_addr":["ipv6地址",null],
    "ipv6_port":["ipv6端口",null],
    "status":["下载状态",function(status){
        return status=='Leecher'?"正在下载":"正在做种";}],
    "client_tag":["客户端",function(cli){
        return cli.substr(1,2);
    }]

};
var historylistformat = {
    "username" : ["用户名",function(name){
        if(name == null)
        {
            return "匿名";
        }
        return name;
    }],
    "user_id" : ["UID",null],
    "create_time":["开始下载时间",function(time){
        return new Date(parseInt(time)*1000).toLocaleString();
    }],
    "stat_up":["统计上传",formatSizeUnit],
    "real_up":["真实上传",formatSizeUnit],
    "stat_down":["统计下载",formatSizeUnit],
    "real_down":["真实下载",formatSizeUnit],
    "update_time":["最后更新",function(time){
        return new Date(parseInt(time)*1000).toLocaleString();
    }]
};

var CURRENT_PEER_INDEX = 1;
function peerindex_change(obj,index)
{
    obj.className = "current";
    $('plistindex_'+CURRENT_PEER_INDEX).className='';
    CURRENT_PEER_INDEX = index;
    var builder = null;
    switch(parseInt(index))
    {
        case 0://全部用户
            builder = new  BuildPeerListTable(all,peerlistformat,index,'peertable');
            currentjson = all;
            break;
        case 1://做种
            builder = new  BuildPeerListTable(up,peerlistformat,index,'peertable');
            currentjson = up;
            break;
        case 2: //下载
            builder = new  BuildPeerListTable(down,peerlistformat,index,'peertable');
            currentjson = down;
            break;
        case 3: //历史
            builder = new  BuildPeerListTable(phistory,historylistformat,index,'peertable');
            currentjson = phistory;
            break;
    }
    if(builder != null)
    {
        var len = builder.UpdateRoot();
        $('pstatus') && ($('pstatus').innerHTML = "一共有" + len + "条记录");
    }
}

(function () {
    var f = function () {
        var ctl = $('plistindex_0');
        if (ctl) {
            ctl.click();
        }
    };
    if (!$('append_parent').myload_peer) {
        $('append_parent').myload_peer = f;
    }
    f();
})();
