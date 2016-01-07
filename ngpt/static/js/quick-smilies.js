/*
	[Discuz!] (C)2001-2099 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: smilies.js 29684 2012-04-25 04:00:58Z zhangguosheng $
*/

QSML_WIDTH = 50;
QSML_HEIGHT = 50;
QSML_PRE_WIDTH = 70;

function _qsmilies_show(id, smcols, seditorkey) {
	//if(seditorkey && !$(seditorkey + 'sml_menu')) {
	//	var div = document.createElement("div");
	//	div.id = seditorkey + 'sml_menu';
	//	div.style.display = 'none';
	//	div.className = 'sllt';
	//	$('append_parent').appendChild(div);
	//	var div = document.createElement("div");
	//	div.id = id;
	//	div.style.overflow = 'hidden';
	//	$(seditorkey + 'sml_menu').appendChild(div);
	//}
	if(typeof smilies_type == 'undefined') {
		var scriptNode = document.createElement("script");
		scriptNode.type = "text/javascript";
		scriptNode.charset = charset ? charset : (BROWSER.firefox ? document.characterSet : document.charset);
		scriptNode.src = 'data/cache/common_smilies_var.js?' + VERHASH;
		if(BROWSER.ie) {
			scriptNode.onreadystatechange = function() {
				qsmilies_onload(id, smcols, seditorkey);
			};
		} else {
			scriptNode.onload = function() {
				qsmilies_onload(id, smcols, seditorkey);
			};
		}
        $('append_parent').appendChild(scriptNode);
	} else {
		qsmilies_onload(id, smcols, seditorkey);
	}
}

function qsmilies_onload(id, smcols, seditorkey) {
    // 设置父 div 距离发帖框底部距离（不设置此项目则在快速发帖处显得比较奇怪）
    var o = $(id);
    if (typeof(o) != 'undefined') {
        o.style.marginTop = '8px';
    }
	seditorkey = !seditorkey ? '' : seditorkey;
	var smile = getcookie('smile').split('D');
	if(typeof smilies_type == 'object') {
		if(smile[0] && smilies_array[smile[0]]) {
			CURRENTSTYPE = smile[0];
		} else {
            // TODO: 此句句意为何？至少 foreach 循环的循环变量是索引/键。
            // 这句的目的应该就是取 数组中的第一个有效索引值.
			for(i in smilies_array) {
				CURRENTSTYPE = i;
                break;
			}
		}
        // m_c_compenstaion: quick_smilies.htm L4
		smiliestype = '<div id="'+id+'_tb" style="border-bottom: none; margin-bottom: 0;" class="tb tb_s cl"><ul>';
		for(i in smilies_type) {
			key = i.substring(1);
			if(smilies_type[i][0]) {
				smiliestype += '<li ' + (CURRENTSTYPE == key ? 'class="current"' : '') + ' id="'+seditorkey+'qstype_'+key+'" onclick="qsmilies_switch(\'' + id + '\', \'' + smcols + '\', '+key+', 1, \'' + seditorkey + '\');if(CURRENTSTYPE) {$(\''+seditorkey+'qstype_\'+CURRENTSTYPE).className=\'\';}this.className=\'current\';CURRENTSTYPE='+key+';doane(event);"><a href="javascript:;" hidefocus="true">'+smilies_type[i][0]+'</a></li>';
			}
		}
		smiliestype += '</ul></div>';
		$(id).innerHTML = smiliestype + '<div id="' + id + '_data" style="border-width: 1px; border-style: solid; border-color: lightgray; padding: 10px;">' +
            '</div><div class="sllt_p" id="' + id + '_page"></div>';
		qsmilies_switch(id, smcols, CURRENTSTYPE, smile[1], seditorkey);
		smilies_fastdata = '';
	}
}

function qsmilies_switch(id, smcols, type, page, seditorkey) {
	page = page ? page : 1;
	if(!smilies_array[type] || !smilies_array[type][page]) return;
	setcookie('smile', type + 'D' + page, 31536000);
	smiliesdata = '<table ' +
    'style="width:100%;" ' +
    'id="' + id + '_table" cellpadding="0" cellspacing="0"><tr>';
	j = k = 0;
	img = [];
	for(var i = 0; i < smilies_array[type][page].length; i++) {
		if(j >= smcols) {
			smiliesdata += '<tr>';
			j = 0;
		}
		s = smilies_array[type][page][i];
		smilieimg = STATICURL + 'image/smiley/' + smilies_type['_' + type][1] + '/' + s[2];
		img[k] = new Image();
		img[k].src = smilieimg;
		smiliesdata += s && s[0] ? '<td style="text-align: center;" ' +
			'id="' + seditorkey + 'smilie_' + s[0] + '_td"><img style="cursor: pointer;" onmouseover="qsmilies_preview(\'' + seditorkey + '\', \'' + id + '\', this, ' + s[5] + ')" onclick="' + (typeof wysiwyg != 'undefined' ? 'insertSmiley(' + s[0] + ')' : 'seditor_insertunit(\'' + seditorkey + '\', \'' + s[1].replace(/'/, '\\\'') + '\')') + '" id="smilie_' + s[0] + '" width="' + QSML_WIDTH +'" height="' + QSML_HEIGHT +'" src="' + smilieimg + '" alt="' + s[1] + '" />' : '<td>';
		j++;k++;
	}
	smiliesdata += '</table>';
	smiliespage = '';

    smiliespage = '<div class="z">';
    var c = 0;
    for(var i = 1;i<smilies_array[type].length;i++)
    {
        if(i == page)
        {
            smiliespage += '<a id="qsml_page_' + i + '" ' +
            'href="javascript::"' +
            '><strong>' + i + '</strong></a>';
            c = i;
        }
        else
        {
            smiliespage += '<a id="qsml_page_' + i + '" href="javascript:;" onclick="' +
            'qsmilies_switch(\'' + id + '\', \'' + smcols + '\', ' + type + ', ' + i + ', \'' + seditorkey + '\');doane(event);">' + i + '</a>';
        }
    }
    smiliespage += '</div><span>'+ (c) + '/' + (smilies_array[type].length - 1)+ '</span>';
	$(id + '_data').innerHTML = smiliesdata;
	$(id + '_page').innerHTML = smiliespage;
	$(id + '_tb').style.width = smcols*(16+parseInt(s[3])) + 'px';
}

function qsmilies_preview(seditorkey, id, obj, w) {
    return;
	var menu = $('qsmilies_preview');
	if(!menu) {
		menu = document.createElement('div');
		menu.id = 'qsmilies_preview';
		menu.className = 'sl_pv';
		menu.style.display = 'none';
		$('append_parent').appendChild(menu);
	}
	menu.innerHTML = '<img width="' + QSML_PRE_WIDTH + '" src="' + obj.childNodes[0].src + '" />';
	mpos = fetchOffset($(id + '_data'));
	spos = fetchOffset(obj);
	pos = spos['left'] >= mpos['left'] + $(id + '_data').offsetWidth / 2 ? '13' : '24';
	showMenu({'ctrlid':obj.id,'showid':id + '_data','menuid':menu.id,'pos':pos,'layer':3});
}