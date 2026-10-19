/* RNSTYLE gallery 1.1.0 */
(()=>{'use strict';
const toggle=document.querySelector('.menu-toggle'),nav=document.querySelector('#mobile-nav');
if(toggle&&nav){const setOpen=open=>{toggle.setAttribute('aria-expanded',String(open));toggle.querySelector('.screen-reader-text').textContent=open?'メニューを閉じる':'メニューを開く';nav.hidden=!open;document.body.classList.toggle('menu-open',open);if(open)nav.querySelector('a')?.focus();};toggle.addEventListener('click',()=>setOpen(toggle.getAttribute('aria-expanded')!=='true'));nav.addEventListener('click',e=>{if(e.target.closest('a'))setOpen(false);});document.addEventListener('keydown',e=>{if(nav.hidden)return;if(e.key==='Escape'){setOpen(false);toggle.focus();}if(e.key==='Tab'){const items=[toggle,...nav.querySelectorAll('a,button')];const i=items.indexOf(document.activeElement);if(e.shiftKey&&i===0){e.preventDefault();items.at(-1).focus();}else if(!e.shiftKey&&i===items.length-1){e.preventDefault();toggle.focus();}}});matchMedia('(min-width:768px)').addEventListener('change',e=>{if(e.matches)setOpen(false);});}
document.querySelectorAll('.motion-toggle').forEach(motion=>motion.addEventListener('click',()=>{const paused=document.body.classList.toggle('motion-paused');document.querySelectorAll('.motion-toggle').forEach(b=>{b.setAttribute('aria-pressed',String(paused));b.textContent=paused?'動きを再開する':'動きを止める';});}));
// One published-project collection for the ring, grid, filters and preview.
document.querySelectorAll('.works-orbit').forEach(orbit=>{
    const plane=orbit.querySelector('.orbit-plane'),cards=[...orbit.querySelectorAll('.orbit-art')];
    if(!plane)return;
    const reduced=matchMedia('(prefers-reduced-motion:reduce)'),filters=[...orbit.querySelectorAll('[data-work-filter]')];
    const footer=orbit.querySelector('.orbit-footer'),viewToggle=orbit.querySelector('[data-orbit-view]'),empty=orbit.querySelector('.orbit-empty'),result=orbit.querySelector('.orbit-result-count');
    const dialog=orbit.querySelector('.work-preview'),turn=Math.PI*2,wrap=value=>((value%turn)+turn)%turn;
    let active=[],angle=0,velocity=0,target=null,frame=0,lastFrame=0,pointer=null,radiusX=1,suppressClickUntil=0,opener=null;
    let grid=orbit.classList.contains('is-grid'),selected=grid?'':orbit.dataset.filter||'';
    const step=()=>turn/Math.max(1,active.length);
    const announce=()=>{if(result)result.textContent=`${active.length}件の制作実績`;};
    const draw=()=>{
        angle=wrap(angle);
        if(grid)return;
        active.forEach((card,index)=>{
            const theta=angle-index*step(),y=Math.cos(theta),depth=(y+1)/2;
            card.style.setProperty('--orbit-x',(active.length>1?Math.sin(theta):0).toFixed(6));
            card.style.setProperty('--orbit-y',(active.length>1?y:0).toFixed(6));
            card.style.setProperty('--orbit-scale',(.7+.38*depth).toFixed(6));
            card.style.setProperty('--orbit-opacity',(.75+.25*depth).toFixed(6));
            card.style.setProperty('--orbit-brightness',(.4+.6*depth).toFixed(6));
            card.style.setProperty('--orbit-saturation',(.6+.4*depth).toFixed(6));
            card.style.zIndex=String(Math.round(10+80*depth));
        });
    };
    const stop=()=>{cancelAnimationFrame(frame);frame=0;lastFrame=0;velocity=0;target=null;};
    const tick=time=>{
        const elapsed=lastFrame?Math.min(32,time-lastFrame):16.67;lastFrame=time;
        if(target!==null){
            const difference=Math.atan2(Math.sin(target-angle),Math.cos(target-angle));
            angle+=difference*(1-Math.exp(-elapsed*.014));
            if(Math.abs(difference)<.001){angle=target;target=null;}
        }else{
            angle+=velocity*elapsed;velocity*=Math.exp(-elapsed*.005);
            if(Math.abs(velocity)<.000015)velocity=0;
        }
        draw();
        if(target!==null||velocity)frame=requestAnimationFrame(tick);
        else{frame=0;lastFrame=0;}
    };
    const animate=()=>{if(!frame){lastFrame=0;frame=requestAnimationFrame(tick);}};
    const select=next=>{stop();if(reduced.matches){angle=next;draw();}else{target=wrap(next);animate();}};
    const resize=()=>{
        radiusX=Math.min(500,plane.clientWidth*.33);
        plane.style.setProperty('--orbit-radius-x',`${radiusX}px`);
        plane.style.setProperty('--orbit-radius-y',`${Math.min(130,plane.clientHeight*.17)}px`);
    };
    if(typeof ResizeObserver!=='undefined')new ResizeObserver(resize).observe(plane);
    const makeURL=(tag,isGrid)=>{
        const url=new URL(location.href);
        ['work_tag','tag','view','category','work_page'].forEach(key=>url.searchParams.delete(key));
        if(tag)url.searchParams.set('work_tag',tag);
        if(isGrid)url.searchParams.set('view','grid');
        return url;
    };
    const syncControls=()=>{
        // Keep keyboard order consistent with the visible controls above the grid.
        if(footer&&(grid?footer.nextElementSibling!==plane:plane.nextElementSibling!==footer)){
            const focused=footer.contains(document.activeElement)?document.activeElement:null;
            orbit.insertBefore(footer,grid?plane:plane.nextSibling);
            focused?.focus({preventScroll:true});
        }
        filters.forEach(link=>{
            const current=link.dataset.workFilter===selected;
            if(current)link.setAttribute('aria-current','true');else link.removeAttribute('aria-current');
            link.href=makeURL(link.dataset.workFilter,grid).href;
        });
        if(viewToggle){
            viewToggle.replaceChildren(document.createTextNode(grid?'ORBIT VIEW ':'GRID VIEW '));
            const arrow=document.createElement('span');arrow.setAttribute('aria-hidden','true');arrow.textContent='↗';viewToggle.append(arrow);
            viewToggle.setAttribute('aria-label',grid?'回転表示に切り替え':'グリッド表示に切り替え');
            viewToggle.href=makeURL(selected,!grid).href;
        }
        plane.setAttribute('aria-label',grid?'制作実績のグリッド':'制作実績。左右にドラッグして回転');
    };
    const filter=key=>{
        stop();selected=key;angle=0;
        cards.forEach(card=>{card.hidden=!!key&&!card.dataset.tags.split(' ').includes(key);});
        active=cards.filter(card=>!card.hidden);
        if(empty)empty.hidden=active.length>0;
        orbit.classList.toggle('is-empty',active.length===0);
        syncControls();resize();draw();announce();
    };
    const plainClick=event=>event.button===0&&!event.metaKey&&!event.ctrlKey&&!event.shiftKey&&!event.altKey;
    filters.forEach(link=>link.addEventListener('click',event=>{
        if(!plainClick(event))return;
        event.preventDefault();filter(link.dataset.workFilter);history.replaceState(null,'',makeURL(selected,grid));
    }));
    viewToggle?.addEventListener('click',event=>{
        if(!plainClick(event))return;
        event.preventDefault();stop();grid=!grid;orbit.classList.toggle('is-grid',grid);
        if(grid)filter('');else{syncControls();resize();draw();}history.replaceState(null,'',makeURL(selected,grid));
        if(grid)footer?.scrollIntoView({block:'start',behavior:reduced.matches?'instant':'smooth'});
    });
    let previewAnimations=[],previewClosing=false;
    const clearPreviewMotion=()=>{previewAnimations.forEach(animation=>animation.cancel());previewAnimations=[];};
    const animatePreview=(element,frames,options)=>{
        if(!element||reduced.matches||typeof element.animate!=='function')return null;
        const animation=element.animate(frames,{fill:'both',...options});previewAnimations.push(animation);return animation;
    };
    const previewOrigin=card=>{
        const rect=(card?.querySelector('.orbit-media')||card)?.getBoundingClientRect();
        return rect&&rect.width&&rect.height?rect:null;
    };
    const imageTransform=(source,image)=>{
        const rect=image.getBoundingClientRect();
        return source&&rect.width&&rect.height?`translate(${source.left-rect.left}px,${source.top-rect.top}px) scale(${source.width/rect.width},${source.height/rect.height})`:'scale(.94)';
    };
    const closePreview=()=>{
        if(!dialog?.open||previewClosing)return;
        previewClosing=true;clearPreviewMotion();
        if(reduced.matches||typeof dialog.animate!=='function'){dialog.close();return;}
        dialog.classList.add('is-closing');
        const image=dialog.querySelector('.work-preview-image'),copy=dialog.querySelector('.work-preview-copy');
        const source=previewOrigin(opener),destination=imageTransform(source,image);
        animatePreview(copy,[{opacity:1,transform:'none'},{opacity:0,transform:'translateX(-26vw)'}],{duration:400,easing:'cubic-bezier(.55,.055,.675,.19)'});
        animatePreview(dialog.querySelector('.work-preview-close'),[{opacity:1},{opacity:0}],{duration:200});
        const last=animatePreview(image,[{opacity:1,transform:'none'},{opacity:source?1:0,transform:destination}],{duration:600,delay:200,easing:'cubic-bezier(.645,.045,.355,1)'});
        if(last)last.finished.then(()=>{if(previewClosing)dialog.close();}).catch(()=>{});else dialog.close();
    };
    const preview=card=>{
        if(!dialog||typeof dialog.showModal!=='function')return false;
        let data;try{data=JSON.parse(card.dataset.preview);}catch{return false;}
        const wasOpen=dialog.open,source=previewOrigin(card);
        clearPreviewMotion();previewClosing=false;dialog.classList.remove('is-closing');
        stop();opener=card;
        dialog.querySelector('h2').textContent=data.title;
        dialog.querySelector('.work-preview-meta').textContent=[data.sample?'SAMPLE':'WORK',data.year].filter(Boolean).join(' / ');
        dialog.querySelector('.work-preview-intro').textContent=data.intro||'';
        const imageBox=dialog.querySelector('.work-preview-image');imageBox.replaceChildren();
        if(data.image){const img=document.createElement('img');img.src=data.image;img.alt=data.alt||data.title;imageBox.append(img);}
        else{const label=document.createElement('div');label.className='orbit-image-placeholder';label.textContent=data.title;imageBox.append(label);}
        const tags=dialog.querySelector('.work-preview-tags');tags.replaceChildren();
        (data.tags||[]).forEach(tag=>{const item=document.createElement('li');item.textContent=tag;tags.append(item);});
        dialog.querySelector('.work-preview-enter').href=card.href;
        dialog.querySelector('.work-preview-navigation').hidden=active.length<2;
        if(!wasOpen){dialog.showModal();document.body.classList.add('work-preview-open');dialog.querySelector('.work-preview-close').focus({preventScroll:true});}
        const image=dialog.querySelector('.work-preview-image'),copy=dialog.querySelector('.work-preview-copy');
        if(wasOpen){
            animatePreview(image,[{opacity:0,transform:'translateX(80px)'},{opacity:1,transform:'none'}],{duration:500,delay:120,easing:'cubic-bezier(.165,.84,.44,1)'});
            animatePreview(copy,[{opacity:0},{opacity:1}],{duration:250,delay:180});
        }else{
            animatePreview(image,[{opacity:1,transform:imageTransform(source,image)},{opacity:1,transform:'none'}],{duration:550,delay:150,easing:'cubic-bezier(.165,.84,.44,1)'});
            const copyRect=copy.getBoundingClientRect(),offset=source?source.left+source.width/2-copyRect.left:-80;
            animatePreview(copy,[{opacity:0,transform:`translateX(${offset}px)`},{opacity:1,transform:'none'}],{duration:450,delay:400,easing:'cubic-bezier(.165,.84,.44,1)'});
            animatePreview(dialog.querySelector('.work-preview-close'),[{opacity:0},{opacity:1}],{duration:250,delay:400});
        }
        return true;
    };
    dialog?.querySelectorAll('[data-preview-step]').forEach(button=>button.addEventListener('click',()=>{
        const index=active.indexOf(opener),next=(index+Number(button.dataset.previewStep)+active.length)%active.length;
        if(active[next])preview(active[next]);
    }));
    plane.addEventListener('click',event=>{
        if(Date.now()<suppressClickUntil){event.preventDefault();return;}
        const card=event.target.closest('.orbit-art');
        if(card&&plainClick(event)&&preview(card))event.preventDefault();
    });
    dialog?.querySelector('.work-preview-close').addEventListener('click',closePreview);
    dialog?.addEventListener('click',event=>{
        if(event.target!==dialog)return;
        const rect=dialog.getBoundingClientRect();
        if(event.clientX<rect.left||event.clientX>rect.right||event.clientY<rect.top||event.clientY>rect.bottom)closePreview();
    });
    dialog?.addEventListener('cancel',event=>{event.preventDefault();closePreview();});
    dialog?.addEventListener('close',()=>{
        previewClosing=false;clearPreviewMotion();dialog.classList.remove('is-closing');
        document.body.classList.remove('work-preview-open');
        if(opener?.isConnected&&!opener.hidden)opener.focus({preventScroll:true});else plane.focus({preventScroll:true});
    });
    plane.addEventListener('dragstart',event=>{if(!grid)event.preventDefault();});
    plane.addEventListener('pointerdown',event=>{
        if(grid||active.length<2||!event.isPrimary||(event.pointerType==='mouse'&&event.button!==0))return;
        stop();pointer={id:event.pointerId,x:event.clientX,y:event.clientY,lastX:event.clientX,time:event.timeStamp,moved:false};
    });
    plane.addEventListener('pointermove',event=>{
        if(!pointer||event.pointerId!==pointer.id)return;
        const dx=event.clientX-pointer.x,dy=event.clientY-pointer.y;
        if(!pointer.moved){
            if(event.pointerType!=='mouse'&&Math.abs(dy)>Math.abs(dx)&&Math.abs(dy)>6){pointer=null;return;}
            if(Math.abs(dx)<6)return;
            pointer.moved=true;plane.setPointerCapture(event.pointerId);plane.classList.add('is-dragging');plane.focus({preventScroll:true});
        }
        event.preventDefault();
        const delta=(event.clientX-pointer.lastX)/Math.max(1,radiusX),elapsed=Math.max(8,event.timeStamp-pointer.time);
        velocity=Math.max(-.006,Math.min(.006,delta/elapsed));
        angle+=delta;pointer.lastX=event.clientX;pointer.time=event.timeStamp;draw();
    });
    const release=event=>{
        if(!pointer||event.pointerId!==pointer.id)return;
        const dragged=pointer.moved,stale=event.timeStamp-pointer.time>100;
        pointer=null;plane.classList.remove('is-dragging');
        if(plane.hasPointerCapture(event.pointerId))plane.releasePointerCapture(event.pointerId);
        if(event.type==='pointercancel'||event.type==='lostpointercapture'||reduced.matches||stale)velocity=0;
        if(dragged){suppressClickUntil=Date.now()+300;if(velocity)animate();}
    };
    ['pointerup','pointercancel','lostpointercapture'].forEach(type=>plane.addEventListener(type,release));
    plane.addEventListener('pointerleave',()=>{if(pointer&&!pointer.moved)pointer=null;});
    plane.addEventListener('focusin',event=>{
        if(grid||pointer)return;
        const index=active.indexOf(event.target.closest('.orbit-art'));
        if(index>=0)select(index*step());
    });
    plane.addEventListener('keydown',event=>{
        if(grid||!active.length||event.altKey||event.ctrlKey||event.metaKey)return;
        if(event.key==='ArrowLeft'||event.key==='ArrowRight'){
            event.preventDefault();select((Math.round((target??angle)/step())+(event.key==='ArrowRight'?1:-1))*step());
        }else if(event.key==='Home'||event.key==='End'){
            event.preventDefault();select(event.key==='Home'?0:(active.length-1)*step());
        }
    });
    plane.addEventListener('wheel',event=>{
        if(grid||active.length<2)return;
        const delta=Math.abs(event.deltaX)>Math.abs(event.deltaY)?event.deltaX:(event.shiftKey?event.deltaY:0);
        if(!delta)return;
        event.preventDefault();stop();angle-=delta*(event.deltaMode===1?16:1)/Math.max(1,radiusX);draw();
    },{passive:false});
    reduced.addEventListener('change',()=>{stop();draw();});
    document.addEventListener('visibilitychange',()=>{if(document.hidden)stop();});
    if(window.ResizeObserver)new ResizeObserver(resize).observe(plane);else window.addEventListener('resize',resize);
    filter(selected);orbit.classList.add('is-ready');
});
document.querySelectorAll('.fan-carousel').forEach(carousel=>{const track=carousel.querySelector('.carousel-track');carousel.querySelectorAll('[data-carousel]').forEach(button=>button.addEventListener('click',()=>{track.scrollBy({left:(button.dataset.carousel==='next'?1:-1)*(track.querySelector('.carousel-slide').getBoundingClientRect().width+40),behavior:matchMedia('(prefers-reduced-motion:reduce)').matches?'instant':'smooth'});}));});
const form=document.querySelector('.contact-form');if(form){let pending=false;form.addEventListener('submit',async e=>{if(!window.fetch||!window.FormData)return;e.preventDefault();if(pending||!form.reportValidity())return;pending=true;const button=form.querySelector('[type=submit]'),status=form.querySelector('.form-status');button.disabled=true;button.textContent='送信中…';form.querySelectorAll('.field-error').forEach(el=>el.textContent='');form.querySelectorAll('[aria-invalid]').forEach(el=>el.removeAttribute('aria-invalid'));status.classList.remove('is-error');status.textContent='送信しています。';const data=new FormData(form);data.set('rn_ajax','1');try{const response=await fetch(form.getAttribute('action'),{method:'POST',body:data,credentials:'same-origin',headers:{'Accept':'application/json'}});const result=await response.json();status.textContent=result.message;if(!response.ok||!result.success){status.classList.add('is-error');Object.entries(result.errors||{}).forEach(([key,messages])=>{const field=form.elements.namedItem(key),error=document.getElementById(`error-${key}`);if(field)field.setAttribute('aria-invalid','true');if(error)error.textContent=messages.join(' ');});status.focus();}else{form.reset();const success=document.createElement('div');success.className='form-success';success.tabIndex=-1;success.setAttribute('role','status');const heading=document.createElement('h2');heading.textContent='お問い合わせありがとうございます。';const message=document.createElement('p');message.textContent=result.message;success.append(heading,message);form.replaceWith(success);success.focus();}}catch{status.classList.add('is-error');status.textContent='通信を確認できませんでした。入力内容は保持されています。送信が完了している場合もあるため、時間をおいてからご確認ください。';status.focus();}finally{pending=false;button.disabled=false;button.textContent='送信する';}});}
})();
