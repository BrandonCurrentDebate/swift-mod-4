!function(e){var t={};function r(n){if(t[n])return t[n].exports;var o=t[n]={i:n,l:!1,exports:{}};return e[n].call(o.exports,o,o.exports,r),o.l=!0,o.exports}r.m=e,r.c=t,r.d=function(e,t,n){r.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:n})},r.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},r.t=function(e,t){if(1&t&&(e=r(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var n=Object.create(null);if(r.r(n),Object.defineProperty(n,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var o in e)r.d(n,o,function(t){return e[t]}.bind(null,o));return n},r.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return r.d(t,"a",t),t},r.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},r.p="",r(r.s="./src/index.js")}({"./node_modules/classnames/index.js":function(e,t,r){var n;!function(){"use strict";var r={}.hasOwnProperty;function o(){for(var e=[],t=0;t<arguments.length;t++){var n=arguments[t];if(n){var a=typeof n;if("string"===a||"number"===a)e.push(n);else if(Array.isArray(n)&&n.length){var i=o.apply(null,n);i&&e.push(i)}else if("object"===a)for(var l in n)r.call(n,l)&&n[l]&&e.push(l)}}return e.join(" ")}e.exports?(o.default=o,e.exports=o):void 0===(n=function(){return o}.apply(t,[]))||(e.exports=n)}()},"./src/index.js":function(e,t,r){"use strict";r.r(t);var n=r("@wordpress/element"),o=(r("./node_modules/classnames/index.js"),wp.i18n.__),a=wp.hooks.addFilter,i=wp.element.Fragment,l=wp.editor.InspectorAdvancedControls,u=wp.compose.createHigherOrderComponent,c=wp.components.ToggleControl;var s=u(function(e){return function(t){t.name;var r=t.attributes,a=t.setAttributes,u=t.isSelected,s=r.isSwiftPerfrormanceLazyloaded;return Object(n.createElement)(i,null,Object(n.createElement)(e,t),u&&Object(n.createElement)(l,null,Object(n.createElement)(c,{label:o(swift_performance.i18n['Swift Performance Lazyload']),checked:!!s,onChange:function(){return a({isSwiftPerfrormanceLazyloaded:!s})},help:o(s?"Lazyloaded":"Not lazyloaded.")})))}},"withAdvancedControls");a("blocks.registerBlockType","swiftperformance/custom-attributes",function(e){return void 0!==e.attributes&&(e.attributes=Object.assign(e.attributes,{isSwiftPerfrormanceLazyloaded:{type:"boolean",default:!1}})),e}),a("editor.BlockEdit","swiftperformance/custom-advanced-control",s)},"@wordpress/element":function(e,t){e.exports=window.wp.element}});