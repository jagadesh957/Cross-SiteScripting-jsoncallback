(function(b,P,fa){function c(a,d){var q=P.createElement("div");if(a)q.id=l+a;q.style.cssText=d||!1;return b(q)}function i(a,b){b=b==="x"?p.width():p.height();return typeof a==="string"?Math.round(/%/.test(a)?b/100*parseInt(a,10):parseInt(a,10)):a}function R(Q){return a.photo||/\.(gif|png|jpg|jpeg|bmp)(?:\?([^#]*))?(?:#(\.*))?$/i.test(Q)}function ba(a){for(var d in a)b.isFunction(a[d])&&d.substring(0,2)!=="on"&&(a[d]=a[d].call(j));a.rel=a.rel||j.rel||"nofollow";a.href=b.trim(a.href||b(j).attr("href"));
a.title=a.title||j.title}function x(a,d){d&&d.call(j);b.event.trigger(a)}function ga(){var b,d=l+"Slideshow_",q="click."+l,c,f;a.slideshow&&g[1]&&(c=function(){B.text(a.slideshowStop).unbind(q).bind(S,function(){if(h<g.length-1||a.loop)b=setTimeout(e.next,a.slideshowSpeed)}).bind(T,function(){clearTimeout(b)}).one(q+" "+J,f);m.removeClass(d+"off").addClass(d+"on");b=setTimeout(e.next,a.slideshowSpeed)},f=function(){clearTimeout(b);B.text(a.slideshowStart).unbind([S,T,J,q].join(" ")).one(q,c);m.removeClass(d+
"on").addClass(d+"off")},a.slideshowAuto?c():f())}function U(Q){if(!K){j=Q;ba(b.extend(a,b.data(j,r)));g=b(j);h=0;a.rel!=="nofollow"&&(g=b("."+C).filter(function(){return(b.data(this,r).rel||this.rel)===a.rel}),h=g.index(j),h===-1&&(g=g.add(j),h=g.length-1));if(!t){t=D=!0;m.show();if(a.returnFocus)try{j.blur(),b(j).one(ca,function(){try{this.focus()}catch(a){}})}catch(d){}s.css({opacity:+a.opacity,cursor:a.overlayClose?"pointer":"auto"}).show();a.w=i(a.initialWidth,"x");a.h=i(a.initialHeight,"y");
e.position(0);L&&p.bind("resize."+M+" scroll."+M,function(){s.css({width:p.width(),height:p.height(),top:p.scrollTop(),left:p.scrollLeft()})}).trigger("resize."+M);x(da,a.onOpen);V.add(u).hide();W.html(a.close).show()}e.load(!0)}}var ea={transition:"elastic",speed:300,width:!1,initialWidth:"600",innerWidth:!1,maxWidth:!1,height:!1,initialHeight:"450",innerHeight:!1,maxHeight:!1,minWidth:0,minHeight:0,scalePhotos:!0,scrolling:!0,inline:!1,html:!1,iframe:!1,fastIframe:!0,photo:!1,href:!1,title:!1,rel:!1,
opacity:0.9,preloading:!0,current:"image {current} of {total}",previous:"previous",next:"next",close:"close",open:!1,returnFocus:!0,loop:!0,slideshow:!1,slideshowAuto:!0,slideshowSpeed:2500,slideshowStart:"start slideshow",slideshowStop:"stop slideshow",onOpen:!1,onLoad:!1,onComplete:!1,onCleanup:!1,onClosed:!1,overlayClose:!0,escKey:!0,arrowKey:!0},r="colorbox",l="cbox",da=l+"_open",T=l+"_load",S=l+"_complete",J=l+"_cleanup",ca=l+"_closed",N=l+"_purge",E=b.browser.msie&&!b.support.opacity,L=E&&b.browser.version<
7,M=l+"_IE6",s,m,y,k,X,Y,Z,$,g,p,o,F,G,O,u,aa,B,H,I,W,V,a={},z,A,v,w,j,h,f,t,D,K=!1,e,C=l+"Element";e=b.fn[r]=b[r]=function(a,d){var c=this,e;if(!c[0]&&c.selector)return c;a=a||{};if(d)a.onComplete=d;if(!c[0]||c.selector===void 0)c=b("<a/>"),a.open=!0;c.each(function(){b.data(this,r,b.extend({},b.data(this,r)||ea,a));b(this).addClass(C)});e=a.open;b.isFunction(e)&&(e=e.call(c));e&&U(c[0]);return c};e.launch=U;e.init=function(){p=b(fa);m=c().attr({id:r,"class":E?l+(L?"IE6":"IE"):""});s=c("Overlay",
L?"position:absolute":"").hide();y=c("Wrapper");O=c("Controls").append(aa=c("Current"),B=c("Slideshow").bind(da,ga),H=c("Next"),I=c("Previous"),W=c("Close"));k=c("Content").append(o=c("LoadedContent","width:0; height:0; overflow:hidden"),G=c("LoadingOverlay").add(c("LoadingGraphic")),O,u=c("Title"));y.append(c().append(c("TopLeft"),X=c("TopCenter"),c("TopRight")),c(!1,"clear:left").append(Y=c("MiddleLeft"),k,Z=c("MiddleRight")),c(!1,"clear:left").append(c("BottomLeft"),$=c("BottomCenter"),c("BottomRight"))).children().children().css({"float":"left"});
F=c(!1,"position:absolute; width:9999px; visibility:hidden; display:none");b("body").prepend(s,m.append(y,F));k.children().hover(function(){b(this).addClass("hover")},function(){b(this).removeClass("hover")}).addClass("hover");z=X.height()+$.height()+k.outerHeight(!0)-k.height();A=Y.width()+Z.width()+k.outerWidth(!0)-k.width();v=o.outerHeight(!0);w=o.outerWidth(!0);m.css({"padding-bottom":z,"padding-right":A}).hide();H.click(function(){e.next()});I.click(function(){e.prev()});W.click(function(){e.close()});
V=H.add(I).add(aa).add(B);k.children().removeClass("hover");b("."+C).live("click.colorbox",function(a){a.button!==0&&typeof a.button!=="undefined"||a.ctrlKey||a.shiftKey||a.altKey||(a.preventDefault(),U(this))});s.click(function(){a.overlayClose&&e.close()});b(P).bind("keydown."+l,function(b){var c=b.keyCode;t&&a.escKey&&c===27&&(b.preventDefault(),e.close());t&&a.arrowKey&&g[1]&&(c===37?(b.preventDefault(),I.click()):c===39&&(b.preventDefault(),H.click()))})};e.remove=function(){m.add(s).remove();
b("."+C).die("click").removeData(r).removeClass(C)};e.position=function(b,c){function e(a){X[0].style.width=$[0].style.width=k[0].style.width=a.style.width;G[0].style.height=G[1].style.height=k[0].style.height=Y[0].style.height=Z[0].style.height=a.style.height}var f,g=Math.max(P.documentElement.clientHeight-a.h-v-z,0)/2+p.scrollTop(),h=Math.max(p.width()-a.w-w-A,0)/2+p.scrollLeft();f=m.width()===a.w+w&&m.height()===a.h+v?0:b;y[0].style.width=y[0].style.height="9999px";u.width(a.w);m.dequeue().animate({width:a.w+
w,height:a.h+v+u.height(),top:g,left:h},{duration:f,complete:function(){e(this);D=!1;y[0].style.width=a.w+w+A+"px";y[0].style.height=a.h+v+z+u.height()+"px";c&&c()},step:function(){e(this)}})};e.resize=function(b){if(t){b=b||{};if(b.width)a.w=i(b.width,"x")-w-A;if(b.innerWidth)a.w=i(b.innerWidth,"x");o.css({width:a.w});if(b.height)a.h=i(b.height,"y")-v-z;if(b.innerHeight)a.h=i(b.innerHeight,"y");if(!b.innerHeight&&!b.height)b=o.wrapInner("<div style='overflow:auto'></div>").children(),a.h=b.height(),
b.replaceWith(b.children());o.css({height:a.h});e.position(a.transition==="none"?0:a.speed)}};e.prep=function(s){function d(c){u.html(a.title||j.title||"");e.position(c,function(){var c,d,j,i;d=g.length;var n,k;if(t){k=function(){G.hide();x(S,a.onComplete)};E&&f&&o.fadeIn(100);O.show();u.add(o).show();if(d>1){if(typeof a.current==="string"&&aa.html(a.current.replace(/\{current\}/,h+1).replace(/\{total\}/,d)).show(),H[a.loop||h<d-1?"show":"hide"]().html(a.next),I[a.loop||h?"show":"hide"]().html(a.previous),
c=h?g[h-1]:g[d-1],j=h<d-1?g[h+1]:g[0],a.slideshow&&B.show(),a.preloading){i=b.data(j,r).href||j.href;d=b.data(c,r).href||c.href;i=b.isFunction(i)?i.call(j):i;d=b.isFunction(d)?d.call(c):d;if(R(i))b("<img/>")[0].src=i;if(R(d))b("<img/>")[0].src=d}}else V.hide();if(a.iframe){n=b("<iframe/>").addClass(l+"Iframe")[0];a.fastIframe?k():b(n).load(k);n.name=l+ +new Date;n.src=a.href;if(!a.scrolling)n.scrolling="no";if(E)n.frameBorder=0,n.allowTransparency="true";b(n).appendTo(o).one(N,function(){n.src="//about:blank"})}else k();
a.transition==="fade"?m.fadeTo(q,1,function(){m[0].style.filter=""}):m[0].style.filter="";p.bind("resize."+l,function(){e.position(0)})}})}if(t){var q=a.transition==="none"?0:a.speed;p.unbind("resize."+l);o.remove();o=c("LoadedContent").html(s);o.hide().appendTo(F.show()).css({width:function(){a.w=a.w||o.width();if(a.minWidth){var b=i(a.minWidth,"x");if(a.w<b)a.w=b}if(a.mw&&a.mw<a.w)a.w=a.mw;return a.w}(),overflow:a.scrolling?"auto":"hidden"}).css({height:function(){a.h=a.h||o.height();if(a.minHeight){var b=
i(a.minHeight,"y");if(a.h<b)a.h=b}if(a.mh&&a.mh<a.h)a.h=a.mh;return a.h}()}).prependTo(k);F.hide();b(f).css({"float":"none"});if(L)b("select").not(m.find("select")).filter(function(){return this.style.visibility!=="hidden"}).css({visibility:"hidden"}).one(J,function(){this.style.visibility="inherit"});a.transition==="fade"?m.fadeTo(q,0,function(){d(0)}):d(q)}};e.load=function(m){var d,k,n=e.prep;D=!0;f=!1;j=g[h];m||ba(b.extend(a,b.data(j,r)));x(N);x(T,a.onLoad);a.h=a.height?i(a.height,"y")-v-z:a.innerHeight&&
i(a.innerHeight,"y");a.w=a.width?i(a.width,"x")-w-A:a.innerWidth&&i(a.innerWidth,"x");a.mw=a.w;a.mh=a.h;if(a.maxWidth)a.mw=i(a.maxWidth,"x")-w-A,a.mw=a.w&&a.w<a.mw?a.w:a.mw;if(a.maxHeight)a.mh=i(a.maxHeight,"y")-v-z,a.mh=a.h&&a.h<a.mh?a.h:a.mh;d=a.href;O.hide();u.hide();G.show();a.inline?(c().hide().insertBefore(b(d)[0]).one(N,function(){b(this).replaceWith(o.children())}),n(b(d))):a.iframe?n(" "):a.html?n(a.html):R(d)?(b(f=new Image).addClass(l+"Photo").error(function(){a.title=!1;n(c("Error").text("This image could not be loaded"))}).load(function(){var b;
f.onload=null;a.scalePhotos&&(k=function(){f.height-=f.height*b;f.width-=f.width*b},a.mw&&f.width>a.mw&&(b=(f.width-a.mw)/f.width,k()),a.mh&&f.height>a.mh&&(b=(f.height-a.mh)/f.height,k()));setTimeout(function(){n(f);if(a.h)f.style.marginTop=Math.max(a.h-f.height,0)/2+"px";if(g[1]&&(h<g.length-1||a.loop))f.style.cursor="pointer",f.onclick=function(){e.next()};if(E)f.style.msInterpolationMode="bicubic"},1)}),setTimeout(function(){f.src=d},1)):d&&F.load(d,function(a,d,e){n(d==="error"?c("Error").text("Request unsuccessful: "+
e.statusText):b(this).contents())})};e.next=function(){if(!D&&g[1]&&(h<g.length-1||a.loop))h=h<g.length-1?h+1:0,e.load()};e.prev=function(){if(!D&&g[1]&&(h||a.loop))h=h?h-1:g.length-1,e.load()};e.close=function(){t&&!K&&(K=!0,t=!1,x(J,a.onCleanup),p.unbind("."+l+" ."+M),s.fadeTo(200,0),m.stop().fadeTo(300,0,function(){m.add(s).css({opacity:1,cursor:"auto"}).hide();x(N);o.remove();setTimeout(function(){K=!1;x(ca,a.onClosed)},1)}))};e.element=function(){return b(j)};e.settings=ea;b(e.init)})(jQuery,
document,this);