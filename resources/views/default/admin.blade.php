<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate, max-stale=0, post-check=0, pre-check=0" />
    <link rel="icon"  type="image/png" href="/assets/icons/fontawesome/gray/32/user-circle.png">
    <title>IAMBing | {{$title}}</title>

    <!-- Custom Light / Dark Mode CSS -->
    <script>
      if (window.matchMedia('(prefers-color-scheme: dark)').media === 'not all') {
        document.documentElement.style.display = 'none';
        document.head.insertAdjacentHTML(
          'beforeend',
          '<link rel="stylesheet" href="/light.css" onload="document.documentElement.style.display = \'\'">',
        );
      }
    </script>
    <link rel="stylesheet" href="/assets/css/bootstrap.darkly.min.css" media="(prefers-color-scheme: dark)" />
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css" media="(prefers-color-scheme: light)" />
    <!-- AFTER -->  

    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <!--<link href="../../assets/css/ie10-viewport-bug-workaround.css" rel="stylesheet">-->
    <!-- Custom styles for this template -->
    <link href="/assets/css/IAMBing.css" rel="stylesheet">
    <link href="/assets/css/toastr.min.css" rel="stylesheet">
    <link href="/assets/css/font-awesome.min.css" rel="stylesheet">

    

    <!-- Just for debugging purposes. Don't actually copy these 2 lines! -->
    <!--[if lt IE 9]><script src="../../assets/js/ie8-responsive-file-warning.js"></script><![endif]-->
    <!--<script src="../../assets/js/ie-emulation-modes-warning.js"></script>-->
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <link data-name="vs/editor/editor.main" rel="stylesheet" href="/assets/js/vendor/vs/editor/editor.main.css">
  </head>
  <body>

<style>
@media (prefers-color-scheme: dark) {

}
</style>

    <nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container-fluid">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="/" style="background: #004333;padding: 12px 0px 0px 18px;">
            <h3 style="color:#fff;margin:0px;"><i class="fa fa-user-circle fa-fw"></i> IAMBing</h3>
          </a>
            <ul class="nav navbar-nav  hidden-xs">
                <li><a href="#"><h4 style="margin:0">{{$title}}</h4></a></li>
            </ul>
          <ul class="nav navbar-nav navbar-right hidden-xs">
          </li>
            <li><a href="#"><h4 style="margin:0"></h4></a></li>
          </ul>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
          <ul class="nav navbar-nav navbar-right">
          <li><a href="/"><h4 style="margin:0;">IAMBing Admin</h4></a>
            <li class="dropdown">
              <a href="#" class="dropdown-toggle user-info" data-toggle="dropdown" role="button">
                <img class="gravatar" src="https://www.gravatar.com/avatar/{{ md5(Auth::user()->email) }}?d=mm" /> 
                {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}
                <span class="caret"></span>
              </a>
              <ul class="dropdown-menu">
                <li><a href="/"><i class="fa fa-arrow-left"></i> Home</a></li>
                <li><a href="{{ url('/logout') }}"><i class="fa fa-times-circle"></i> Logout</a></li>
              </ul>
              @can('view_in_admin',App\Models\User::class)
                  <li class=" visible-xs-block @if($page=="users") active @endif"><a href="/users"><i class="fa fa-user fa-fw"></i>&nbsp; Users</a></li>
              @endcan
              @can('view_in_admin',App\Models\Group::class)
                  <li class="visible-xs-block @if($page=="groups") active @endif"><a href="/groups"><i class="fa fa-users fa-fw"></i>&nbsp; Groups</a></li>
              @endcan
              @can('view_in_admin',App\Models\System::class)
                  <li class="visible-xs-block @if($page=="systems") active @endif"><a href="/systems"><i class="fa fa-wrench fa-fw"></i>&nbsp; Systems</a></li>
              @endcan
              @can('view_in_admin',App\Models\Entitlement::class)
                  <li class="visible-xs-block @if($page=="entitlements") active @endif"><a href="/entitlements"><i class="fa fa-certificate fa-fw"></i>&nbsp; Entitlements</a></li>
              @endcan
              @can('view_in_admin',App\Models\Endpoint::class)
                  <li class="visible-xs-block @if($page=="endpoints") active @endif"><a href="/endpoints"><i class="fa fa-cogs fa-fw"></i>&nbsp; API Endpoints</a></li>
              @endcan
              @can('view_in_admin',App\Models\Configuration::class)
                  <li class="visible-xs-block @if($page=="configuration") active @endif"><a href="/configuration"><i class="fa fa-cog fa-fw"></i>&nbsp; System Config</a></li>
              @endcan
              <li class="visible-xs-block"><a href="https://github.com/BinghamtonUniversity/IAMBing/wiki" target="_blank"><i class="fa fa-info fa-fw"></i>&nbsp; View Documentation</a></li>
            </li>
          </ul>
          <ul class="nav navbar-nav navbar-right visible-xs-block">
              <!-- Insert Links Here -->
          </ul>
        </div>
      </div>
    </nav>
    <div class="col-sm-3 col-md-2 sidebar">
        <ul class="nav nav-sidebar">
            @can('view_in_admin',App\Models\User::class)
                <li class="@if($page=="users") active @endif"><a href="/users"><i class="fa fa-user fa-fw"></i>&nbsp; Users</a></li>
            @endcan
            @can('view_in_admin',App\Models\Group::class)
                <li class="@if($page=="groups") active @endif"><a href="/groups"><i class="fa fa-users fa-fw"></i>&nbsp; Groups</a></li>
            @endcan
            @can('view_in_admin',App\Models\System::class)
                <li class="@if($page=="systems") active @endif"><a href="/systems"><i class="fa fa-wrench fa-fw"></i>&nbsp; Systems</a></li>
            @endcan
            @can('view_in_admin',App\Models\Entitlement::class)
                <li class="@if($page=="entitlements") active @endif"><a href="/entitlements"><i class="fa fa-certificate fa-fw"></i>&nbsp; Entitlements</a></li>
            @endcan
            @can('view_in_admin',App\Models\Endpoint::class)
                <li class="@if($page=="endpoints") active @endif"><a href="/endpoints"><i class="fa fa-cogs fa-fw"></i>&nbsp; API Endpoints</a></li>
            @endcan
            @can('view_in_admin',App\Models\Configuration::class)
                <li class="@if($page=="configuration") active @endif"><a href="/configuration"><i class="fa fa-cog fa-fw"></i>&nbsp; System Config</a></li>
            @endcan
            <li><a href="https://github.com/BinghamtonUniversity/IAMBing/wiki" target="_blank"><i class="fa fa-fw fa-info"></i>&nbsp; View Documentation</a></li>
        </ul>
    </div>
    <div class="container-fluid" id="main-container">
      <div class="row">
        <div class="col-sm-12 admin-main">
            <div id="content">
              <!--
                <nav aria-label="breadcrumb">
                    <?php $crumbs = explode('_',$page); ?>
                    <ol class="breadcrumb">
                        @if (isset($ids))
                            @foreach($crumbs as $index => $crumb)
                                <li class="breadcrumb-item"><a href="/<?php
                                    for($i=0;$i<=$index;$i++) {
                                        echo (isset($ids[$i-1])?('/'.$ids[$i-1]):'').'/'.$crumbs[$i];
                                    }
                                ?>">{{Str::snakeToTitle(Str::snake($crumb))}}</a></li>
                            @endforeach
                        @endif
                    </ol>
                </nav>
              -->
                @if(isset($help))
                    <div class="alert alert-info">{{$help}}</div>
                @endif
                <div id="adminDataGrid"></div>
                <style>
                div#adminDataGrid > div.well > div {
                    /* Make All Datagrid Stuff Scrollable Hack */
                    overflow: scroll !important;
                }
                </style>
            </div>
        </div>
      </div>
    </div>
    
<!-- Begin Modal -->
<div class="modal fade" id="adminModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Modal title</h4>
      </div>
      <div class="modal-body">
        <p>One fine body&hellip;</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<!-- End Modal -->

    <script src='/assets/js/vendor/jquery.min.js'></script>
    <script src="/assets/js/vendor/bootstrap.min.js"></script>
    <script src="/assets/js/vendor/lodash.min.js"></script>
    <script>_.findWhere = _.find; _.where = _.filter;_.pluck = _.map;_.contains = _.includes;</script>
    <script src='/assets/js/vendor/hogan.min.js'></script>
    <script src='/assets/js/vendor/toastr.min.js'></script> 
    <script src='/assets/js/vendor/gform_bootstrap.js'></script>
    <script src='/assets/js/vendor/GrapheneDataGrid.min.js'></script> 
    <script src='/assets/js/vendor/moment.js'></script> 
    <script src='/assets/js/vendor/bootstrap-datetimepicker.min.js'></script> 
    <script src='/assets/js/vendor/sortable.js'></script> 
    <script src='/assets/js/vendor/ractive.min.js'></script> 
    <script src="/assets/js/admin/admin.js"></script>
    <script>
        @if(isset($ids)) window.ids={!!json_encode($ids)!!}; @endif 
        if (typeof window.ids !== 'undefined' && Array.isArray(window.ids)) {
            window.id = window.ids[window.ids.length-1]
        }
        @if(isset($actions)) window.actions={!!json_encode($actions)!!}; @endif 
    </script>
    <script src="/assets/js/admin/admin_{{$page}}.js"></script>
  </body>
</html>
