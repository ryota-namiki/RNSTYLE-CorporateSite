/* Fail open if fonts, the animation library, or the page script cannot load. */
(()=>{'use strict';
    const root=document.documentElement;
    const reduced=window.matchMedia?.('(prefers-reduced-motion: reduce)');
    const navigation=window.performance?.getEntriesByType?.('navigation')[0];
    if(reduced?.matches||navigation?.type==='back_forward'||location.hash){root.dataset.caseEntryState='skipped';return;}
    root.classList.add('case-entry-pending');root.dataset.caseEntryState='waiting';
    const release=reason=>{
        if(!root.classList.contains('case-entry-pending'))return;
        root.classList.remove('case-entry-pending');root.dataset.caseEntryState=reason;
        document.dispatchEvent(new CustomEvent('rnstyle:case-entry-release'));
    };
    const timer=setTimeout(()=>release('timeout'),6000);
    document.addEventListener('rnstyle:case-entry-complete',()=>clearTimeout(timer),{once:true});
    window.addEventListener('pagehide',()=>release('skipped'));
    window.addEventListener('pageshow',event=>{if(event.persisted)release('skipped');});
    window.addEventListener('pointerdown',()=>release('skipped'),{once:true,capture:true,passive:true});
    window.addEventListener('wheel',()=>release('skipped'),{once:true,capture:true,passive:true});
    window.addEventListener('touchmove',()=>release('skipped'),{once:true,capture:true,passive:true});
    window.addEventListener('keydown',event=>{if(['Escape','Tab','PageDown','ArrowDown',' '].includes(event.key))release('skipped');},true);
    reduced?.addEventListener?.('change',event=>{if(event.matches)release('skipped');});
})();
