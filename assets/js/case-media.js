(()=>{'use strict';
    document.querySelectorAll('[data-case-media]').forEach(gallery=>{
        const track=gallery.querySelector('.case-media-track'),dots=[...gallery.querySelectorAll('.case-media-dot')];
        if(!track||dots.length<2)return;
        const slides=[...track.children],reduced=matchMedia('(prefers-reduced-motion:reduce)');
        const position=i=>slides[i].offsetLeft-slides[0].offsetLeft;
        let active=0,frame=0,drag=null,settling=0,navigation=false;
        const update=()=>{
            frame=0;let best=Infinity;
            slides.forEach((slide,i)=>{const distance=Math.abs(track.scrollLeft-position(i));if(distance<best){best=distance;active=i;}});
            dots.forEach((dot,i)=>dot.setAttribute('aria-current',String(i===active)));
            slides.forEach((slide,i)=>{if(i!==active)slide.querySelectorAll('video').forEach(video=>{if(!video.paused)video.pause();});});
            const height=slides[active].getBoundingClientRect().height;
            if(height>0)track.style.height=height+'px';
        };
        const schedule=()=>{if(!frame)frame=requestAnimationFrame(update);};
        const settle=()=>{clearTimeout(settling);settling=setTimeout(()=>{navigation=false;gallery.scrollIntoView({block:'nearest',behavior:'instant'});},150);};
        const go=i=>{navigation=true;track.scrollTo({left:position(Math.max(0,Math.min(slides.length-1,i))),behavior:reduced.matches?'auto':'smooth'});settle();};
        dots.forEach((dot,i)=>dot.addEventListener('click',()=>go(i)));
        track.addEventListener('scroll',()=>{schedule();if(navigation)settle();},{passive:true});
        track.querySelectorAll('img').forEach(image=>image.addEventListener('load',schedule));
        track.querySelectorAll('video').forEach(video=>video.addEventListener('loadedmetadata',schedule));
        track.addEventListener('keydown',event=>{
            if(event.target!==track)return;
            const keys={ArrowRight:active+1,ArrowLeft:active-1,Home:0,End:slides.length-1};
            if(event.key in keys){event.preventDefault();go(keys[event.key]);}
        });
        track.addEventListener('pointerdown',event=>{
            if(event.pointerType!=='mouse'||event.button!==0||event.target.closest('video,a,button,input'))return;
            drag={id:event.pointerId,x:event.clientX,left:track.scrollLeft,moved:false};
        });
        track.addEventListener('pointermove',event=>{
            if(!drag||event.pointerId!==drag.id)return;
            const distance=event.clientX-drag.x;
            if(!drag.moved&&Math.abs(distance)<6)return;
            if(!drag.moved){drag.moved=true;track.setPointerCapture(event.pointerId);track.classList.add('is-dragging');}
            event.preventDefault();track.scrollLeft=drag.left-distance;
        });
        const release=event=>{
            if(!drag||event.pointerId!==drag.id)return;
            const moved=drag.moved;drag=null;track.classList.remove('is-dragging');
            if(track.hasPointerCapture(event.pointerId))track.releasePointerCapture(event.pointerId);
            if(moved){update();go(active);}
        };
        track.addEventListener('pointerup',release);track.addEventListener('pointercancel',release);
        track.addEventListener('pointerleave',event=>{if(drag&&!drag.moved)release(event);});
        window.addEventListener('resize',schedule,{passive:true});
        if(window.ResizeObserver)new ResizeObserver(schedule).observe(track);
        update();
    });
})();
