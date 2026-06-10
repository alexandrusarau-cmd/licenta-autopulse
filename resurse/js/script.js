function calculateRate() {
    const years = parseInt(document.getElementById("years").value);
    if (!years || years <= 0) {
        document.getElementById("result").textContent = "Introduceți un număr valid de ani!";
        return;
    }

    const checkboxes = document.querySelectorAll(".car-check");
    let resultText = "";
    let found = false;

    const annualInterestRate = 0.03;
    const monthlyInterestRate = annualInterestRate / 12;

    checkboxes.forEach((checkbox) => {
        if (checkbox.checked) {
            found = true;
            const price = parseFloat(checkbox.getAttribute("data-price"));
            const months = years * 12;

			const monthlyRate = (price * monthlyInterestRate * Math.pow(1 + monthlyInterestRate, months)) / (Math.pow(1 + monthlyInterestRate, months) - 1);
			const totalAmount = monthlyRate * months;

			resultText += `Rata lunară pentru ${checkbox.closest("tr").children[1].textContent}: ${Math.round(monthlyRate).toLocaleString('ro-RO')} EUR<br>`;
			resultText += `Total de plătit la finalul perioadei de ${years} ani: ${Math.round(totalAmount).toLocaleString('ro-RO')} EUR<br>`;
        }
    });

    if (!found) {
        resultText = "Selectați cel puțin o mașină!";
    }

    document.getElementById("result").innerHTML = resultText;
}
function toggleMenu() {
    const navbar = document.querySelector('.navbar');
    navbar.classList.toggle('active');
}
function afisCeas(nr) {
    if (nr < 10) return "0" + nr;
    return nr;
}

window.onload = function() {
    let a = document.getElementById("titlu");
    let p = document.createElement("p");
    let data = new Date();

    let vectorZile = ["Duminica", "Luni", "Marti", "Miercuri", "Joi", "Vineri", "Sambata"];
    let vectorLuni = ["Ianuarie", "Februarie", "Martie", "Aprilie", "Mai", "Iunie", "Iulie", "August", "Septembrie", "Octombrie", "Noiembrie", "Decembrie"];
    
    p.innerHTML = `<span style='color:red'>${vectorZile[data.getDay()]}</span> `;
    p.innerHTML += `${data.getDate()}/${vectorLuni[data.getMonth()]}/${data.getFullYear()} ${afisCeas(data.getHours())}:${afisCeas(data.getMinutes())}:${afisCeas(data.getSeconds())}`;
    
	a.parentNode.insertBefore(p, a);
}