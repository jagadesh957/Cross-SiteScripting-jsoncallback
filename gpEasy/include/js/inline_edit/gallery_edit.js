gplinks.gallery_inline_edit=function(t,o,p){function q(){function a(b,a,c){b.append('<a href="#" name="'+a+'"><img src="'+gpBase+'/include/imgs/blank.gif" height="16" width="16" class="'+c+'"/></a>')}e=g.find(i.sortable_area_sel);r();gp_editor.resetDirty();strip_from(k,"?");gp_editing.editor_tools();$("#ckeditor_top").html('<div id="gp_image_area"></div><div id="gp_upload_queue"></div>');l(false);f=$('<span class="gp_gallery_edit gp_floating_area"></span>').appendTo("body").hide();a(f,"gp_gallery_caption",
"page_edit");a(f,"gp_gallery_rm","delete");f.bind("mouseenter.gp_edit",function(){f.show()}).bind("mouseleave.gp_edit",function(){f.hide()});n();gplinks.gp_gallery_caption=function(b,a){a.preventDefault();f.hide();var c=$(h),e=c.find(".caption").html();c.find("img").attr("title");c='<div class="inline_box" id="gp_gallery_caption"><form><h3>'+gplang.cp+'</h3><textarea name="caption" cols="40" rows="8">'+$gp.htmlchars(e)+'</textarea><p><input type="submit" name="cmd" value="'+gplang.up+'" class="gp_gallery_update" /> <input type="button" name="" value="'+
gplang.ca+'" class="admin_box_close" /></p></form></div>';$gp.AdminBoxC(c)};gplinks.gp_gallery_rm=function(b,a){a.preventDefault();$(h).remove();f.hide()};gpinputs.gp_gallery_update=function(a){a.preventDefault();var a=$(this.form).find("textarea").val(),d=$(h).find(".caption");d.html(a);a=d.html();$(h).find("a").attr("title",a);$gp.CloseAdminBox()}}function n(){e.children("li").unbind("mouseenter.gp_edit, mouseleave.gp_edit, mousedown.gp_edit").bind("mouseenter.gp_edit",function(){var a=$(this).offset();
f.show().css({left:a.left,top:a.top});h=this}).bind("mouseleave.gp_edit",function(){f.hide()}).bind("mousedown.gp_edit",function(){f.hide()})}function r(){e.sortable({placeholder:"gp_drag_box",opacity:0.6,tolerance:"pointer",beforeStop:function(a,b){b.item.removeAttr("style").removeAttr("class")}});e.disableSelection()}function l(a){var b=strip_from(k,"?");b+="?cmd=gallery_images";a&&(b+="&dir="+encodeURIComponent(a));$gp.jGoTo(b)}function m(a){e.find(".gp_to_remove").remove();a.attr({name:i.img_name,
rel:i.img_rel,title:""}).removeAttr("class");a=$("<li>").append(a).append('<div class="caption"></div>');e.append(a);n()}function s(a){a.attr("action");a.find(".file").auto_upload({start:function(a,d){d.bar=$('<a href="#" name="gp_file_uploading">'+a+"</a>").appendTo("#gp_upload_queue");return true},progress:function(a,d,c){a=Math.round(a*100);a=Math.min(98,a-1);c.bar.text(a+"% "+d)},finish:function(a,d,c){c=c.bar;c.text("100% "+d);var e=$(a),a=e.find(".status").val(),e=e.find(".message").val();a==
"success"?(c.addClass("success"),c.slideUp(1200),d=$("#gp_gallery_avail_imgs"),d=$(e).appendTo(d).find("a[name=gp_gallery_add]"),m(d.clone())):a=="notimage"?c.addClass("success"):(c.addClass("failed"),c.text(d+": "+e))},error:function(a,d,c){alert("error: "+c)}})}o.preventDefault();var i=$.extend(i,{sortable_area_sel:".gp_gallery",img_name:"gallery",img_rel:"gallery_gallery"},p),g=gp_editing.new_edit_area(this);if(g!=false){var f=false,j=false,h=false,k=strip_from(this.href,"#"),e;gp_editor={save_path:this.href,
destroy:function(){e.sortable("destroy");g.html(j.html());e.children("li").unbind("mouseenter.gp_edit, mouseleave.gp_edit, mousedown.gp_edit");f.remove()},checkDirty:function(){e.removeClass("ui-sortable");var a=j.html().replace(/>[\s]+/g,">"),b=g.html().replace(/>[\s]+/g,">");if(a!=b)return e.addClass("ui-sortable"),true;e.addClass("ui-sortable");return false},gp_saveData:function(){var a=g.clone();a.find("li.holder").remove();a.find("ul").removeClass("ui-sortable");a.find(".gp_nosave").remove();
a=a.html();return"gpcontent="+encodeURIComponent(a)},resetDirty:function(){j=g.clone(false);j.find(".ui-sortable").removeClass("ui-sortable")},updateElement:function(){}};gpresponse.rawcontent=function(a){g.get(0).innerHTML=a.CONTENT;q()};$gp.jGoTo(k+"&cmd=rawcontent");gplinks.gp_gallery_add=function(a,b){b.preventDefault();var d=$(this).stop(true,true);m(d.clone());d.parent().fadeTo(100,0.2).fadeTo(2E3,1)};gplinks.gp_gallery_add_all=function(a,b){b.preventDefault();$("#gp_gallery_avail_imgs a[name=gp_gallery_add]").each(function(){m($(this).clone())})};
gpresponse.gp_gallery_images=function(a){$("#gp_image_area").html(a.CONTENT);s($("#gp_upload_form"))};gplinks.gp_file_uploading=function(){var a=$(this),b=false;a.hasClass("failed")?b=true:a.hasClass("success")&&(b=true);b&&a.slideUp(700)};gplinks.gp_show_select=function(a,b){b.preventDefault();var d=$(this),c=d.siblings(".gp_edit_select_options");c.is(":visible")?c.hide():(c.show(),d.parent().unbind("mouseleave").bind("mouseleave",function(){c.hide()}))};gplinks.gp_gallery_folder=function(a,b){b.preventDefault();
l(a)};gpinputs.gp_gallery_folder_add=function(a,b){b.preventDefault();var d=this.form;l(d.dir.value+"/"+d.newdir.value)}}};