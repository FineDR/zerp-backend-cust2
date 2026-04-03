function ShowCompanies() {
	const dropdown = document.getElementById("dropdownlist");
	const companySelect = document.getElementById("CompanySelect");
	if (!dropdown || !companySelect) {
		return;
	}
	dropdown.addEventListener("transitionend",
		function() {
			if (dropdown.style.overflow=="hidden" || dropdown.style.overflow=="") {
				dropdown.style.overflow="auto";
			} else {
				dropdown.style.overflow="hidden";
			}
		}
	);
	dropdown.style.transition = "max-height 0.3s";
	if (dropdown.style.maxHeight=="0px" || dropdown.style.maxHeight=="") {
		const rect = companySelect.getBoundingClientRect();
		var ViewPortHeight = window.innerHeight;
		var DropDownTop=rect.bottom;
		dropdown.style.left=rect.left+"px";
		dropdown.style.maxHeight=ViewPortHeight-DropDownTop-10+"px";
		companySelect.style.background = "url(\'css/ascending.png\') no-repeat right center";
		companySelect.style.backgroundSize = "18px";
		dropdown.style.display="block";
	} else {
		dropdown.style.overflow="hidden"
		dropdown.style.maxHeight="0px";
		dropdown.style.display="none";
		companySelect.style.background = "url(\'css/descending.png\') no-repeat right center";
		companySelect.style.backgroundSize = "18px";
	}
}

function UpdateSelect() {
	const companyField = document.getElementById("CompanyNameField");
	const companySelect = document.getElementById("CompanySelect");
	const dropdown = document.getElementById("dropdownlist");
	if (!companyField || !companySelect || !dropdown) {
		return;
	}
	companyField.value=this.id;
	companySelect.value=companyField.options[companyField.selectedIndex].text;
	dropdown.style.maxHeight="0px"
	dropdown.style.display="none";
	companySelect.style.background = "url(\'css/descending.png\') no-repeat right center";
	companySelect.style.backgroundSize = "18px";
}

function TogglePassword () {
	const passwordField = document.getElementById("password");
	const eye = document.getElementById("eye");
	if (!passwordField || !eye) {
		return;
	}
	if (passwordField.type == "password") {
		passwordField.type = "text";
		eye.style.backgroundImage = "url('css/eyeshut.png')";
		eye.title = eye.dataset.hideTitle || "Hide Password";
	} else {
		passwordField.type = "password";
		eye.style.backgroundImage = "url('css/eye.png')";
		eye.title = eye.dataset.showTitle || "Show Password";
	}
}

function checkMousePos(event) {
	const dropdown = document.getElementById("dropdownlist");
	const companySelect = document.getElementById("CompanySelect");
	if (!dropdown || !companySelect) {
		return;
	}
	if (dropdown.style.maxHeight!="0px" && dropdown.style.maxHeight!="") {
		const rect = companySelect.getBoundingClientRect();
		if ((event.clientX < rect.left || event.clientX > rect.right) || (event.clientY < rect.top || event.clientY > rect.bottom)) {
			ShowCompanies();
		}
	}
}

function ShowSpinner() {
	const spinner = document.getElementById("waiting_show");
	if (spinner) {
		spinner.style.display="block";
	}
}

document.addEventListener("click", checkMousePos);
if (document.getElementById("eye")) {
	document.getElementById("eye").addEventListener("click", TogglePassword);
}
if (document.getElementById("CompanySelect") && document.getElementById("CompanyNameField")) {
	document.getElementById("CompanySelect").value=document.getElementById("CompanyNameField").options[document.getElementById("CompanyNameField").selectedIndex].text;
	document.getElementById("CompanySelect").addEventListener("click", ShowCompanies);
}
var options=document.getElementsByClassName("option");
for (let i = 0; i < options.length; i++) {
	document.getElementById(options[i].id).addEventListener("click", UpdateSelect);
}
