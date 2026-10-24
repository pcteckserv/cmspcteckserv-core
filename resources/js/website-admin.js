const container=document.querySelector('[data-menu-items]');
if(container){
 const update=()=>{const rows=[...container.querySelectorAll('[data-menu-item]')];rows.forEach(row=>{const select=row.querySelector('[data-item-parent]'),value=select.value||select.dataset.value||'';select.replaceChildren(new Option('Sem superior',''));rows.filter(other=>other!==row).forEach(other=>select.add(new Option(other.querySelector('[data-item-label]').value||'Ligação sem nome',other.querySelector('[data-item-id]').value)));select.value=value;select.dataset.value=select.value;});};
 const add=(label='',url='')=>{const key=crypto.randomUUID().replaceAll('-','');const template=document.querySelector('[data-menu-template]').innerHTML.replaceAll('__INDEX__',key).replaceAll('__ID__',key);container.insertAdjacentHTML('beforeend',template);const row=container.lastElementChild;row.querySelector('[data-item-label]').value=label;row.querySelector('[data-item-url]').value=url;update();row.querySelector('[data-item-label]').focus();};
 document.querySelector('[data-menu-add]').addEventListener('click',()=>add());
 document.querySelector('[data-policy-choice]').addEventListener('change',event=>{if(event.target.value)add(event.target.selectedOptions[0].text,event.target.value);event.target.value='';});
 container.addEventListener('click',event=>{if(event.target.closest('[data-menu-remove]')){event.target.closest('[data-menu-item]').remove();update();}});
 container.addEventListener('input',event=>{if(event.target.matches('[data-item-label]'))update();});
 container.addEventListener('change',event=>{if(event.target.matches('[data-item-parent]'))event.target.dataset.value=event.target.value;});update();
}
