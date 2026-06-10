document.getElementById('addRentalBtn').addEventListener('click',()=>document.getElementById('rentalModal').style.display='block');

function calcPrice(){
    const start = new Date(document.getElementById('rentalStart').value);
    const end = new Date(document.getElementById('rentalEnd').value);
    const car = document.getElementById('rentalCar');
    const priceDay = parseFloat(car.selectedOptions[0].dataset.pret);
    if(!isNaN(start) && !isNaN(end) && end>=start){
        const days = Math.ceil((end-start)/(1000*60*60*24)) + 1;
        document.getElementById('rentalPrice').value = days*priceDay;
    }
}

document.getElementById('rentalStart').addEventListener('change',calcPrice);
document.getElementById('rentalEnd').addEventListener('change',calcPrice);
document.getElementById('rentalCar').addEventListener('change',calcPrice);

document.getElementById('saveRentalBtn').addEventListener('click',()=>{
    const data = new URLSearchParams({
        action:'add_rental',
        user_id: document.getElementById('rentalClient').value,
        car_id: document.getElementById('rentalCar').value,
        start_date: document.getElementById('rentalStart').value,
        end_date: document.getElementById('rentalEnd').value,
        price: document.getElementById('rentalPrice').value
    });
    fetch('admin_rentals.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:data
    }).then(r=>r.text()).then(res=>{
        if(res.trim()==='ok'){alert('Rezervare adaugata!'); location.reload();} else{alert(res);}
    });
});

document.querySelectorAll('.delete-rental').forEach(btn=>{
    btn.addEventListener('click',()=>{
        if(!confirm('Sigur vrei sa stergi rezervarea?')) return;
        const id = btn.dataset.id;
        fetch('admin_rentals.php',{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:new URLSearchParams({action:'delete_rental',id})
        }).then(r=>r.text()).then(res=>{
            if(res.trim()==='ok'){alert('Rezervare stearsa!'); location.reload();} else{alert(res);}
        });
    });
});

document.querySelectorAll('.rental-status').forEach(sel=>{
    sel.addEventListener('change',()=>{
        const id = sel.dataset.id;
        const status = sel.value;
        fetch('admin_rentals.php',{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:new URLSearchParams({action:'update_status',id,status})
        }).then(r=>r.text()).then(res=>{
            if(res.trim()==='ok'){alert('Status actualizat!'); location.reload();} else{alert(res);}
        });
    });
});