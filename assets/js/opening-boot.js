/* Home only. When JS, video or motion is unavailable the real page stays usable. */
(()=>{'use strict';
    try{
        const navigation=performance.getEntriesByType('navigation')[0];
        if(location.hash||navigation?.type==='back_forward'||matchMedia('(prefers-reduced-motion:reduce)').matches||navigator.connection?.saveData)return;
        // Claim the opening before hiding the page, including reloads during playback.
        // If session storage is unavailable, leave the live content visible.
        const sessionKey='rnstyle-opening-seen';
        if(sessionStorage.getItem(sessionKey)==='1')return;
        sessionStorage.setItem(sessionKey,'1');
        const root=document.documentElement;
        root.classList.add('rn-opening-active');
        window.rnOpeningBootTimer=setTimeout(()=>{
            if(window.rnOpeningAbort){window.rnOpeningAbort();return;}
            root.classList.remove('rn-opening-active','rn-opening-reveal');
            document.querySelector('[data-site-opening]')?.remove();
        },6000);
    }catch{document.documentElement.classList.remove('rn-opening-active');}
})();
