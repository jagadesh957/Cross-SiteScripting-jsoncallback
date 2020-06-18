(function(c){c.extend({tablesorter:new function(){function a(b,h){i(b+","+((new Date).getTime()-h.getTime())+"ms")}function i(b){typeof console!="undefined"&&typeof console.debug!="undefined"?console.log(b):alert(b)}function m(b,h){if(b.config.debug)var a="";if(b.tBodies.length!=0){var e=b.tBodies[0].rows;if(e[0])for(var f=[],p=e[0].cells.length,j=0;j<p;j++){var k=!1;c.metadata&&c(h[j]).metadata()&&c(h[j]).metadata().sorter?k=l(c(h[j]).metadata().sorter):b.config.headers[j]&&b.config.headers[j].sorter&&
(k=l(b.config.headers[j].sorter));if(!k)a:{for(var k=b,g=e,n=-1,m=j,t=q.length,u=!1,o=!1,r=!0;o==""&&r;)n++,g[n]?(u=g[n].cells[m],o=c.trim(v(k.config,u)),k.config.debug&&i("Checking if value was empty on row:"+n)):r=!1;for(g=1;g<t;g++)if(q[g].is(o,k,u)){k=q[g];break a}k=q[0]}b.config.debug&&(a+="column:"+j+" parser:"+k.id+"\n");f.push(k)}b.config.debug&&i(a);return f}}function l(b){for(var h=q.length,a=0;a<h;a++)if(q[a].id.toLowerCase()==b.toLowerCase())return q[a];return!1}function o(b){if(b.config.debug)var h=
new Date;for(var d=b.tBodies[0]&&b.tBodies[0].rows.length||0,e=b.tBodies[0].rows[0]&&b.tBodies[0].rows[0].cells.length||0,f=b.config.parsers,p={row:[],normalized:[]},j=0;j<d;++j){var k=c(b.tBodies[0].rows[j]),g=[];if(k.hasClass(b.config.cssChildRow))p.row[p.row.length-1]=p.row[p.row.length-1].add(k);else{p.row.push(k);for(var n=0;n<e;++n)g.push(f[n].format(v(b.config,k[0].cells[n]),b,k[0].cells[n]));g.push(p.normalized.length);p.normalized.push(g)}}b.config.debug&&a("Building cache for "+d+" rows:",
h);return p}function v(b,a){var d="";if(!a)return"";if(!b.supportsTextContent)b.supportsTextContent=a.textContent||!1;return d=b.textExtraction=="simple"?b.supportsTextContent?a.textContent:a.childNodes[0]&&a.childNodes[0].hasChildNodes()?a.childNodes[0].innerHTML:a.innerHTML:typeof b.textExtraction=="function"?b.textExtraction(a):c(a).text()}function w(b,h){if(b.config.debug)var d=new Date;for(var e=h.row,f=h.normalized,p=f.length,j=f[0].length-1,k=c(b.tBodies[0]),g=[],n=0;n<p;n++){var m=f[n][j];
g.push(e[m]);if(!b.config.appender)for(var t=e[m].length,i=0;i<t;i++)k[0].appendChild(e[m][i])}b.config.appender&&b.config.appender(b,g);g=null;b.config.debug&&a("Rebuilt table:",d);x(b);setTimeout(function(){c(b).trigger("sortEnd")},0)}function C(b){if(b.config.debug)var h=new Date;var d=D(b);$tableHeaders=c(b.config.selectorHeaders,b).each(function(a){this.column=d[this.parentNode.rowIndex+"-"+this.cellIndex];this.count=this.order=typeof b.config.sortInitialOrder!="Number"?b.config.sortInitialOrder.toLowerCase()==
"desc"?1:0:b.config.sortInitialOrder==1?1:0;var h;h=c.metadata&&c(this).metadata().sorter===!1?!0:!1;h||(h=b.config.headers[a]&&b.config.headers[a].sorter===!1?!0:!1);if(h)this.sortDisabled=!0;if(y(b,a))this.order=this.lockedOrder=y(b,a);this.sortDisabled||(h=c(this).addClass(b.config.cssHeader),b.config.onRenderHeader&&b.config.onRenderHeader.apply(h));b.config.headerList[a]=this});b.config.debug&&(a("Built headers:",h),i($tableHeaders));return $tableHeaders}function D(b){for(var a=[],d={},b=b.getElementsByTagName("THEAD")[0].getElementsByTagName("TR"),
e=0;e<b.length;e++)for(var c=b[e].cells,p=0;p<c.length;p++){var j=c[p],k=j.parentNode.rowIndex,g=k+"-"+j.cellIndex,n=j.rowSpan||1,j=j.colSpan||1,m;typeof a[k]=="undefined"&&(a[k]=[]);for(var i=0;i<a[k].length+1;i++)if(typeof a[k][i]=="undefined"){m=i;break}d[g]=m;for(i=k;i<k+n;i++){typeof a[i]=="undefined"&&(a[i]=[]);for(var g=a[i],l=m;l<m+j;l++)g[l]="x"}}return d}function y(b,a){if(b.config.headers[a]&&b.config.headers[a].lockedOrder)return b.config.headers[a].lockedOrder;return!1}function x(b){for(var a=
b.config.widgets,d=a.length,c=0;c<d;c++)z(a[c]).format(b)}function z(b){for(var a=r.length,c=0;c<a;c++)if(r[c].id.toLowerCase()==b.toLowerCase())return r[c]}function E(b,a){for(var c=a.length,e=0;e<c;e++)if(a[e][0]==b)return!0;return!1}function A(b,a,d,e){a.removeClass(e[0]).removeClass(e[1]);var f=[];a.each(function(){this.sortDisabled||(f[this.column]=c(this))});b=d.length;for(a=0;a<b;a++)f[d[a][0]].addClass(e[d[a][1]])}function F(a){if(a.config.widthFixed){var h=c("<colgroup>");c("tr:first td",
a.tBodies[0]).each(function(){h.append(c("<col>").css("width",c(this).width()))});c(a).prepend(h)}}function B(b,c,d){if(b.config.debug)var e=new Date;for(var f="var sortWrapper = function(a,b) {",i=c.length,j=0;j<i;j++){var k=c[j][0],g=c[j][1],k=b.config.parsers[k].type=="text"?g==0?s("text","asc",k):s("text","desc",k):g==0?s("numeric","asc",k):s("numeric","desc",k),n="e"+j;f+="var "+n+" = "+k;f+="if("+n+") { return "+n+"; } ";f+="else { "}j=d.normalized[0].length-1;f+="return a["+j+"]-b["+j+"];";
for(j=0;j<i;j++)f+="}; ";f+="return 0; ";f+="}; ";b.config.debug&&a("Evaling expression:"+f,new Date);eval(f);d.normalized.sort(sortWrapper);b.config.debug&&a("Sorting on "+c.toString()+" and dir "+g+" time:",e);return d}function s(a,c,d){var e="a["+d+"]",d="b["+d+"]";if(a=="text"&&c=="asc")return"("+e+" == "+d+" ? 0 : ("+e+" === null ? Number.POSITIVE_INFINITY : ("+d+" === null ? Number.NEGATIVE_INFINITY : ("+e+" < "+d+") ? -1 : 1 )));";else if(a=="text"&&c=="desc")return"("+e+" == "+d+" ? 0 : ("+
e+" === null ? Number.POSITIVE_INFINITY : ("+d+" === null ? Number.NEGATIVE_INFINITY : ("+d+" < "+e+") ? -1 : 1 )));";else if(a=="numeric"&&c=="asc")return"("+e+" === null && "+d+" === null) ? 0 :("+e+" === null ? Number.POSITIVE_INFINITY : ("+d+" === null ? Number.NEGATIVE_INFINITY : "+e+" - "+d+"));";else if(a=="numeric"&&c=="desc")return"("+e+" === null && "+d+" === null) ? 0 :("+e+" === null ? Number.POSITIVE_INFINITY : ("+d+" === null ? Number.NEGATIVE_INFINITY : "+d+" - "+e+"));"}var q=[],r=
[];this.defaults={cssHeader:"header",cssAsc:"headerSortUp",cssDesc:"headerSortDown",cssChildRow:"expand-child",sortInitialOrder:"asc",sortMultiSortKey:"shiftKey",sortForce:null,sortAppend:null,sortLocaleCompare:!0,textExtraction:"simple",parsers:{},widgets:[],widgetZebra:{css:["even","odd"]},headers:{},widthFixed:!1,cancelSelection:!0,sortList:[],headerList:[],dateFormat:"us",decimal:"/.|,/g",onRenderHeader:null,selectorHeaders:"thead th",debug:!1};this.benchmark=a;this.construct=function(a){return this.each(function(){if(this.tHead&&
this.tBodies){var h,d,e,f;this.config={};f=c.extend(this.config,c.tablesorter.defaults,a);h=c(this);c.data(this,"tablesorter",f);d=C(this);this.config.parsers=m(this,d);e=o(this);var i=[f.cssDesc,f.cssAsc];F(this);d.click(function(a){var b=h[0].tBodies[0]&&h[0].tBodies[0].rows.length||0;if(!this.sortDisabled&&b>0){h.trigger("sortStart");c(this);b=this.column;this.order=this.count++%2;if(this.lockedOrder)this.order=this.lockedOrder;if(a[f.sortMultiSortKey])if(E(b,f.sortList))for(a=0;a<f.sortList.length;a++){var g=
f.sortList[a],n=f.headerList[g[0]];if(g[0]==b)n.count=g[1],n.count++,g[1]=n.count%2}else f.sortList.push([b,this.order]);else{f.sortList=[];if(f.sortForce!=null){g=f.sortForce;for(a=0;a<g.length;a++)g[a][0]!=b&&f.sortList.push(g[a])}f.sortList.push([b,this.order])}setTimeout(function(){A(h[0],d,f.sortList,i);w(h[0],B(h[0],f.sortList,e))},1);return!1}}).mousedown(function(){if(f.cancelSelection)return this.onselectstart=function(){return!1},!1});h.bind("update",function(){var a=this;setTimeout(function(){a.config.parsers=
m(a,d);e=o(a)},1)}).bind("updateCell",function(a,b){var c=this.config,d=[b.parentNode.rowIndex-1,b.cellIndex];e.normalized[d[0]][d[1]]=c.parsers[d[1]].format(v(c,b),b)}).bind("sorton",function(a,b){c(this).trigger("sortStart");f.sortList=b;for(var g=f.sortList,h=this.config,m=g.length,l=0;l<m;l++){var o=g[l],q=h.headerList[o[0]];q.count=o[1];q.count++}A(this,d,g,i);w(this,B(this,g,e))}).bind("appendCache",function(){w(this,e)}).bind("applyWidgetId",function(a,b){z(b).format(this)}).bind("applyWidgets",
function(){x(this)});if(c.metadata&&c(this).metadata()&&c(this).metadata().sortlist)f.sortList=c(this).metadata().sortlist;f.sortList.length>0&&h.trigger("sorton",[f.sortList]);x(this)}})};this.addParser=function(a){for(var c=q.length,d=!0,e=0;e<c;e++)q[e].id.toLowerCase()==a.id.toLowerCase()&&(d=!1);d&&q.push(a)};this.addWidget=function(a){r.push(a)};this.formatFloat=function(a){a=parseFloat(a);return isNaN(a)?0:a};this.formatInt=function(a){a=parseInt(a);return isNaN(a)?0:a};this.isDigit=function(a){return/^[-+]?\d*$/.test(c.trim(a.replace(/[,.']/g,
"")))};this.clearTableBody=function(a){c.browser.msie?function(){for(;this.firstChild;)this.removeChild(this.firstChild)}.apply(a.tBodies[0]):a.tBodies[0].innerHTML=""}}});c.fn.extend({tablesorter:c.tablesorter.construct});var l=c.tablesorter;l.addParser({id:"text",is:function(){return!0},format:function(a){return c.trim(a.toLocaleLowerCase())},type:"text"});l.addParser({id:"digit",is:function(a,i){return c.tablesorter.isDigit(a,i.config)},format:function(a){return c.tablesorter.formatFloat(a)},type:"numeric"});
l.addParser({id:"currency",is:function(a){return/^[\u00c2\u00a3$\u00e2\u201a\u00ac?.]/.test(a)},format:function(a){return c.tablesorter.formatFloat(a.replace(RegExp(/[\u00c2\u00a3$\u00e2\u201a\u00ac]/g),""))},type:"numeric"});l.addParser({id:"ipAddress",is:function(a){return/^\d{2,3}[\.]\d{2,3}[\.]\d{2,3}[\.]\d{2,3}$/.test(a)},format:function(a){for(var a=a.split("."),i="",m=a.length,l=0;l<m;l++){var o=a[l];i+=o.length==2?"0"+o:o}return c.tablesorter.formatFloat(i)},type:"numeric"});l.addParser({id:"url",
is:function(a){return/^(https?|ftp|file):\/\/$/.test(a)},format:function(a){return jQuery.trim(a.replace(RegExp(/(https?|ftp|file):\/\//),""))},type:"text"});l.addParser({id:"isoDate",is:function(a){return/^\d{4}[\/-]\d{1,2}[\/-]\d{1,2}$/.test(a)},format:function(a){return c.tablesorter.formatFloat(a!=""?(new Date(a.replace(RegExp(/-/g),"/"))).getTime():"0")},type:"numeric"});l.addParser({id:"percent",is:function(a){return/\%$/.test(c.trim(a))},format:function(a){return c.tablesorter.formatFloat(a.replace(RegExp(/%/g),
""))},type:"numeric"});l.addParser({id:"usLongDate",is:function(a){return a.match(RegExp(/^[A-Za-z]{3,10}\.? [0-9]{1,2}, ([0-9]{4}|'?[0-9]{2}) (([0-2]?[0-9]:[0-5][0-9])|([0-1]?[0-9]:[0-5][0-9]\s(AM|PM)))$/))},format:function(a){return c.tablesorter.formatFloat((new Date(a)).getTime())},type:"numeric"});l.addParser({id:"shortDate",is:function(a){return/\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/.test(a)},format:function(a,i){var m=i.config,a=a.replace(/\-/g,"/");if(m.dateFormat=="us")a=a.replace(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/,
"$3/$1/$2");else if(m.dateFormat=="uk")a=a.replace(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/,"$3/$2/$1");else if(m.dateFormat=="dd/mm/yy"||m.dateFormat=="dd-mm-yy")a=a.replace(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2})/,"$1/$2/$3");return c.tablesorter.formatFloat((new Date(a)).getTime())},type:"numeric"});l.addParser({id:"time",is:function(a){return/^(([0-2]?[0-9]:[0-5][0-9])|([0-1]?[0-9]:[0-5][0-9]\s(am|pm)))$/.test(a)},format:function(a){return c.tablesorter.formatFloat((new Date("2000/01/01 "+a)).getTime())},
type:"numeric"});l.addParser({id:"metadata",is:function(){return!1},format:function(a,i,m){a=i.config;a=!a.parserMetadataName?"sortValue":a.parserMetadataName;return c(m).metadata()[a]},type:"numeric"});l.addWidget({id:"zebra",format:function(a){if(a.config.debug)var i=new Date;var m,l=-1,o;c("tr:visible",a.tBodies[0]).each(function(){m=c(this);m.hasClass(a.config.cssChildRow)||l++;o=l%2==0;m.removeClass(a.config.widgetZebra.css[o?0:1]).addClass(a.config.widgetZebra.css[o?1:0])});a.config.debug&&
c.tablesorter.benchmark("Applying Zebra widget",i)}})})(jQuery);