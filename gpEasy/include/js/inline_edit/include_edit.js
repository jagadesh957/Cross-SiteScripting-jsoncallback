function gp_init_inline_edit(g){var h=!1,i="",f=gp_editing.get_path(g),e=gp_editing.get_edit_area(g);if(!(e==!1||f==!1))gp_editor={save_path:f,destroy:function(){e.html(h)},checkDirty:function(){if(gp_editor.gp_saveData()!=i)return!0;return!1},gp_saveData:function(){return $("#gp_include_form").serialize()},resetDirty:function(){i=gp_editor.gp_saveData()},updateElement:function(){h=e.html()}},gp_editing.editor_tools(),$gp.jGoTo(f+"&cmd=include_dialog"),gpresponse.gp_include_dialog=function(c){$("#ckeditor_top").html(c.CONTENT);
gp_editor.resetDirty();gp_editor.updateElement()},gpresponse.gp_autocomplete_include=function(c){var d;d=c.SELECTOR=="file"?$("#gp_file_include"):$("#gp_gadget_include");eval(c.CONTENT);d.css({position:"relative",zIndex:12E3}).focus(function(){d.autocomplete("search",d.val())}).autocomplete({source:source,delay:100,minLength:0,open:function(){},select:function(b,a){$("#gp_include_form .autocomplete").val("");if(a.item)return this.value=a.item,!1}}).data("autocomplete")._renderItem=function(b,a){return $("<li></li>").data("item.autocomplete",
a[1]).append("<a>"+a[0]+"<span>"+a[1]+"</span></a>").appendTo(b)}},gplinks.gp_include_preview=function(c,d){d.preventDefault();loading();var b=gp_editor.save_path;b=strip_from(b,"#");var a="";b.indexOf("?")>0&&(a=strip_to(b,"?")+"&");a+=gp_editor.gp_saveData();a+="&cmd=preview";$gp.postC(b,a)},gpresponse.gp_include_content=function(c){e.html(c.CONTENT)}};