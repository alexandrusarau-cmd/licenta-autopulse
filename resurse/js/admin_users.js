document.addEventListener('DOMContentLoaded', function(){

    function post(data){
        return fetch('admin_users.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: new URLSearchParams(data).toString()
        }).then(r => r.text());
    }

    document.querySelectorAll('.edit-icon').forEach(icon => {
        icon.addEventListener('click', function(e){
            e.stopPropagation();
            const span = this.previousElementSibling;
            if(!span || !span.classList.contains('editable')) return;

            const field = span.dataset.field;
            const id = span.dataset.id;
            const oldValue = span.textContent.trim();

            if(span.querySelector('input') || span.querySelector('select')) return;

            let control;
            if(field === 'role'){
                control = document.createElement('select');
                ['administrator','client'].forEach(opt => {
                    const o = document.createElement('option');
                    o.value = opt;
                    o.textContent = opt;
                    if(opt === oldValue) o.selected = true;
                    control.appendChild(o);
                });
            } else {
                control = document.createElement('input');
                control.type = 'text';
                control.value = oldValue;
            }

            span.textContent = '';
            span.appendChild(control);
            control.focus();

            function save(){
                const newValue = control.value.trim();
                if(newValue === oldValue){
                    span.textContent = oldValue;
                    return;
                }
                post({action:'update',id:id,field:field,value:newValue})
                .then(res => {
                    span.textContent = res.trim() === 'ok' ? newValue : oldValue;
                    if(res.trim() !== 'ok') alert('Eroare la salvare: ' + res);
                });
            }

            if(field === 'role'){
                control.addEventListener('change', save);
                control.addEventListener('blur', function(){
                    if(!span.textContent) span.textContent = oldValue;
                });
            } else {
                control.addEventListener('blur', save);
                control.addEventListener('keydown', function(e){
                    if(e.key === 'Enter'){ e.preventDefault(); control.blur(); }
                    if(e.key === 'Escape'){ span.textContent = oldValue; }
                });
            }
        });
    });

    document.addEventListener('click', function(e){
        document.querySelectorAll('.editable').forEach(span => {
            const control = span.querySelector('input') || span.querySelector('select');
            if(control){
                const icon = span.nextElementSibling;
                if(icon && icon.contains(e.target)) return;
                if(control.contains(e.target)) return;

                span.textContent = control.value;
            }
        });
    });

    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function(){
            const id = this.dataset.id;
            if(!id) return;
            if(!confirm('Sigur stergi utilizatorul cu ID ' + id + ' ?')) return;

            post({action:'delete',id:id})
            .then(res => {
                if(res.trim() === 'ok'){
                    const tr = this.closest('tr');
                    if(tr) tr.remove();
                } else {
                    alert('Eroare la stergere: ' + res);
                }
            }).catch(err => alert('Eroare retea: ' + err));
        });
    });

	const addUserBtn = document.getElementById('addUserBtn');
	const userModal = document.getElementById('userModal');
	const saveUser = document.getElementById('saveUser');

	if(addUserBtn){
		addUserBtn.addEventListener('click', () => {
			userModal.style.display = 'block';
		});
	}

	if(saveUser){
		saveUser.addEventListener('click', () => {
			const data = {
				action: 'add',
				username: document.getElementById('username').value.trim(),
				email: document.getElementById('email').value.trim(),
				password: document.getElementById('password').value.trim(),
				role: document.getElementById('role').value
			};

			if(!data.username || !data.email || !data.password){
				alert("Completeaza toate campurile obligatorii!");
				return;
			}

			fetch('admin_users.php', {
				method: 'POST',
				headers: {'Content-Type':'application/x-www-form-urlencoded'},
				body: new URLSearchParams(data).toString()
			})
			.then(r => r.text())
			.then(res => {
				if(res.trim() === 'ok'){
					location.reload();
				} else {
					alert('Eroare: ' + res);
				}
			})
			.catch(err => alert('Eroare retea: ' + err));
		});
	}
});
