var contentImport={},g=$G;function processWord(t){$(".file-tip").html("正在转换Word文件，请稍后..."),$(".file-result").html("").hide();var e=new FileReader;e.onload=function(t){mammoth.convertToHtml({arrayBuffer:t.target.result}).then(function(t){$(".file-tip").html("转换成功"),contentImport.data.result=t.value,$(".file-result").html(t.value).show()},function(t){$(".file-tip").html("Word文件转换失败:"+t)})},e.onerror=function(t){$(".file-tip").html("Word文件转换失败:"+t)},e.readAsArrayBuffer(t)}function processMarkdown(t){t=(new showdown.Converter).makeHtml(t);$(".file-tip").html("转换成功"),contentImport.data.result=t,$(".file-result").html(t).show()}function processMarkdownFile(t){$(".file-tip").html("正在转换Markdown文件，请稍后..."),$(".file-result").html("").hide();var e=new FileReader;e.onload=function(t){processMarkdown(t.target.result)},e.onerror=function(t){$(".file-tip").html("Markdown文件转换失败:"+t)},e.readAsText(t,"UTF-8")}function addUploadButtonListener(){g("contentImport").addEventListener("change",function(){var t=this.files[0];const e=t.name;var n=e.substring(e.lastIndexOf(".")+1).toLowerCase();switch(n){case"docx":case"doc":processWord(t);break;case"md":processMarkdownFile(t);break;default:$(".file-tip").html("不支持的文件格式:"+n)}}),g("fileInputConfirm").addEventListener("click",function(){processMarkdown(g("fileInputContent").value),$(".file-input").hide()})}function addOkListener(){dialog.onok=function(){if(!contentImport.data.result)return alert("请先上传文件识别内容"),!1;editor.fireEvent("saveScene"),editor.execCommand("inserthtml",contentImport.data.result),editor.fireEvent("saveScene")},dialog.oncancel=function(){}}contentImport.data={result:null},contentImport.init=function(t,e){addUploadButtonListener(),addOkListener()};