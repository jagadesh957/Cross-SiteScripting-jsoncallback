function gp_init_inline_edit(n,p,q){function o(){e.children("li").unbind("mouseenter.gp_edit, mouseleave.gp_edit, mousedown.gp_edit").bind("mouseenter.gp_edit",function(){var a=$(this).offset();f.show().css({left:a.left,top:a.top});h=this}).bind("mouseleave.gp_edit",function(){f.hide()}).bind("mousedown.gp_edit",function(){f.hide()})}function r(){e.sortable({placeholder:"gp_drag_box",opacity:0.6,tolerance:"pointer",beforeStop:function(a,b){b.item.removeAttr("style").removeAttr("class")}});e.disableSelection()}
function l(a){var b=strip_from(i,"?");b+="?cmd=gallery_images";a&&(b+="&dir="+encodeURIComponent(a));$gp.jGoTo(b)}function m(a,b){e.find(".gp_to_remove").remove();a.attr({name:j.img_name,rel:j.img_rel,title:""}).removeAttr("class");var c=$("<li>").append(a).append('<div class="caption"></div>');b?b.replaceWith(c):e.append(c);o()}function s(a){a.attr("action");a.find(".file").auto_upload({start:function(b,a){a.bar=$('<a href="#" name="gp_file_uploading">'+b+"</a>").appendTo("#gp_upload_queue");a.holder=
$('<li class="holder" style="display:none"></li>').appendTo(e);return!0},progress:function(b,a,d){b=Math.round(b*100);b=Math.min(98,b-1);d.bar.text(b+"% "+a)},finish:function(b,a,d){var e=d.bar;e.text("100% "+a);var f=$(b),b=f.find(".status").val(),f=f.find(".message").val();b=="success"?(e.addClass("success"),e.slideUp(1200),a=$("#gp_gallery_avail_imgs"),a=$(f).appendTo(a).find("a[name=gp_gallery_add]"),m(a.clone(),d.holder)):b=="notimage"?e.addClass("success"):(e.addClass("failed"),e.text(a+": "+
f))},error:function(a,c,d){alert("error: "+d)}})}var j=$.extend(j,{sortable_area_sel:".gp_gallery",img_name:"gallery",img_rel:"gallery_gallery"},q),f=!1,k=!1,h=!1,e,i=gp_editing.get_path(n),g=gp_editing.get_edit_area(n);if(!(g==!1||i==!1))gp_editor={save_path:i,destroy:function(){e.sortable("destroy");g.html(k.html());e.children("li").unbind("mouseenter.gp_edit, mouseleave.gp_edit, mousedown.gp_edit");f.remove()},checkDirty:function(){e.removeClass("ui-sortable");var a=k.html().replace(/>[\s]+/g,
">"),b=g.html().replace(/>[\s]+/g,">");if(a!=b)return e.addClass("ui-sortable"),!0;e.addClass("ui-sortable");return!1},gp_saveData:function(){var a=g.clone();a.find("li.holder").remove();a.find("ul").removeClass("ui-sortable");a.find(".gp_nosave").remove();a=a.html();return"gpcontent="+encodeURIComponent(a)},resetDirty:function(){k=g.clone(!1);k.find(".ui-sortable").removeClass("ui-sortable")},updateElement:function(){}},g.get(0).innerHTML=p.content,function(){function a(a,c,d){a.append('<a href="#" name="'+
c+'"><img src="'+gpBase+'/include/imgs/blank.gif" height="16" width="16" class="'+d+'"/></a>')}e=g.find(j.sortable_area_sel);r();gp_editor.resetDirty();strip_from(i,"?");gp_editing.editor_tools();$("#ckeditor_top").html('<div id="gp_image_area"></div><div id="gp_upload_queue"></div>');l(!1);f=$('<span class="gp_gallery_edit gp_floating_area"></span>').appendTo("body").hide();a(f,"gp_gallery_caption","page_edit");a(f,"gp_gallery_rm","delete");f.bind("mouseenter.gp_edit",function(){f.show()}).bind("mouseleave.gp_edit",
function(){f.hide()});o();gplinks.gp_gallery_caption=function(a,c){c.preventDefault();f.hide();var d=$(h),d=d.find(".caption").html()||d.find("a:first").attr("title"),d='<div class="inline_box" id="gp_gallery_caption"><form><h3>'+gplang.cp+'</h3><textarea name="caption" cols="40" rows="8">'+$gp.htmlchars(d)+'</textarea><p><input type="submit" name="cmd" value="'+gplang.up+'" class="gp_gallery_update" /> <input type="button" name="" value="'+gplang.ca+'" class="admin_box_close" /></p></form></div>';
$gp.AdminBoxC(d)};gplinks.gp_gallery_rm=function(a,c){c.preventDefault();$(h).remove();f.hide()};gpinputs.gp_gallery_update=function(a){a.preventDefault();var a=$(this.form).find("textarea").val(),c=$(h).find(".caption");c.html(a);a=c.html();$(h).find("a").attr("title",a);$gp.CloseAdminBox()}}(),gplinks.gp_gallery_add=function(a,b){b.preventDefault();var c=$(this).stop(!0,!0);m(c.clone());c.parent().fadeTo(100,0.2).fadeTo(2E3,1)},gplinks.gp_gallery_add_all=function(a,b){b.preventDefault();$("#gp_gallery_avail_imgs a[name=gp_gallery_add]").each(function(){m($(this).clone())})},
gpresponse.gp_gallery_images=function(a){$("#gp_image_area").html(a.CONTENT);s($("#gp_upload_form"))},gplinks.gp_file_uploading=function(){var a=$(this),b=!1;a.hasClass("failed")?b=!0:a.hasClass("success")&&(b=!0);b&&a.slideUp(700)},gplinks.gp_show_select=function(a,b){b.preventDefault();var c=$(this),d=c.siblings(".gp_edit_select_options");d.is(":visible")?d.hide():(d.show(),c.parent().unbind("mouseleave").bind("mouseleave",function(){d.hide()}))},gplinks.gp_gallery_folder=function(a,b){b.preventDefault();
l(a)},gpinputs.gp_gallery_folder_add=function(a,b){b.preventDefault();var c=this.form;l(c.dir.value+"/"+c.newdir.value)}};