define("ace/mode/xml_highlight_rules",["require","exports","module","ace/lib/oop","ace/mode/text_highlight_rules"],function(e,t,n){"use strict";var o=e("../lib/oop"),e=e("./text_highlight_rules").TextHighlightRules,r=function(e){var t="[_:a-zA-ZÀ-￿][-_:.a-zA-Z0-9À-￿]*";this.$rules={start:[{token:"string.cdata.xml",regex:"<\\!\\[CDATA\\[",next:"cdata"},{token:["punctuation.instruction.xml","keyword.instruction.xml"],regex:"(<\\?)("+t+")",next:"processing_instruction"},{token:"comment.start.xml",regex:"<\\!--",next:"comment"},{token:["xml-pe.doctype.xml","xml-pe.doctype.xml"],regex:"(<\\!)(DOCTYPE)(?=[\\s])",next:"doctype",caseInsensitive:!0},{include:"tag"},{token:"text.end-tag-open.xml",regex:"</"},{token:"text.tag-open.xml",regex:"<"},{include:"reference"},{defaultToken:"text.xml"}],processing_instruction:[{token:"entity.other.attribute-name.decl-attribute-name.xml",regex:t},{token:"keyword.operator.decl-attribute-equals.xml",regex:"="},{include:"whitespace"},{include:"string"},{token:"punctuation.xml-decl.xml",regex:"\\?>",next:"start"}],doctype:[{include:"whitespace"},{include:"string"},{token:"xml-pe.doctype.xml",regex:">",next:"start"},{token:"xml-pe.xml",regex:"[-_a-zA-Z0-9:]+"},{token:"punctuation.int-subset",regex:"\\[",push:"int_subset"}],int_subset:[{token:"text.xml",regex:"\\s+"},{token:"punctuation.int-subset.xml",regex:"]",next:"pop"},{token:["punctuation.markup-decl.xml","keyword.markup-decl.xml"],regex:"(<\\!)("+t+")",push:[{token:"text",regex:"\\s+"},{token:"punctuation.markup-decl.xml",regex:">",next:"pop"},{include:"string"}]}],cdata:[{token:"string.cdata.xml",regex:"\\]\\]>",next:"start"},{token:"text.xml",regex:"\\s+"},{token:"text.xml",regex:"(?:[^\\]]|\\](?!\\]>))+"}],comment:[{token:"comment.end.xml",regex:"--\x3e",next:"start"},{defaultToken:"comment.xml"}],reference:[{token:"constant.language.escape.reference.xml",regex:"(?:&#[0-9]+;)|(?:&#x[0-9a-fA-F]+;)|(?:&[a-zA-Z0-9_:\\.-]+;)"}],attr_reference:[{token:"constant.language.escape.reference.attribute-value.xml",regex:"(?:&#[0-9]+;)|(?:&#x[0-9a-fA-F]+;)|(?:&[a-zA-Z0-9_:\\.-]+;)"}],tag:[{token:["meta.tag.punctuation.tag-open.xml","meta.tag.punctuation.end-tag-open.xml","meta.tag.tag-name.xml"],regex:"(?:(<)|(</))((?:"+t+":)?"+t+")",next:[{include:"attributes"},{token:"meta.tag.punctuation.tag-close.xml",regex:"/?>",next:"start"}]}],tag_whitespace:[{token:"text.tag-whitespace.xml",regex:"\\s+"}],whitespace:[{token:"text.whitespace.xml",regex:"\\s+"}],string:[{token:"string.xml",regex:"'",push:[{token:"string.xml",regex:"'",next:"pop"},{defaultToken:"string.xml"}]},{token:"string.xml",regex:'"',push:[{token:"string.xml",regex:'"',next:"pop"},{defaultToken:"string.xml"}]}],attributes:[{token:"entity.other.attribute-name.xml",regex:t},{token:"keyword.operator.attribute-equals.xml",regex:"="},{include:"tag_whitespace"},{include:"attribute_value"}],attribute_value:[{token:"string.attribute-value.xml",regex:"'",push:[{token:"string.attribute-value.xml",regex:"'",next:"pop"},{include:"attr_reference"},{defaultToken:"string.attribute-value.xml"}]},{token:"string.attribute-value.xml",regex:'"',push:[{token:"string.attribute-value.xml",regex:'"',next:"pop"},{include:"attr_reference"},{defaultToken:"string.attribute-value.xml"}]}]},this.constructor===r&&this.normalizeRules()};(function(){this.embedTagRules=function(e,t,n){this.$rules.tag.unshift({token:["meta.tag.punctuation.tag-open.xml","meta.tag."+n+".tag-name.xml"],regex:"(<)("+n+"(?=\\s|>|$))",next:[{include:"attributes"},{token:"meta.tag.punctuation.tag-close.xml",regex:"/?>",next:t+"start"}]}),this.$rules[n+"-end"]=[{include:"attributes"},{token:"meta.tag.punctuation.tag-close.xml",regex:"/?>",next:"start",onMatch:function(e,t,n){return n.splice(0),this.token}}],this.embedRules(e,t,[{token:["meta.tag.punctuation.end-tag-open.xml","meta.tag."+n+".tag-name.xml"],regex:"(</)("+n+"(?=\\s|>|$))",next:n+"-end"},{token:"string.cdata.xml",regex:"<\\!\\[CDATA\\["},{token:"string.cdata.xml",regex:"\\]\\]>"}])}}).call(e.prototype),o.inherits(r,e),t.XmlHighlightRules=r}),define("ace/mode/behaviour/xml",["require","exports","module","ace/lib/oop","ace/mode/behaviour","ace/token_iterator","ace/lib/lang"],function(e,t,n){"use strict";function s(e,t){return e&&-1<e.type.lastIndexOf(t+".xml")}var o=e("../../lib/oop"),r=e("../behaviour").Behaviour,g=e("../../token_iterator").TokenIterator,e=(e("../../lib/lang"),function(){this.add("string_dquotes","insertion",function(e,t,n,o,r){if('"'==r||"'"==r){var a=r,r=o.doc.getTextRange(n.getSelectionRange());if(""!==r&&"'"!==r&&'"'!=r&&n.getWrapBehavioursEnabled())return{text:a+r+a,selection:!1};var r=n.getCursorPosition(),n=o.doc.getLine(r.row).substring(r.column,r.column+1),i=new g(o,r.row,r.column),l=i.getCurrentToken();if(n==a&&(s(l,"attribute-value")||s(l,"string")))return{text:"",selection:[1,1]};if(l=l||i.stepBackward()){for(;s(l,"tag-whitespace")||s(l,"whitespace");)l=i.stepBackward();r=!n||n.match(/\s/);return s(l,"attribute-equals")&&(r||">"==n)||s(l,"decl-attribute-equals")&&(r||"?"==n)?{text:a+a,selection:[1,1]}:void 0}}}),this.add("string_dquotes","deletion",function(e,t,n,o,r){var a=o.doc.getTextRange(r);if(!r.isMultiLine()&&('"'==a||"'"==a)&&o.doc.getLine(r.start.row).substring(r.start.column+1,r.start.column+2)==a)return r.end.column++,r}),this.add("autoclosing","insertion",function(e,t,n,o,r){if(">"==r){var n=n.getSelectionRange().start,a=new g(o,n.row,n.column),i=a.getCurrentToken()||a.stepBackward();if(i&&(s(i,"tag-name")||s(i,"tag-whitespace")||s(i,"attribute-name")||s(i,"attribute-equals")||s(i,"attribute-value"))&&!s(i,"reference.attribute-value")){if(s(i,"attribute-value")){var l=a.getCurrentTokenColumn()+i.value.length;if(n.column<l)return;if(n.column==l){var u=a.stepForward();if(u&&s(u,"attribute-value"))return;a.stepBackward()}}if(!/^\s*>/.test(o.getLine(n.row).slice(n.column))){for(;!s(i,"tag-name");)if("<"==(i=a.stepBackward()).value){i=a.stepForward();break}l=a.getCurrentTokenRow(),u=a.getCurrentTokenColumn();if(!s(a.stepBackward(),"end-tag-open")){o=i.value;if(l==n.row&&(o=o.substring(0,n.column-u)),!this.voidElements.hasOwnProperty(o.toLowerCase()))return{text:"></"+o+">",selection:[1,1]}}}}}}),this.add("autoindent","insertion",function(e,t,n,o,r){if("\n"==r){var a=n.getCursorPosition(),i=(o.getLine(a.row),new g(o,a.row,a.column)),l=i.getCurrentToken();if(l&&-1!==l.type.indexOf("tag-close")&&"/>"!=l.value){for(;l&&-1===l.type.indexOf("tag-name");)l=i.stepBackward();if(l){r=l.value,n=i.getCurrentTokenRow(),l=i.stepBackward();if(l&&-1===l.type.indexOf("end-tag")&&this.voidElements&&!this.voidElements[r]){var a=o.getTokenAt(a.row,a.column+1),u=o.getLine(n),u=this.$getIndent(u),o=u+o.getTabString();return a&&"</"===a.value?{text:"\n"+o+"\n"+u,selection:[1,o.length,1,o.length]}:{text:"\n"+o}}}}}})});o.inherits(e,r),t.XmlBehaviour=e}),define("ace/mode/folding/xml",["require","exports","module","ace/lib/oop","ace/lib/lang","ace/range","ace/mode/folding/fold_mode","ace/token_iterator"],function(e,t,n){"use strict";function u(e,t){return-1<e.type.lastIndexOf(t+".xml")}var o=e("../../lib/oop"),s=(e("../../lib/lang"),e("../../range").Range),r=e("./fold_mode").FoldMode,g=e("../../token_iterator").TokenIterator,t=t.FoldMode=function(e,t){r.call(this),this.voidElements=e||{},this.optionalEndTags=o.mixin({},this.voidElements),t&&o.mixin(this.optionalEndTags,t)};o.inherits(t,r);function i(){this.tagName="",this.closing=!1,this.selfClosing=!1,this.start={row:0,column:0},this.end={row:0,column:0}}!function(){this.getFoldWidget=function(e,t,n){var o=this._getFirstTagInLine(e,n);return o?o.closing||!o.tagName&&o.selfClosing?"markbeginend"==t?"end":"":!o.tagName||o.selfClosing||this.voidElements.hasOwnProperty(o.tagName.toLowerCase())||this._findEndTagInLine(e,n,o.tagName,o.end.column)?"":"start":this.getCommentFoldWidget(e,n)},this.getCommentFoldWidget=function(e,t){return/comment/.test(e.getState(t))&&/<!-/.test(e.getLine(t))?"start":""},this._getFirstTagInLine=function(e,t){for(var n=e.getTokens(t),o=new i,r=0;r<n.length;r++){var a=n[r];if(u(a,"tag-open")){if(o.end.column=o.start.column+a.value.length,o.closing=u(a,"end-tag-open"),!(a=n[++r]))return null;for(o.tagName=a.value,o.end.column+=a.value.length,r++;r<n.length;r++)if(a=n[r],o.end.column+=a.value.length,u(a,"tag-close")){o.selfClosing="/>"==a.value;break}return o}if(u(a,"tag-close"))return o.selfClosing="/>"==a.value,o;o.start.column+=a.value.length}return null},this._findEndTagInLine=function(e,t,n,o){for(var r=e.getTokens(t),a=0,i=0;i<r.length;i++){var l=r[i];if(!((a+=l.value.length)<o)&&(u(l,"end-tag-open")&&(l=r[i+1])&&l.value==n))return!0}return!1},this._readTagForward=function(e){var t=e.getCurrentToken();if(!t)return null;var n=new i;do{if(u(t,"tag-open"))n.closing=u(t,"end-tag-open"),n.start.row=e.getCurrentTokenRow(),n.start.column=e.getCurrentTokenColumn();else if(u(t,"tag-name"))n.tagName=t.value;else if(u(t,"tag-close"))return n.selfClosing="/>"==t.value,n.end.row=e.getCurrentTokenRow(),n.end.column=e.getCurrentTokenColumn()+t.value.length,e.stepForward(),n}while(t=e.stepForward());return null},this._readTagBackward=function(e){var t=e.getCurrentToken();if(!t)return null;var n=new i;do{if(u(t,"tag-open"))return n.closing=u(t,"end-tag-open"),n.start.row=e.getCurrentTokenRow(),n.start.column=e.getCurrentTokenColumn(),e.stepBackward(),n}while(u(t,"tag-name")?n.tagName=t.value:u(t,"tag-close")&&(n.selfClosing="/>"==t.value,n.end.row=e.getCurrentTokenRow(),n.end.column=e.getCurrentTokenColumn()+t.value.length),t=e.stepBackward());return null},this._pop=function(e,t){for(;e.length;){var n=e[e.length-1];if(!t||n.tagName==t.tagName)return e.pop();if(!this.optionalEndTags.hasOwnProperty(n.tagName))return null;e.pop()}},this.getFoldWidgetRange=function(e,t,n){var o=this._getFirstTagInLine(e,n);if(!o)return this.getCommentFoldWidget(e,n)&&e.getCommentFoldRange(n,e.getLine(n).length);var r,a=[];if(o.closing||o.selfClosing){for(var i=new g(e,n,o.end.column),l={row:n,column:o.start.column};r=this._readTagBackward(i);)if(r.selfClosing){if(!a.length)return r.start.column+=r.tagName.length+2,r.end.column-=2,s.fromPoints(r.start,r.end)}else if(r.closing)a.push(r);else if(this._pop(a,r),0==a.length)return r.start.column+=r.tagName.length+2,r.start.row==r.end.row&&r.start.column<r.end.column&&(r.start.column=r.end.column),s.fromPoints(r.start,l)}else{var i=new g(e,n,o.start.column),u={row:n,column:o.start.column+o.tagName.length+2};for(o.start.row==o.end.row&&(u.column=o.end.column);r=this._readTagForward(i);)if(r.selfClosing){if(!a.length)return r.start.column+=r.tagName.length+2,r.end.column-=2,s.fromPoints(r.start,r.end)}else if(r.closing){if(this._pop(a,r),0==a.length)return s.fromPoints(u,r.start)}else a.push(r)}}}.call(t.prototype)}),define("ace/mode/xml",["require","exports","module","ace/lib/oop","ace/lib/lang","ace/mode/text","ace/mode/xml_highlight_rules","ace/mode/behaviour/xml","ace/mode/folding/xml","ace/worker/worker_client"],function(e,t,n){"use strict";var o=e("../lib/oop"),r=e("../lib/lang"),a=e("./text").Mode,i=e("./xml_highlight_rules").XmlHighlightRules,l=e("./behaviour/xml").XmlBehaviour,u=e("./folding/xml").FoldMode,s=e("../worker/worker_client").WorkerClient,e=function(){this.HighlightRules=i,this.$behaviour=new l,this.foldingRules=new u};o.inherits(e,a),function(){this.voidElements=r.arrayToMap([]),this.blockComment={start:"\x3c!--",end:"--\x3e"},this.createWorker=function(t){var e=new s(["ace"],"ace/mode/xml_worker","Worker");return e.attachToDocument(t.getDocument()),e.on("error",function(e){t.setAnnotations(e.data)}),e.on("terminate",function(){t.clearAnnotations()}),e},this.$id="ace/mode/xml"}.call(e.prototype),t.Mode=e}),window.require(["ace/mode/xml"],function(e){"object"==typeof module&&"object"==typeof exports&&module&&(module.exports=e)});