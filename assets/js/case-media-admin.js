(()=>{'use strict';
    const initialize=()=>{
        document.querySelectorAll('.acf-field[data-name$="_image_1"]').forEach(first=>{
            if(first.dataset.caseSlotsReady)return;
            const prefix=first.dataset.name.replace(/_image_1$/,'');
            const slots=Array.from({length:5},(_,i)=>document.querySelector('.acf-field[data-name="'+prefix+'_image_'+(i+1)+'"]'));
            const orderField=document.querySelector('.acf-field[data-name="'+prefix+'_media_order"] input[type="text"]');
            if(slots.some(slot=>!slot)||!orderField)return;
            first.dataset.caseSlotsReady='1';
            const saved=orderField.value.split(',').map(n=>Number(n.trim())).filter(n=>Number.isInteger(n)&&n>=1&&n<=5);
            const order=[...new Set([...saved,1,2,3,4,5])];
            const list=document.createElement('div');list.className='rn-case-media-list';first.before(list);
            order.forEach(number=>{const slot=slots[number-1];slot.dataset.mediaSlot=String(number);slot.classList.add('rn-case-media-slot');list.append(slot);});
            const ordered=()=>[...list.querySelectorAll(':scope > .rn-case-media-slot')];
            let visible=2;
            ordered().forEach((slot,i)=>{const input=slot.querySelector('.acf-image-uploader input[type="hidden"],.acf-file-uploader input[data-name="id"]');if(Number(input?.value)>0)visible=Math.max(visible,i+1);});
            const controls=document.createElement('div');controls.className='rn-case-image-controls';
            const button=document.createElement('button');button.type='button';button.className='button';button.textContent='メディア枠を追加';
            const count=document.createElement('span');count.className='rn-case-image-count';count.setAttribute('aria-live','polite');controls.append(button,count);list.after(controls);
            const update=()=>{
                ordered().forEach((slot,i)=>{
                    slot.classList.toggle('rn-case-image-hidden',i>=visible);
                    const label=slot.querySelector('.acf-label>label');if(label)label.textContent='メディア '+(i+1);
                    slot.querySelector('[data-media-move="before"]').disabled=i===0;
                    slot.querySelector('[data-media-move="after"]').disabled=i>=visible-1;
                });
                button.disabled=visible===5;count.textContent=visible+' / 5 枠'+(visible===5?'（上限）':'');
            };
            const persist=()=>{orderField.value=ordered().map(slot=>slot.dataset.mediaSlot).join(',');orderField.dispatchEvent(new Event('change',{bubbles:true}));update();};
            slots.forEach(slot=>{
                const tools=document.createElement('div');tools.className='rn-case-media-tools';
                const grip=document.createElement('button');grip.type='button';grip.className='rn-case-media-grip';grip.textContent='⋮⋮ 並び替え';grip.setAttribute('aria-label','このメディアをドラッグして並び替え');
                tools.append(grip);
                ['before','after'].forEach(direction=>{
                    const move=document.createElement('button');move.type='button';move.className='button button-small';move.dataset.mediaMove=direction;move.textContent=direction==='before'?'前へ':'後へ';
                    move.addEventListener('click',()=>{const items=ordered(),i=items.indexOf(slot);if(direction==='before'&&i>0)items[i-1].before(slot);if(direction==='after'&&i<visible-1)items[i+1].after(slot);persist();grip.focus();});tools.append(move);
                });
                slot.prepend(tools);
                const uploader=slot.querySelector('.acf-file-uploader');
                if(uploader){
                    const preview=document.createElement('div');preview.className='rn-case-media-preview';uploader.after(preview);let previous='';
                    const refresh=()=>{
                        const input=uploader.querySelector('input[data-name="id"]'),link=uploader.querySelector('[data-name="filename"]');
                        const url=Number(input?.value)>0?link?.href||'':'';if(url===previous)return;previous=url;preview.replaceChildren();
                        if(!url)return;
                        if(/\.(mp4|webm)(?:[?#]|$)/i.test(url)){const video=document.createElement('video');video.src=url;video.controls=true;video.preload='none';video.playsInline=true;preview.append(video);}
                        else if(/\.(jpe?g|png|gif|webp|avif)(?:[?#]|$)/i.test(url)){const image=document.createElement('img');image.src=url;image.alt=link?.textContent||'選択した画像';image.loading='lazy';preview.append(image);}
                    };
                    new MutationObserver(refresh).observe(uploader,{subtree:true,childList:true,attributes:true,attributeFilter:['href','class','value']});uploader.addEventListener('change',refresh);refresh();
                }
            });
            button.addEventListener('click',()=>{if(visible>=5)return;const slot=ordered()[visible++];update();slot.querySelector('[data-name="add"]')?.focus();});
            if(window.jQuery?.fn.sortable){
                jQuery(list).sortable({items:'> .rn-case-media-slot:not(.rn-case-image-hidden)',handle:'.rn-case-media-grip',cancel:'input,textarea,select,video,a,[data-media-move]',tolerance:'pointer',placeholder:'rn-case-media-placeholder',forcePlaceholderSize:true,stop:persist});
            }
            update();
        });
    };
    if(window.acf){acf.addAction('ready',initialize);acf.addAction('append',initialize);}
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initialize);else initialize();
})();
