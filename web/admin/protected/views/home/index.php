<body class="hold-transition skin-blue-light sidebar-mini">

    <!-- Site wrapper -->
    <div class="wrapper">
        <header class="main-header">
            <!-- Logo -->
            <a href="/" class="logo">
                <!-- mini logo for sidebar mini 50x50 pixels -->
                <span class="logo-mini">mysql中间件</span>
                <!-- logo for regular state and mobile devices -->
                <span class="logo-lg">mysql中间件</span>
            </a>
            <nav class="navbar navbar-static-top">
                <!-- Sidebar toggle button-->
                <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </a>
                <ul class="nav navbar-nav">
                    <li><a href="/url_shortener/index">技术支持</a></li>
                </ul>
                <div class="navbar-custom-menu">
                    <ul class="nav navbar-nav">
                        <li><a href="#">郭新华 (guoxinhua)</a></li>
                        <li>
                            <a href="/page/logout">
                                <i class="fa fa-sign-out fa-lg" aria-hidden="true"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
        </header>
        <!-- Left side column. contains the sidebar -->
        <aside class="main-sidebar">
            <!-- sidebar: style can be found in sidebar.less -->
            <section class="sidebar">
                <ul class="sidebar-menu">
                    <li class="treeview active">
                        <a href="#">
                            <i class="fa fa-list-alt"></i> <span>管理</span>
                            <span class="pull-right-container">
                                <i class="fa fa-angle-left pull-right"></i>
                            </span>
                        </a>
                        <ul class="treeview-menu">

                            <li ><a href="/sqllist"><i class="fa fa-lg fa-fw fa-reorder" aria-hidden="true"></i> 请求列表</a></li>
                        </ul>
                    </li>
                </ul>
            </section>
            <!-- /.sidebar -->
        </aside>
        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <section class="content-header">
                <h1>
                    sql汇总          </h1>
            </section>
            <!-- Main content -->
            <section class="content">

                <div class="box">
                    <form class="form-inline" action="" method="get">
                        <div class="box-body">
                            <div class="form-group">
                                <label for="project_id">请求mysql的qps</label>
                                <input class="form-control" id="tiny_url" name="tiny_url" type="text" disabled="disabled" value="<?php echo $qps ?>">
                            </div>
                        </div>
                    </form>
                </div>
                <?php
                foreach ($connCount as $proxyIp => $value)
                {
                    ?>
                     <label>连接数(proxy维度)</label>
                    <div class="box">
                        <form class="form-inline" action="" method="get">
                            <div class="box-body">
                                <div class="form-group">
                                    <label for="project_id">proxy的ip</label>
                                    <label class="btn btn-danger btn-xs action-delete" for="project_id"><?php echo $proxyIp ?></label>
                                </div>
                            </div>
                        </form>
                        <div class="box-body no-padding">
                            <table id="url_list_table" class="table table-hover table-bordered table-striped">
                                <tbody>
                                    <tr height="32">
                                        <?php
                                        foreach ($value as $k => $v)
                                        {
                                            $name = ($k == "client_count") ? "客户端连接数" : $k;
                                            ?>
                                            <td><?php echo $name . "     " ?><label class="btn btn-danger btn-xs action-delete"><?php echo $v ?>连接数</label></td>
                                        <?php } ?>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php } ?>


                <label>慢查询top100(汇总)</label>
                <div class="box">
                    <div class="box-body no-padding">
                        <table id="url_list_table" class="table table-hover table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>&nbsp;</th>
                                    <th>开始时间</th>
                                    <th>结束时间</th>
                                    <th>sql耗时(ms)</th>
                                    <th>sql语句</th>
                                    <th>数据量大小(字节)</th>
                                    <th>数据源(ip:port:db)</th>
                                    <th>来源ip(请求来自哪个ip)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($slowTop as $value)
                                {
                                    ?>

                                    <tr height="32">
                                        <td>
                                            <a class="add-favorite" href="javascript:void(0);" ><i class="fa fa-star-o fa-lg"></i></a>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i:s', substr($value['start'], 0, 10)) ?></td>
                                        <td><?php echo date('Y-m-d H:i:s', substr($value['start'], 0, 10)) ?></td>
                                        <td><?php echo $value['end'] - $value['start'] ?></td>
                                        <td><?php echo $value['sql'] ?></td>
                                        <td><?php echo $value['size'] ?></td>
                                        <td><?php echo $value['datasource'] ?></td>
                                        <td><?php echo $value['client_ip'] ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <br>
                    <label>大数据块top100(汇总)</label>
                    <div class="box">
                        <div class="box-body no-padding">
                            <table id="url_list_table" class="table table-hover table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>&nbsp;</th>
                                        <th>开始时间</th>
                                        <th>结束时间</th>
                                        <th>sql耗时(ms)</th>
                                        <th>sql语句</th>
                                        <th>数据量大小(字节)</th>
                                        <th>数据源(ip:port:db)</th>
                                        <th>来源ip(请求来自哪个ip)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($bigTop as $value)
                                    {
                                        ?>

                                        <tr height="32">
                                            <td>
                                                <a class="add-favorite" href="javascript:void(0);" ><i class="fa fa-star-o fa-lg"></i></a>
                                            </td>
                                            <td><?php echo date('Y-m-d H:i:s', substr($value['start'], 0, 10)) ?></td>
                                            <td><?php echo date('Y-m-d H:i:s', substr($value['start'], 0, 10)) ?></td>
                                            <td><?php echo $value['end'] - $value['start'] ?></td>
                                            <td><?php echo $value['sql'] ?></td>
                                            <td><?php echo $value['size'] ?></td>
                                            <td><?php echo $value['datasource'] ?></td>
                                            <td><?php echo $value['client_ip'] ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Modal -->
                        <div class="modal fade" id="add-favorite" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        <h4 class="modal-title" id="myModalLabel">收藏</h4>
                                    </div>
                                    <div class="modal-body">
                                        <form>
                                            <select class="form-control">
                                            </select>
                                            <input name="tiny_url_id" type="hidden" value="">
                                        </form>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
                                        <button type="submit" class="btn btn-primary">保存</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        </section>
                        <!-- /.content -->
                    </div>
                    <!-- /.content-wrapper -->


                    <script>
                        $(function () {
                            $('.action-delete').click(function () {
                                return confirm('确认删除？');
                            })
                            var li = $('li[data-active="1"]');
                            li.parents('.treeview').addClass('active');
                            li.addClass('active');

                            $(".select2").select2();
                        });
                    </script>
                    <script>
                        $(function () {
                            $('#url_list_table').on('click', '.add-favorite', function () {
                                var modal = $('#add-favorite');
                                var tiny_url_id = $(this).data('id');
                                modal.find('input[name="tiny_url_id"]').val(tiny_url_id);
                                modal.modal('show');
                            })

                            $('#add-favorite button[type="submit"]').click(function () {
                                var modal = $('#add-favorite');
                                var tiny_url_id = modal.find('input[name="tiny_url_id"]').val();
                                var url = '/url_shortener/add_favorite?id='
                                        + tiny_url_id
                                        + '&category='
                                        + modal.find('select').val();

                                $.get(url, '', function (data) {
                                    if (data.code === 0) {
                                        var link = $('#url_list_table a[data-id="' + tiny_url_id + '"]');
                                        link.removeClass('add-favorite').addClass('delete-favorite');
                                        link.children('i.fa-star-o').removeClass('fa-star-o').addClass('fa-star');
                                        modal.modal('hide');
                                    } else {
                                        alert(data.message);
                                    }
                                });

                                return false;
                            });

                            $('#url_list_table').on('click', '.delete-favorite', function () {
                                var tiny_url_id = $(this).data('id');
                                var url = '/url_shortener/delete_favorite?id=' + tiny_url_id + '&is_ajax=1';
                                $.get(url, '', $.proxy(
                                        function (data) {
                                            if (data.code === 0) {
                                                var link = $('#url_list_table a[data-id="' + tiny_url_id + '"]');
                                                link.removeClass('delete-favorite').addClass('add-favorite');
                                                link.children('i.fa-star').removeClass('fa-star').addClass('fa-star-o');
                                            } else
                                            {
                                                alert(data.message);
                                            }
                                        },
                                        this
                                        ));
                                return false;
                            });

                            $('#url_list_table td .qrcode-button').popover({
                                title: '二维码',
                                trigger: 'click',
                                html: true,
                            });
                        });
                    </script>
                    </body>
                    </html>
