$(function(){var c=!0;window.top.location.href.indexOf("type=all")>0&&(c=!1);gplinks.select=function(d,e){e.preventDefault();if(c&&d!="image")return!1;var a=$(this).children("input[name=fileUrl]").val();if(a&&window.top.opener){if(typeof window.top.opener.CKEDITOR!="undefined"){var b=window.top.location.search.match(/(?:[?&]|&amp;)CKEditorFuncNum=([^&]+)/i);window.top.opener.CKEDITOR.tools.callFunction(b&&b.length>1?b[1]:"",a)}else window.top.opener.SetUrl(a);window.top.close();window.top.opener.focus()}return!1}});