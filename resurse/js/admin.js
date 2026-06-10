function postFormData(formData) {
    return fetch('admin_cars.php', {
        method: 'POST',
        body: formData  
    }).then(r => r.text());
}
document.addEventListener('DOMContentLoaded', function() {

    function post(data){
        return fetch('admin_cars.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
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

        if(field === 'status'){
            control = document.createElement('select');
            ['disponibil','inchiriat','vandut','in service','de vanzare'].forEach(opt => {
                const o = document.createElement('option');
                o.value = opt;
                o.textContent = opt;
                if(opt === oldValue) o.selected = true;
                control.appendChild(o);
            });

        } else if(field === 'vizibil'){
            control = document.createElement('select');
            
            const optDa = document.createElement('option');
            optDa.value = '1';
            optDa.textContent = 'Da';
            if(oldValue.includes('Da') || oldValue == '1') optDa.selected = true;
            control.appendChild(optDa);

            const optNu = document.createElement('option');
            optNu.value = '0';
            optNu.textContent = 'Nu';
            if(oldValue.includes('Nu') || oldValue == '0') optNu.selected = true;
            control.appendChild(optNu);

        } else {
            control = document.createElement('input');
            control.type = (field.indexOf('pret') === 0) ? 'number' : 'text';
            if(control.type === 'number') control.step = '0.01';
            control.value = oldValue;
        }

        span.textContent = '';
        span.appendChild(control);
        control.focus();

        function save(){
            let newValue = (field === 'status' || field === 'vizibil') ? control.value : control.value.trim();
            if(newValue === oldValue){
                span.textContent = oldValue;
                return;
            }
            post({ action: 'update', id, field, value: newValue })
            .then(res => {
                if(res.trim() === 'ok'){
                    if(field === 'pret_promo'){
                        span.textContent = (newValue === '' || newValue === '0' || newValue === '0.00') ? '-' : parseFloat(newValue).toFixed(2);
                    } else if(field === 'vizibil'){
                        span.textContent = newValue == '1' ? 'Da' : 'Nu';
                    } else {
                        span.textContent = newValue;
                    }
                } else {
                    span.textContent = oldValue;
                }
            });
        }

        if(field === 'status' || field === 'vizibil'){
            control.addEventListener('change', save);
        } else {
            control.addEventListener('blur', save);
        }
    });
});

    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function(){
            const id = this.dataset.id;
            if(!confirm('Sigur stergi masina cu ID ' + id + '?')) return;

            post({ action: 'delete', id })
            .then(res => {
                if(res.trim() === 'ok'){
                    this.closest('tr').remove();
                }
            });
        });
    });
	document.querySelectorAll('.details-btn').forEach(btn => {
		btn.addEventListener('click', () => {
			const id = btn.dataset.id;

			fetch('admin_cars.php', {
				method: 'POST',
				headers: {'Content-Type': 'application/x-www-form-urlencoded'},
				body: new URLSearchParams({ action: 'details', id })
			})
			.then(res => res.json())
			.then(data => {
				document.getElementById('detaliiTitlu').textContent = `${data.marca} ${data.model} (${data.an})`;
				document.getElementById('detaliiField').value = data.detalii || '';
				document.getElementById('detailsModal').style.display = 'block';

				document.getElementById('saveDetaliiBtn').onclick = () => {
					fetch('admin_cars.php', {
						method: 'POST',
						headers: {'Content-Type':'application/x-www-form-urlencoded'},
						body: new URLSearchParams({ action: 'update_details', id, detalii: document.getElementById('detaliiField').value })
					})
					.then(res => res.text())
					.then(res => {
						if(res.trim() === 'ok'){
							alert('Detalii salvate!');
							document.getElementById('detailsModal').style.display = 'none';
						} else {
							alert('Eroare la salvare: ' + res);
						}
					});
				};

				document.getElementById('closeDetails').onclick = () => {
					document.getElementById('detailsModal').style.display = 'none';
				};
			});
		});
	});
document.querySelectorAll('.edit-poza-btn').forEach(btn => {
    btn.addEventListener('click', function(){
        const id = this.dataset.id;
        document.getElementById('editPozaId').innerText = id;
        
        document.getElementById('edit_poza_main').value = '';
        document.getElementById('edit_poze_extra').value = '';
        
        loadCurrentExtraPhotos(id);
        
        document.getElementById('editPozaModal').style.display = 'block';
    });
});

// Functia care genereaza thumbnails cu buton de stergere (X)
function loadCurrentExtraPhotos(id) {
    const container = document.getElementById('current_extra_photos');
    container.innerHTML = 'Încărcare...';

    const formData = new FormData();
    formData.append('action', 'list_extra_photos');
    formData.append('id', id);

    fetch('admin_cars.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(photos => {
        container.innerHTML = '';
        if(photos.length === 0) {
            container.innerHTML = '<span style="color:#888; font-size:0.9rem;">Nicio poză extra.</span>';
            return;
        }
        
        photos.forEach(filename => {
            const div = document.createElement('div');
            div.style.cssText = 'position:relative; width:80px; height:60px;';
            
            div.innerHTML = `
                <img src="../resurse/imagini/masini/car_${id}/${filename}" style="width:100%; height:100%; object-fit:cover; border-radius:4px; border:1px solid #ccc;">
                <button type="button" onclick="deleteThisPhoto(${id}, '${filename}', this)" style="position:absolute; top:-5px; right:-5px; background:red; color:white; border:none; border-radius:50%; width:20px; height:20px; cursor:pointer; font-size:12px; line-height:20px; padding:0;">X</button>
            `;
            container.appendChild(div);
        });
    });
}

// functie stergere instant X
window.deleteThisPhoto = function(id, filename, btnElement) {
    if(!confirm('Ștergi definitiv această poză?')) return;

    const formData = new FormData();
    formData.append('action', 'delete_single_photo');
    formData.append('id', id);
    formData.append('filename', filename);

    fetch('admin_cars.php', { method: 'POST', body: formData })
    .then(r => r.text())
    .then(res => {
        if(res.trim() === 'ok') {
            btnElement.parentElement.remove();
        } else {
            alert("Eroare la ștergere: " + res);
        }
    });
};

document.getElementById('saveEditPoza').addEventListener('click', function(){
    const id = document.getElementById('editPozaId').innerText;
    const mainInput = document.getElementById('edit_poza_main');
    const extraInput = document.getElementById('edit_poze_extra');

    if(mainInput.files.length === 0 && extraInput.files.length === 0){
        alert("Nu ai selectat nicio poză nouă pentru încărcare!");
        return;
    }

    const formData = new FormData();
    formData.append('action', 'edit_poza');
    formData.append('id', id);

    if(mainInput.files.length > 0) formData.append('edit_poza_main', mainInput.files[0]);

    if(extraInput.files.length > 0){
        for(let i = 0; i < extraInput.files.length; i++){
            formData.append('edit_poze_extra[]', extraInput.files[i]);
        }
    }

    fetch('admin_cars.php', { method: 'POST', body: formData })
    .then(r => r.text())
    .then(res => {
        if(res.trim() === 'ok'){
            alert("Actualizare reușită!");
            location.reload();
        } else {
            alert("Eroare la server: " + res);
        }
    })
    .catch(err => alert("Eroare rețea: " + err));
});

    const addBtn = document.getElementById('addCarBtn');
    const modal = document.getElementById('carModal');
    const saveBtn = document.getElementById('saveCar');

    if(addBtn){
        addBtn.addEventListener('click', () => {
            modal.style.display = 'block';
        });
    }
if(saveBtn){
		saveBtn.addEventListener('click', function(){
			const formData = new FormData();

			formData.append('action', 'add');
			formData.append('marca', document.getElementById('marca').value);
			formData.append('model', document.getElementById('model').value);
			formData.append('vin', document.getElementById('vin').value);
			formData.append('status', document.getElementById('status').value);
			
			formData.append('vizibil', document.getElementById('vizibil').value);
			
			formData.append('pret_vanzare', document.getElementById('pret_vanzare').value);
			formData.append('pret_inchiriere', document.getElementById('pret_inchiriere').value);
			formData.append('kilometraj', document.getElementById('kilometraj').value);
			formData.append('an', document.getElementById('an').value);
			formData.append('combustibil', document.getElementById('combustibil').value);
			formData.append('transmisie', document.getElementById('transmisie').value);
			formData.append('numar_inmatriculare', document.getElementById('numar_inmatriculare').value);
			formData.append('motorizare', document.getElementById('motorizare').value);
			formData.append('putere', document.getElementById('putere').value);

			const pozaInput = document.getElementById('poza');
			if(pozaInput.files.length === 0){
				alert("Te rog să selectezi o poză!");
				return;
			}
			formData.append('poza', pozaInput.files[0]);

			const extraInput = document.getElementById('poze_extra');

			if(extraInput && extraInput.files.length > 0){
				for(let i = 0; i < extraInput.files.length; i++){
					formData.append('poze_extra[]', extraInput.files[i]);
				}
			}
			
			if(!formData.get('marca') || !formData.get('model') || !formData.get('vin')){
				alert("Completează minim Marca, Model și VIN!");
				return;
			}

			postFormData(formData)
			.then(res => {
				if(res.trim() === 'ok'){
					alert("Mașină adăugată cu succes!");
					document.getElementById('carModal').style.display = 'none'; // închide modalul
					location.reload(); // reîncarcă pagina automat
				} else {
					alert("Eroare: " + res);
				}
			})
			.catch(err => alert("Eroare la trimitere: " + err));
		});
	}
});
