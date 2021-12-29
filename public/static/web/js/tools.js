window.onload = function () {
    scrollTop();
    if (typeof copyPhone === 'function'){
        copyPhone();
    }
    if (typeof messageInit === 'function'){
        messageInit();
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
						<div class="modal-dialog modal-dialog-scrollable ` + params.size + `">
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
    //按确定按钮触发
    confirm.addEventListener('click', function (e) {
        if (params.autoClose) {
            myModal.hide();
            modalEl.parentNode.removeChild(modalEl)
        }
        if (confirmCallback) {
            confirmCallback(myModal);
        }

    })
    //按关闭按钮触发
    close.addEventListener('click', function (e) {
        console.log(e)
        myModal.hide()
        if (closeCallback) {
            closeCallback(myModal);
        }
        modalEl.parentNode.removeChild(modalEl)
    })

    //关闭窗口触发
    iconClose.addEventListener('click', function (e) {
        myModal.hide()
        modalEl.parentNode.removeChild(modalEl)
    })
}

//关闭mosal
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
function loading(type = true, shadeType = true) {
    let loading = document.getElementById('loading');
    let shade = document.getElementById('shade');
    if (type) {
        loading.classList.remove("d-none");
        if (shadeType) {
            shade.classList.remove("d-none");
        }
    } else {
        loading.classList.add("d-none");
        shade.classList.add("d-none");
    }
}

//copy
function copy() {
    let clipboard = new ClipboardJS('.copy')
    console.log(clipboard);
    toast('Copy success!!!')
    clipboard.on('error', function (e) {
        toast('Copy fail!!!', {type: 'danger'})
    })
}

function subscription(info) {
    let subscriptionHtml = `
		<div class="container">
			<div class="row">
				<div class="modal-body">
				<form class="was-validated" novalidate>
					<div class="form-floating mb-3">
					  <input class="form-control" id="email_address" required>
					  <label for="email_address">Email address</label>
				  	</div>
				  <div class="alert alert-secondary" role="alert">
					  ` + info + `
				  </div>
				</form>
			  </div>
			</div>
		</div>
	`;
    modal(subscriptionHtml, {title: 'Subscription', size: '', autoClose: false}, function (e) {
        //发送ajax请求提交数据
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
        console.log(e)
    });
}

function feedback() {
    let feedbackHtml = `
		<div class="container">
			<div class="row">
				<div class="modal-body">
				<form class="was-validated" novalidate>
					<div class="form-floating mb-3">
					  <textarea class="form-control" id="feedback_content" style="height: 100px" required></textarea>
                      <label for="floatingTextarea2">Content of the feedback</label>
				  	</div>
				  	<div class="form-floating mb-3">           
					  <input class="form-control" id="feedback_email" required>
					  <label for="email_address">Email address</label>
				  	</div>
				</form>
			  </div>
			</div>
		</div>
	`;
    modal(feedbackHtml, {title: 'Feedback', size: '', autoClose: false}, function (e) {
        //发送ajax请求提交数据
        var modal = e;
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
        console.log(e)
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

//动态加载jquery
function loadScript(callback, url) {
    let urls = {jquery: '//'+ window.location.host + '/static/web/js/jquery-3.6.0.min.js', copy: '//' + window.location.host + '/static/web/js/clipboard.min.js'};
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
