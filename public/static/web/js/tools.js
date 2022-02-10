window.onload = function () {
	init();
	scrollTop();
	clickLoading();
	unload();
	stretchBottom();
	adWidth();
	if (typeof copyPhone === 'function'){
		copyPhone();
	}
	if (typeof messageInit === 'function'){
		messageInit();
	}
}

function init() {
	//privacy alert show
	if (!localStorage.getItem('brsPrivacy')){
		document.getElementById('privacy_alert').classList.remove('d-none');
	}
	//tooltip
	let tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
	let tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
		return new bootstrap.Tooltip(tooltipTriggerEl)
	})

	//nav active
	let pathname = window.location.pathname.substring(1);
	if (pathname.length > 1){
		try {
			document.getElementById(pathname).classList.add('active');
			let countries = document.getElementById('countries');
			if (pathname.indexOf('-phone-number') != -1 && pathname !== 'upcoming-phone-number' || pathname === 'country'){
				countries.classList.add('active');
			}
		}catch (e) {
			try {
				let regExp = /receive-sms-from-([a-zA-Z]+)/;
				let link_title = regExp.exec(pathname);
				document.getElementById(link_title[1] + '-phone-number').classList.add('active');
				document.getElementById('countries').classList.add('active');
			}catch (e) {
				
			}
		}
	}
}

//left right自适应广告位宽度
function adWidth(){
	let windows_width = screen.width;
	if (windows_width > 1400){
		document.getElementById('main-right').style.width = ((windows_width - document.getElementById('container-ad').offsetWidth)/2)-30 + 'px';
		document.getElementById('main-left').style.width = ((windows_width - document.getElementById('container-ad').offsetWidth)/2)-75 + 'px';
		(adsbygoogle = window.adsbygoogle || []).push({});
	}
}
//底部拉伸
function stretchBottom(){
	let windows_height = screen.availHeight;
	let foot_null = document.getElementById('foot-null');
	let full_height = foot_null.offsetTop;
	let height = windows_height - full_height - 232;
	if (height > 0){
		foot_null.style.height = height + 'px';
	}
}

function unload(){
	window.addEventListener("unload", function(e) {
		//虽然无用，但是这里不能删，删除后，loading()后再返回页面，loading不会关闭
		console.log(1)
	});
}

function clickLoading(){
	let list = document.getElementsByClassName('click_loading'); // 获取class为ant-btn的元素
	for(let i in list)
	{
		list[i].onclick=function() {
			loading(true, 5);
		};
	}
}

//top button
function scrollTop() {
	window.onscroll = function (e) {
		let top = document.getElementById('top');
		if (document.documentElement.scrollTop > (document.body.clientHeight*0.1)) {
			top.classList.remove('d-none')
		} else {
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
	let toastList = toastElList.map(function (toastEl) {
		return new bootstrap.Toast(toastEl, {
			animation: true
		})
	})
	toastList.forEach(toast => toast.show());

	let myToastEl = document.getElementById('toast' + random)
	myToastEl.addEventListener('hidden.bs.toast', function (e) {
		let toastC = document.getElementById("toast" + random)
		toastC.parentNode.removeChild(toastC)
	})
}

//modal
function modal(body, params = {}, confirmCallback, closeCallback) {
	if (params.title === undefined) {
		params.title = 'Message';
	}
	if (params.closeButton === undefined) {
		params.closeButton = 'Close';
	}
	if (params.confirmButton === undefined) {
		params.confirmButton = 'Confirm';
	}
	if (params.confirmButton === undefined) {
		params.confirmButton = 'Confirm';
	}
	if (params.size === undefined) {
		params.size = 'modal-lg';
	}
	if (params.autoClose === undefined) {
		params.autoClose = true;
	}

	let modalHtml = `<div class="modal fade" id="modal" data-bs-backdrop="false" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
						<div class="modal-dialog modal-dialog-scrollable ` + params.size + ` modal-dialog-centered">
							<div class="modal-content">
								<div class="modal-header">
									<h5 class="modal-title" id="modal-title">` + params.title + `</h5>
									<button type="button" class="btn-close" aria-label="Close" id="icon_close"></button>
								</div>
								<div class="modal-body" id="modal-body">
									` + body + `
								</div>
								<div class="modal-footer">
									<button type="button" class="btn btn-secondary" id="close">` + params.closeButton + `</button>
									<button type="button" class="btn btn-primary" id="confirm">
									<span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="confirm_loading"></span>
                                    ` + params.confirmButton + `
                                    </button>
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
	let iconClose = document.getElementById('icon_close');
	////press the Confirm button to trigger
	confirm.addEventListener('click', function (e) {
		if (params.autoClose) {
			myModal.hide();
			modalEl.parentNode.removeChild(modalEl)
		}
		if (confirmCallback) {
			confirmCallback(myModal);
		}

	})
	//press the off button to trigger
	close.addEventListener('click', function (e) {
		myModal.hide()
		if (closeCallback) {
			closeCallback(myModal);
		}
		modalEl.parentNode.removeChild(modalEl)
	})

	//press the Close button to trigger
	iconClose.addEventListener('click', function (e) {
		myModal.hide()
		modalEl.parentNode.removeChild(modalEl)
	})
}

//close mosal
function modalClose(modal, callback) {
	modal.hide();
	let modalEl = document.getElementById('modal');
	let rmodal = modalEl.parentNode.removeChild(modalEl);

	if (callback){
		callback(rmodal);
	}
	loading(false);
}

//loading
function loading(type = true,autoClose = 0, shadeType = true) {
	let loading = document.getElementById('loading');
	let shade = document.getElementById('shade');
	if (type) {
		loading.classList.remove("d-none");
		if (shadeType) {
			shade.classList.remove("d-none");
		}
		if (autoClose > 0){
			setTimeout(function () {
				loading.classList.add("d-none");
				shade.classList.add("d-none");
			},autoClose*1000)
		}
	} else {
		loading.classList.add("d-none");
		shade.classList.add("d-none");
	}
}

//copy
function copy() {
	let clipboard = new ClipboardJS('.copy')
	toast('Copy success!!!')
	clipboard.on('error', function (e) {
		toast('Copy fail!!!', {type: 'danger'})
	})
}

function referrer() {
	let ref = '';
	if (document.referrer.length > 0) {
		ref = document.referrer;
	}
	try {  if (ref.length == 0 && opener.location.href.length > 0) {
		ref = opener.location.href;
	}
	} catch (e) {}
	return ref;
}

function captchaCallback (e) {
	if (e){
		$.ajax({
			url: '/hcaptcha',
			type: 'post',
			data: {response: e},
			beforeSend: function(){
				loading();
			},
			success: function (e) {
				console.log(e)
				if (e.error_code == 0) {
					toast(e.msg);
					window.location.href = referrer();
				} else {
					toast(e.msg, {type: 'danger'})
				}
			},
			complete: function () {
				loading(false);
			}
		})
	}
}

function subscription(lang) {
	let subscriptionHtml = `
		<div class="container">
			<div class="row">
				<div class="modal-body">
				<form id="subscription_form">
					<div class="form-floating mb-3">
					  <input class="form-control" placeholder="`+lang.emailAddress+`" id="email_address" required onfocus="inputFocus('subscription_form')">
					  <label for="email_address" class="text-secondary">`+lang.emailAddress+`</label>
				  	</div>
				  <div class="alert alert-secondary" role="alert">
					  ` + lang.explain + `
				  </div>
				</form>
			  </div>
			</div>
		</div>
	`;
	modal(subscriptionHtml, {title: lang.title, size: '', autoClose: false}, function (e) {
		inputFocus('subscription_form');
		var modal = e;
		loadScript(function () {
			let email = $('#email_address').val()
			$.ajax({
				url: '/api/mailbox',
				type: 'post',
				data: {mailbox: email},
				beforeSend: function(){
					$("#confirm").attr('disabled', "disabled")
					$("#confirm_loading").removeClass('d-none')
				},
				success: function (e) {
					if (e.error_code == 0) {
						toast(e.msg);
						modalClose(modal)
					} else {
						toast(e.msg, {type: 'danger'});
					}
				},
				complete: function () {
					$('#confirm').removeAttr('disabled')
					$("#confirm_loading").addClass('d-none')
				}
			})
		});
	}, function (e) {

	});
}

function feedback(lang) {
	let feedbackHtml = `
		<div class="container">
			<div class="row">
				<div class="modal-body">
				<form id="form_feedback">
					<div class="form-floating mb-3">
					  <textarea class="form-control" id="feedback_content" placeholder="`+lang.contentPla+`" style="height: 100px" required onfocus="inputFocus('form_feedback')"></textarea>
                      <label for="floatingTextarea2" class="text-secondary">`+lang.contentPla+`</label>
                      <div class="invalid-feedback">`+lang.contentLabel+`</div>
				  	</div>
				  	<div class="form-floating mb-3">           
					  <input class="form-control" id="feedback_email" placeholder="`+lang.emailAddress+`" required onfocus="inputFocus('form_feedback')">
					  <label for="email_address" class="text-secondary">`+lang.emailAddress+`</label>
					  <div class="invalid-feedback">`+lang.emailLabel+`</div>
				  	</div>
				</form>
			  </div>
			</div>
		</div>
	`;
	modal(feedbackHtml, {title: lang.title, size: '', autoClose: false}, function (e) {
		inputFocus('form_feedback');
		let modal = e;
		loadScript(function () {
			let email = $('#email_address').val()
			$.ajax({
				url: '/api/feedback',
				type: 'post',
				data: {content: $("#feedback_content").val(), email: $("#feedback_email").val(), type: 1},
				beforeSend: function(){
					$("#confirm").attr('disabled', "disabled")
					$("#confirm_loading").removeClass('d-none')
				},
				success: function (e) {
					if (e.error_code == 0) {
						toast(e.msg);
						modalClose(modal)
					} else {
						toast(e.msg, {type: 'danger'});
					}
				},
				complete: function () {
					$('#confirm').removeAttr('disabled')
					$("#confirm_loading").addClass('d-none')
				}
			})
		});
	}, function (e) {

	});
}

function buttonLoading(buttonID, type = true){
	let button = document.getElementById(buttonID);
	if (type === true){
		button.classList.remove("d-none");
	}else {
		button.classList.add("d-none");
	}
}

//dynamic loading JS
function loadScript(callback, url) {
	let urls = {
		jquery: '//'+ window.location.host + '/static/web/js/jquery-3.6.0.min.js',
		copy: '//' + window.location.host + '/static/web/js/clipboard.min.js',
		recaptcha: 'https://recaptcha.net/recaptcha/api.js?render=6Lf71VQcAAAAAG1lsIdsb3aeTivSf_rkKUKaCu7V',
	};
	if (url === undefined){
		url = urls.jquery;
	}else {
		url = urls[url];
	}
	let script = document.createElement("script")
	script.type = "text/javascript";
	if (script.readyState) { //IE
		script.onreadystatechange = function () {
			if (script.readyState == "loaded" || script.readyState == "complete") {
				script.onreadystatechange = null;
				callback();
			}
		};
	} else { //Others
		script.onload = function () {
			callback();
		};
	}
	script.src = url;
	document.getElementsByTagName("head")[0].appendChild(script);
}

//input 激活事件
function inputFocus(id = 'form_data'){
	let form = document.getElementById(id);
	form.classList.add('was-validated');
}
