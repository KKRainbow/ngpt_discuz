/**
 * Created by MIC on 2015/5/26.
 */

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
