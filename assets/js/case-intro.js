(()=>{'use strict';
    const root=document.documentElement,entry=document.querySelector('[data-case-entry]');
    if(!entry)return;
    const gsap=window.gsap,word=entry.querySelector('[data-case-entry-word]');
    const outline=entry.querySelector('.case-entry-outline'),fill=entry.querySelector('.case-entry-fill');
    const overview=document.querySelector('.case-overview');
    if(!word||!outline||!fill)return;
    // Measure the actual font and keep fitting after animation, font loads, and resizing.
    let frame=0,lastWidth=0,timeline=null,done=false;
    const fit=()=>{
        frame=0;
        const available=entry.clientWidth;
        if(!available)return;
        word.style.fontSize='var(--case-entry-max-size)';
        const preferred=parseFloat(getComputedStyle(word).fontSize);
        const measured=word.getBoundingClientRect().width;
        if(measured>0)word.style.fontSize=(preferred*Math.min(1,Math.max(0,available-2)/measured))+'px';
        // Guard against subpixel rounding and font-specific shaping.
        const actual=word.getBoundingClientRect().width;
        if(actual>available)word.style.fontSize=(parseFloat(getComputedStyle(word).fontSize)*available/actual*.999)+'px';
    };
    const scheduleFit=()=>{if(!frame)frame=requestAnimationFrame(fit);};
    fit();
    window.addEventListener('resize',scheduleFit,{passive:true});
    if(window.ResizeObserver)new ResizeObserver(records=>{
        const width=records[0].contentRect.width;
        if(Math.abs(width-lastWidth)>.1){lastWidth=width;scheduleFit();}
    }).observe(entry);
    if(document.fonts?.ready)document.fonts.ready.then(scheduleFit).catch(()=>{});
    document.fonts?.addEventListener?.('loadingdone',scheduleFit);
    const clean=()=>{
        if(done)return;done=true;
        timeline?.kill();
        root.classList.remove('case-entry-pending');
        if(gsap){
            gsap.set([outline,fill],{clearProps:'opacity,clipPath'});
            if(overview)gsap.set(overview,{clearProps:'opacity,transform'});
        }
        document.dispatchEvent(new CustomEvent('rnstyle:case-entry-complete'));
    };
    const finish=()=>{root.dataset.caseEntryState='complete';clean();};
    document.addEventListener('rnstyle:case-entry-release',clean,{once:true});
    if(entry.dataset.entryEnabled==='0'){
        root.dataset.caseEntryState='static';clean();return;
    }
    if(!root.classList.contains('case-entry-pending'))return;
    if(!gsap){root.dataset.caseEntryState='unavailable';clean();return;}
    const start=()=>{
        if(done||!root.classList.contains('case-entry-pending'))return;
        fit();root.dataset.caseEntryState='playing';
        gsap.set(outline,{opacity:0});gsap.set(fill,{clipPath:'inset(0 100% 0 0)'});
        if(overview)gsap.set(overview,{opacity:0,y:16});
        // Match the reference site's timeline and easing exactly.
        timeline=gsap.timeline({delay:.3,onComplete:finish});
        timeline.to(outline,{opacity:1,duration:.6,ease:'power2.out'})
            .to(fill,{clipPath:'inset(0 0% 0 0)',duration:1.32,ease:'power2.inOut'})
            .to(outline,{opacity:0,duration:.3},'-=0.1');
        if(overview)timeline.to(overview,{opacity:1,y:0,duration:.8,ease:'power2.out'},'>0.3');
        entry.dataset.caseEntryDuration=String(Number((timeline.duration()+timeline.delay()).toFixed(2)));
    };
    if(document.fonts?.ready){
        Promise.race([document.fonts.ready,new Promise(resolve=>setTimeout(resolve,150))]).then(start).catch(start);
    }else start();
})();
