$(function () {
    //点击复制
    $('.copy').click(function (e) {
        var clipboard = new Clipboard('.copy')
        clipboard.on('success', function (e) {
            layer.msg('复制' + e.text + '成功', {icon: 1})
        })
        clipboard.on('error', function (e) {
            layer.msg('复制' + e.text + '失败', {icon: 2})
        })
    })

    $('.clickA').click(function (e) {
        if(window.matchMedia("(min-width: 767px)").matches){
            var index = layer.load(1, {
                shade: [0.5,'#fff'], //0.1透明度的白色背景
                time: 5000
            })
            // setTimeout(function () {
            //     layer.closeAll('loading')
            //     layer.msg('访问超时,请尝试获取其他号码')
            // }, 20000)
        }
    })

    //点击小程序
    function clickXCX() {
        layer.open({
            type: 1,
            title: false,
            closeBtn: 0,
            area: '280px',
            skin: 'layui-layer-nobg', //没有背景色
            shadeClose: true,
            content: '<img src="/static/sms/images/ald.png">'
        })
    }

    //活动每日弹出一次
    var today = new Date();
    if (localStorage.getItem('alert') != today.getDate()){
        layer.open({
            type: 1,
            title: false,
            shade: 0.2,
            closeBtn: 1,
            area: '720px',
            skin: 'layui-layer-nobg', //没有背景色
            shadeClose: true,
            content: '<a target="_blank" href="https://s.click.taobao.com/3LsRRxv"><img src="https://www.yinsixiaohao.com/static/activity/alert.png"></a>',
            success: function () {
                localStorage.setItem('alert', today.getDate());
            }
        })
    }
    //右侧广告自适应宽度
    var win_width =  $(window).width()
    if (win_width > 772 && win_width < 990){
        $('.main-right').width((win_width - 772)/2);
    }
    if (win_width > 992 && win_width < 1200){
        $('.main-right').width((win_width - 992)/2);
    }
    if (win_width > 1200){
        $('.main-right').width((win_width - 1200)/2);
    }
})
