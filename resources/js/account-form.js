document.querySelectorAll('[data-enable-password-change]').forEach(toggle=>{
 const fields=toggle.form.querySelectorAll('[data-new-password]');
 const sync=()=>fields.forEach(field=>{field.disabled=!toggle.checked;if(!toggle.checked)field.value='';});
 toggle.addEventListener('change',sync);sync();
});

document.querySelectorAll('[data-enable-email-change]').forEach(toggle=>{const field=toggle.form.querySelector('[data-edit-email]');if(!field)return;const original=field.defaultValue;const sync=()=>{field.readOnly=!toggle.checked;if(!toggle.checked)field.value=original;};toggle.addEventListener('change',sync);sync();});
