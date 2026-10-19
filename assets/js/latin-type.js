/* Standalone Latin labels use the webfont; mixed Japanese sentences retain their type. */
(()=>{'use strict';
    const mark='rn-standalone-latin';
    const excluded='script,style,noscript,svg,textarea,input,select,option,code,pre,[contenteditable],.screen-reader-text,#wpadminbar,.eyebrow,.rn-service-page';
    const sentence='p,h1,h2,h3,h4,h5,h6,li,dt,dd,label,legend,button,figcaption,.orbit-card-enter,.orbit-view-toggle,.work-preview-enter,.case-all-works,.number,time';
    const japanese=/[\p{Script=Han}\p{Script=Hiragana}\p{Script=Katakana}]/u;
    const latin=/[A-Za-z0-9]/;
    const textOf=element=>{
        const walker=document.createTreeWalker(element,NodeFilter.SHOW_TEXT,{acceptNode:node=>node.parentElement?.closest('svg,.screen-reader-text,script,style')?NodeFilter.FILTER_REJECT:NodeFilter.FILTER_ACCEPT});
        let text='',node;while((node=walker.nextNode()))text+=node.nodeValue;return text;
    };
    const refresh=()=>{
        document.querySelectorAll('body *').forEach(element=>{
            if(element.closest(excluded)){element.classList.remove(mark);return;}
            const direct=[...element.childNodes].filter(node=>node.nodeType===Node.TEXT_NODE).map(node=>node.nodeValue).join('');
            const context=element.closest(sentence)||element.closest('a')||element;
            const eligible=latin.test(direct)&&!japanese.test(textOf(element))&&!japanese.test(textOf(context));
            element.classList.toggle(mark,eligible);
        });
    };
    refresh();
    let pending=false;
    new MutationObserver(()=>{
        if(pending)return;pending=true;
        requestAnimationFrame(()=>{pending=false;refresh();});
    }).observe(document.body,{childList:true,characterData:true,subtree:true});
})();
