// 封装 ajax 为 Promise
        function fetchData(url) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'json',
                    success: function (data) {
                        if (data.code === 0) {
                            resolve(data);
                        } else {
                            reject(new Error(data.msg || "请求失败"));
                        }
                    },
                    error: function (xhr, status, err) {
                        reject(err);
                    }
                });
            });
        }

        // 渲染 table
        function renderTable(url, data) {
            layui.use('table', function () {
                var table = layui.table;

                table.render({
                    elem: '#table',
                    toolbar: true,
                    defaultToolbar: ['filter', 'print', 'exports'],
                    url: url,
                    data: data,
                    page: {
                        layout: ['limit', 'count', 'prev', 'page', 'next', 'skip', 'first'],
                        curr: 1,
                        limit: 10,
                        limits: [10, 50, 100, 500],
                        groups: 8,
                        prev: 'prev',
                        next: 'next',
                        first: "First",
                        last: "Last",
                        skipText: ['Go to', '', 'Confirm'],
                        countText: ['Total ', ''],
                        limitTemplet: item => item + ' / page'
                    },
                    cols: [[
                        { field: 'id', title: 'id', sort: true, hide: true },
                        { field: 'email', title: 'email', sort: true, width: 200 },
                        { field: 'tag', title: 'tag', sort: true },
                        { field: 'reason', title: 'reason', sort: true },
                        { field: 'note', title: 'note', sort: true },
                        { field: 'firstname', title: 'addname', sort: true },
                        { field: 'add_time', title: 'add_time', sort: true },
                        { field: 'is_use', title: 'is_use', sort: true },
                        { title: 'Operate', toolbar: '#toolbar', width: 160 }
                    ]]
                });

                // 工具条事件
                table.on('tool(demo)', function (obj) {
                    let rowData = obj.data;

                    // null 转为空字符串
                    for (let key in rowData) {
                        if (rowData[key] === null) {
                            rowData[key] = '';
                        }
                    }

                    if (obj.event === 'update') {
                        openEditDialog(rowData);
                    }
                });
            });
        }

        // 弹层编辑
        function openEditDialog(data) {
            let editindex = layer.open({
                type: 1,
                title: 'Edit information',
                area: ['800px', '690px'],
                shade: 0,
                shadeClose: true,
                maxmin: true,
                content: `
            <table width="90%" border="0" cellspacing="0" cellpadding="0" class="layui-table">
                <tr><td><b>Email</b>: ${data.email}</td></tr>
                <tr><td><b>add_time</b>: ${data.add_time}</td></tr>
                <tr><td><b>tag</b>: ${data.tag}</td></tr>
                <tr><td><b>is_use</b>: <input type="text" id="layeris_use" class="layui-input" value="${data.is_use}"></td></tr>
                <tr><td><b>note</b>: <textarea id="layernote" class="layui-textarea">${data.note}</textarea></td></tr>
            </table>
        `,
                btn: ['Save', 'Close'],
                btn1: function () {
                    var layerload = layer.load(1);
                    const note = $('#layernote').val();
                    const is_use = $('#layeris_use').val();

                    $.post('/User/Index/setBounceListNote', { id: data.id, note, is_use })
                        .done(() => {
                            layer.close(layerload);
                            $("#btnSubmit").trigger("click");
                            layer.close(editindex);
                        })
                        .fail(() => {
                            layer.close(layerload);
                            layer.msg("保存失败");
                        });
                },
                btn2: function () {
                    layer.close(editindex);
                }
            });
        }

        // 主入口
        function getdata(url) {
            let load = layer.load(1);
            fetchData(url)
                .then(res => {
                    renderTable(url, res.data);
                })
                .catch(err => {
                    console.error(err);
                    layer.msg(err.message || "请求失败");
                })
                .finally(() => {
                    layer.close(load);
                });
        }


        $("#btnSubmit").click(function () {
            const email = $('#email').val();
            const tag = $('#tag').val();
            const note = $('#note').val();

            const is_use = $('#is_use').val();
            const params = {
                email: email ? `/email/${email}` : '',
                tag: tag ? `/tag/${tag}` : '',
                note: note ? `/note/${note}` : '',

                is_use: is_use ? `/is_use/${is_use}` : ''
            };
            const url = encodeURI(`/User/Index/getBounceList${params.email}${params.tag}${params.note}${params.is_use}`);
            getdata(url);


        });
        getdata('/User/Index/getBounceList');

