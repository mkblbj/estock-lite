$("#dailypoupSaveButton").on("click", function (e) {
    $(this).html(__t("Saving..."));
    //禁用按钮
    $(this).prop("disabled", true);
    //获取表单数据和文件
    const btn = $(this);
    var formData = new FormData($("#dailypoupForm")[0]);
    //发送ajax请求
    $.ajax({
        url: "/api/dailypoup/save.php",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        // 上传进度事件
        xhr: function () {
            var xhr = new window.XMLHttpRequest();
            // 监听上传进度
            xhr.upload.addEventListener("progress", function (evt) {
                if (evt.lengthComputable) {
                    // 计算上传进度百分比
                    var percentComplete = (evt.loaded / evt.total) * 100;
                    // 可以在这里更新进度条等 UI
                    console.log(percentComplete + '% uploaded');
                }
            }, false);
            return xhr;
        },
        success: function (result) {
            if (result.code == 0) {
                toastr.success("保存成功");
                //点击关闭按钮
                $(".dailypoupCloseButton").trigger("click");
            } else {
                toastr.error(result.msg);
            }
        },
        error: function (xhr) {
            // 显示错误提示信息
            toastr.error("保存失败" + xhr.statusText);

        },
        complete: function () {
            // 恢复按钮文本
            btn.html('保存');
            // 启用按钮
            btn.prop("disabled", false);
        }
    });
});

$('#dailypoupCloseOverDayButton').on('click', function () {
    // 获取当前时间
    const now = new Date();

    // 计算明天零点的时间
    const tomorrow = new Date(now);
    tomorrow.setDate(now.getDate() + 1); // 设置为明天
    tomorrow.setHours(0, 0, 0, 0); // 时间设置为 00:00:00

    // 将时间转换为 UTC 字符串
    const expires = tomorrow.toUTCString();

    // 设置 Cookie
    document.cookie = `dailypoup=1; expires=${expires}; path=/`;


    $("#dailypoupCloseButton").trigger("click");
});

$(() => {
    //获取配置
    $.get("/api/dailypoup/get.php", (res) => {
        $("#dailypoupContent").text(res.content);
        $('#dailypoupContent').summernote('code', res.content);
        $("#dailypoupIsOpen").val(res.is_open);
        if (res.is_open != 1) {
            return;
        }
        //是否已经弹出过了
        if (document.cookie.indexOf("dailypoup=1") != -1) {
            return;
        }

        //url包含以下路径则不弹出
        const without = ['/login', '/logout']
        for (let i = 0; i < without.length; i++) {
            if (window.location.pathname.indexOf(without[i]) != -1) {
                return;
            }
        }

        $("#daily-content").html(res.content);

        $("#dailypoupModal").modal("show");
    })
})