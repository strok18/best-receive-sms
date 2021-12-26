$(function () {
    var email = $("span[id='email']").text()
    var email_list = localStorage.getItem('email_list_' + email)
    if (email_list){
        $("tbody[id='mail_list']").html(email_list)
    }
})
function mail_open(id) {
    var email = $("span[id='email']").text()
    var email_array = JSON.parse(localStorage.getItem('email_array_' + email))[id]
    layer.open({
        type: 1,
        title: email_array['subject'],
        shadeClose: true,
        shade: false,
        maxmin: true, //开启最大化最小化按钮
        area: ['50%', '50%'],
        content: "<div class='mail-detail'>来自邮箱：<xmp>"+ email_array['from'] +"</xmp></div>" +
            "<br />" +
            "<div class='mail-detail'>"+ email_array['html'] +"</div>" +
            "<div class='mail-detail' style='text-align: right'>接收时间："+ transformTime(email_array['time']) +"</div>"
    })
}

function mail_list() {
        var email = $("span[id='email']").text()
        if (!email) {
            layer.msg('请先申请隐私邮箱')
            return
        }
        $.ajax({
            url: '/api/email_get',
            type: 'post',
            data: {email: email},
            beforeSend: function(){
                $("button[id='mail-list']").attr('disabled',"disabled")
                $("td[id='mail-td-list']").attr('onclick',"")
            },
            success: function (e) {
                console.log(e)
                if (e.error_code == 0) {
                    var data = e.data
                    var tr = ''
                    for (var i = 0; i < data.length; i++) {
                        tr += "<tr id='"+ i +"' onclick='mail_open(this.id)'>" +
                            "<td id='mail_from'><xmp>" + data[i]['from'] + "</xmp></td>" +
                            "<td id='mail_subject'>" + data[i]['subject'] + "</td>" +
                            "<td id='mail_time'>" + transformTime(data[i]['time']) + "</td>" +
                            "</tr>"
                    }
                    localStorage.setItem('email_list_' + email, tr)
                    localStorage.setItem('email_array_' + email, JSON.stringify(data))
                    $("tbody[id='mail_list']").html(tr)
                } else if (e.error_code == 1) {
                    layer.msg('服务器还未接收到邮件')
                } else {
                    layer.msg(e.msg)
                }
            }
        })
    click(3)
}

function mail_apply(e) {
    var name = $("input[name='email_name']").val()
    var site = $("select[name='site']").text()
    var email = $("span[id='email']").text()
    if (email) {
        layer.msg('您当前的邮箱' + email + '生效中')
        return
    }
    if (e) {
        if (!name) {
            layer.msg('申请邮箱用户名不能为空')
            return
        }
        $.ajax({
            url: '/api/email_apply',
            type: 'post',
            data: {email_name: name, site: site},
            success: function (e) {
                console.log(e)
                if (e.error_code == 0) {
                    $("span[id='email']").text(e.data)
                    $("span[id='email']").attr('data-clipboard-text', e.data)
                } else {
                    layer.msg(e.msg)
                }
            }
        })
    } else {
        $.ajax({
            url: '/api/email_apply',
            type: 'post',
            data: {site: site},
            success: function (e) {
                console.log(e)
                if (e.error_code == 0) {
                    $("span[id='email']").text(e.data)
                    $("span[id='email']").attr('data-clipboard-text', e.data)
                } else {
                    layer.msg(e.msg)
                }
            }
        })
    }

}

function mail_delete() {
    var email = $("span[id='email']").text()
    if (!email) {
        layer.msg('当前没有生效的邮箱')
        return
    }
    $.ajax({
        url: '/api/email_user_delete',
        type: 'post',
        data: {email: email},
        success: function (e) {
            console.log(e)
            if (e.error_code == 0) {
                $("span[id='email']").text('')
                localStorage.removeItem('email_list_' + email)
                localStorage.removeItem('email_array_' + email)
                var email_list = "<tr><td colspan='3' class='mail-null'>点击接收邮件查收邮件</td></tr>"
                $("tbody[id='mail_list']").html(email_list)
                layer.msg(email + '邮箱销毁成功')
            } else {
                layer.msg(email + '邮箱销毁失败')
            }
        }
    })
}

function transformTime(time) {
    var unixtime = time;
    var unixTimestamp = new Date(unixtime * 1000);
    var Y = unixTimestamp.getFullYear(),
        M = ((unixTimestamp.getMonth() + 1) > 10 ? (unixTimestamp.getMonth() + 1) : '0' + (unixTimestamp.getMonth() + 1)),
        D = (unixTimestamp.getDate() > 10 ? unixTimestamp.getDate() : '0' + unixTimestamp.getDate()),
        h = (unixTimestamp.getHours() < 10) ? "0" + unixTimestamp.getHours() : unixTimestamp.getHours(),
        min = (unixTimestamp.getMinutes() < 10) ? "0" + unixTimestamp.getMinutes() : unixTimestamp.getMinutes(),
        s = (unixTimestamp.getSeconds() < 10) ? "0" + unixTimestamp.getSeconds() : unixTimestamp.getSeconds();
    var toDay = Y + '-' + M + '-' + D + " " + h + ":" + min + ":" + s;
    return toDay
}

function click(second) {
    setTimeout(function () {
        $("button[id='mail-list']").removeAttr('disabled')
        $("td[id='mail-td-list']").attr('onclick',"mail_list()")
    }, second*1000)
}