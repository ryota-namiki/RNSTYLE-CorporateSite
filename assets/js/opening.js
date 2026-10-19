/* The MP4 stops at the scene exit; the hold and fade use the actual live DOM. */
(()=>{'use strict';
    const root=document.documentElement,overlay=document.querySelector('[data-site-opening]');
    clearTimeout(window.rnOpeningBootTimer);
    if(!overlay){root.classList.remove('rn-opening-active','rn-opening-reveal');return;}
    const video=overlay.querySelector('video'),skip=overlay.querySelector('button'),reduced=matchMedia('(prefers-reduced-motion:reduce)');
    let state='pending',loadTimer=0,holdTimer=0,fadeTimer=0,watchdog=0,locked=[];
    const setState=value=>{state=value;overlay.dataset.state=value;};
    const finish=()=>{
        if(state==='done')return;
        setState('done');[loadTimer,holdTimer,fadeTimer,watchdog].forEach(clearTimeout);
        const moveFocus=overlay.contains(document.activeElement);
        if(video){video.pause();video.querySelectorAll('source').forEach(source=>source.removeAttribute('src'));video.removeAttribute('src');video.load();}
        locked.forEach(([element,wasInert])=>{element.inert=wasInert;});locked=[];
        root.classList.remove('rn-opening-active','rn-opening-reveal');overlay.remove();
        document.removeEventListener('keydown',onKey);document.removeEventListener('focusin',onFocus);window.removeEventListener('pagehide',finish);reduced.removeEventListener('change',finish);
        if(window.rnOpeningAbort===finish)delete window.rnOpeningAbort;
        if(moveFocus){
            const main=document.getElementById('main');
            if(main){const previous=main.getAttribute('tabindex');main.setAttribute('tabindex','-1');main.focus({preventScroll:true});if(previous===null)main.addEventListener('blur',()=>main.removeAttribute('tabindex'),{once:true});else main.setAttribute('tabindex',previous);}
        }
    };
    const onKey=event=>{
        if(event.key==='Escape'){event.preventDefault();finish();}
        else if(event.key==='Tab'){event.preventDefault();if(state!=='hold'&&state!=='revealing')skip.focus({preventScroll:true});}
    };
    const onFocus=event=>{if(!overlay.contains(event.target)&&state!=='hold'&&state!=='revealing'&&state!=='done')skip.focus({preventScroll:true});};
    if(!root.classList.contains('rn-opening-active')||reduced.matches||!video||!skip){finish();return;}
    window.rnOpeningAbort=finish;
    overlay.hidden=false;setState('loading');
    locked=[...document.body.children].filter(element=>element!==overlay&&!['SCRIPT','STYLE','LINK'].includes(element.tagName)).map(element=>[element,element.inert]);
    locked.forEach(([element])=>{element.inert=true;});
    document.addEventListener('keydown',onKey);document.addEventListener('focusin',onFocus);window.addEventListener('pagehide',finish);reduced.addEventListener('change',finish);
    skip.addEventListener('click',finish);skip.focus({preventScroll:true});
    video.addEventListener('error',finish);
    video.addEventListener('playing',()=>{if(state==='loading'){clearTimeout(loadTimer);setState('playing');overlay.classList.add('is-playing');}});
    video.addEventListener('ended',()=>{
        if(state==='done'||state==='hold'||state==='revealing')return;
        clearTimeout(loadTimer);video.pause();setState('hold');overlay.classList.add('is-blank');
        holdTimer=setTimeout(()=>{
            if(state!=='hold')return;
            setState('revealing');root.classList.add('rn-opening-reveal');overlay.classList.add('is-revealing');fadeTimer=setTimeout(finish,1650);
        },1000);
    });
    // Fail open on slow downloads, stalled playback, policy blocks or missing assets.
    loadTimer=setTimeout(finish,6000);watchdog=setTimeout(finish,19000);
    video.muted=true;video.defaultMuted=true;
    video.querySelectorAll('source[data-src]').forEach(source=>{source.src=source.dataset.src;});
    video.load();const playback=video.play();if(playback?.catch)playback.catch(finish);
})();
