window.onload = function() {
	window.onscroll = function(e){
		let top = document.getElementById('top');
		if(document.documentElement.scrollTop > 500){
			/* let top = document.getElementsByClassName('top')
			top.classList.remove('do-none') */
			top.classList.remove('d-none')
		}else{
			top.classList.add('d-none')
		}
	}
}

//toast
function toast(message, params = {}) {
	if (params.type === undefined) {
		params.type = 'success';
	}
	if (params.title === undefined) {
		params.title = 'MESSAGE';
	}
	//auto close
	if (params.isClose === undefined) {
		params.isClose = true;
	}
	if (params.showTime === undefined) {
		params.showTime = 5000;
	}

	let random = Math.floor(Math.random() * 99999);
	let messageHtml = `<div id="toast` + random + `" class="toast bg-` + params.type +
		` text-light m-1" aria-atomic="true" data-bs-autohide="` + params.isClose +
		`" data-bs-delay="` + params.showTime + `" style="width:450px;">
						  <div class="toast-header bg-` + params.type + ` text-light">
						    <strong class="me-auto">` + params.title + `</strong>
						    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
						  </div>
						  <div class="toast-body">
						    ` + message + `
						  </div>
						</div>`;
	let toast_box = document.getElementById('toast-box');
	toast_box.innerHTML += messageHtml;
	let toastElList = [].slice.call(document.querySelectorAll('.toast'))
	let toastList = toastElList.map(function(toastEl) {
		return new bootstrap.Toast(toastEl, {
			animation: true
		})
	})
	toastList.forEach(toast => toast.show());

	let myToastEl = document.getElementById('toast' + random)
	myToastEl.addEventListener('hidden.bs.toast', function(e) {
		let toastC=document.getElementById("toast" + random)
		toastC.parentNode.removeChild(toastC)
	})
}

//modal
function modal(body,params = {}, confirmCallback, closeCallback){
	if (params.title === undefined) {
		params.title = 'Message';
	}
	if (params.closeButton === undefined) {
		params.closeButton = 'Close';
	}
	if (params.confirmButton === undefined) {
		params.confirmButton = 'Confirm';
	}

	let modalHtml = `<div class="modal fade" id="modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
						<div class="modal-dialog modal-lg modal-dialog-scrollable">
							<div class="modal-content">
								<div class="modal-header">
									<h5 class="modal-title" id="modal-title">`+params.title+`</h5>
									<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
								</div>
								<div class="modal-body" id="modal-body">
									`+body+`
								</div>
								<div class="modal-footer">
									<button type="button" class="btn btn-secondary" id="close">`+params.closeButton+`</button>
									<button type="button" class="btn btn-primary" id="confirm">`+params.confirmButton+`</button>
								</div>
							</div>
						</div>
					</div>`;
	document.body.insertAdjacentHTML('beforeend', modalHtml);
	let modalEl = document.getElementById('modal');
	let myModal = new bootstrap.Modal(modalEl, {backdrop: 'static'})
	myModal.toggle()

	let confirm = document.getElementById('confirm');
	let close = document.getElementById('close');
	confirm.addEventListener('click', function (e) {
		myModal.hide()
		if (confirmCallback){
			confirmCallback('click confirm');
		}
		modalEl.parentNode.removeChild(modalEl)
	})
	close.addEventListener('click', function (e) {
		myModal.hide()
		if (closeCallback){
			closeCallback('click close');
		}
		modalEl.parentNode.removeChild(modalEl)
	})
}

//loading
function loading(type = true, shadeType = true){
	let loading = document.getElementById('loading');
	let shade = document.getElementById('shade');
	if (type){
		loading.classList.remove("d-none");
		if (shadeType){
			shade.classList.remove("d-none");
		}
	}else {
		loading.classList.add("d-none");
		shade.classList.add("d-none");
	}
}