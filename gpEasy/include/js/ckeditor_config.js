CKEDITOR.editorConfig=function(a){a.toolbar="gpeasy";a.resize_minWidth=!0;a.height=300;a.contentsCss=gpBase+"/include/css/ckeditor_contents.css";a.fontSize_sizes="Smaller/smaller;Normal/;Larger/larger;8/8px;9/9px;10/10px;11/11px;12/12px;14/14px;16/16px;18/18px;20/20px;22/22px;24/24px;26/26px;28/28px;36/36px;48/48px;72/72px";a.ignoreEmptyParagraph=!1;a.entities_latin=!1;a.entities_greek=!1;a.scayt_autoStartup=!1;a.disableNativeSpellChecker=!1;a.toolbar_gpeasy=[["Source","-","Templates"],"Cut,Copy,Paste,PasteText,PasteFromWord,-,Print,SpellChecker,Scayt".split(","),
"Undo,Redo,-,Find,Replace,-,SelectAll,RemoveFormat".split(","),"/","NumberedList,BulletedList,-,Outdent,Indent,Blockquote".split(","),["JustifyLeft","JustifyCenter","JustifyRight","JustifyBlock"],["Link","Unlink","Anchor"],"Image,Flash,Table,HorizontalRule,Smiley,SpecialChar,PageBreak".split(","),"/",["Format","Font","FontSize"],"Bold,Italic,Underline,Strike,-,Subscript,Superscript".split(","),["TextColor","BGColor"],["Maximize","ShowBlocks","-","About"]];a.toolbar_inline=[["Source","Templates","Print",
"ShowBlocks"],"Cut,Copy,Paste,PasteText,PasteFromWord,SelectAll,Find,Replace".split(","),["Undo","Redo","RemoveFormat","SpellChecker","Scayt"],"HorizontalRule,Smiley,SpecialChar,PageBreak,TextColor,BGColor".split(","),"Link,Unlink,Anchor,Image,Flash,Table".split(","),["Format","Font","FontSize"],"JustifyLeft,JustifyCenter,JustifyRight,JustifyBlock,NumberedList,BulletedList,Outdent,Indent".split(","),"Bold,Italic,Underline,Strike,Blockquote,Subscript,Superscript".split(",")]};
CKEDITOR.plugins.addExternal("gpautogrow",gpBase+"/include/js/inline_edit/","gp_autogrow.js");
CKEDITOR.on("dialogDefinition",function(a){var b=a.data.definition;if(a.data.name=="link"){var c=!1,a=b.getContents("info").get("protocol");a["default"]="";a.items.unshift(["",""]);b.onOk=CKEDITOR.tools.override(b.onOk,function(a){return function(){return c?c=!1:a.call(this)}});b.onLoad=CKEDITOR.tools.override(b.onLoad,function(a){return function(){a.call(this);var b=this.getContentElement("info","url").getInputElement().$,e=this.getContentElement("info","protocol").getInputElement().$;$(b).css({position:"relative",
zIndex:12E3}).autocomplete({source:gptitles,delay:100,minLength:0,select:function(a,d){if(d.item)return b.value=d.item,e.value="",a.which==13&&(c=!0),!1}}).data("autocomplete")._renderItem=function(a,b){return $("<li></li>").data("item.autocomplete",b[1]).append("<a>"+b[0]+"<span>"+b[1]+"</span></a>").appendTo(a)}}})}});