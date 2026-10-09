@include("blueprint.admin.admin")
@yield('blueprint.lib')

<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>{{ config('app.name', 'Pterodactyl') }} - @yield('title')</title>
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
        <meta name="_token" content="{{ csrf_token() }}">

        <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
        <link rel="icon" type="image/png" href="/favicons/favicon-32x32.png" sizes="32x32">
        <link rel="icon" type="image/png" href="/favicons/favicon-16x16.png" sizes="16x16">
        <link rel="manifest" href="/favicons/manifest.json">
        <link rel="mask-icon" href="/favicons/safari-pinned-tab.svg" color="#bc6e3c">
        <link rel="shortcut icon" href="/favicons/favicon.ico">
        <meta name="theme-color" content="#141211">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

        @include('layouts.scripts')

        @section('scripts')
            {!! Theme::css('vendor/select2/select2.min.css?t={cache-version}') !!}
            {!! Theme::css('vendor/bootstrap/bootstrap.min.css?t={cache-version}') !!}
            {!! Theme::css('vendor/adminlte/admin.min.css?t={cache-version}') !!}
            {!! Theme::css('vendor/adminlte/colors/skin-blue.min.css?t={cache-version}') !!}
            {!! Theme::css('vendor/sweetalert/sweetalert.min.css?t={cache-version}') !!}
            {!! Theme::css('vendor/animate/animate.min.css?t={cache-version}') !!}
            {!! Theme::css('css/pterodactyl.css?t={cache-version}') !!}
            <link rel="stylesheet" href="/themes/pterodactyl/css/bytenodes-admin.css?v={{ time() }}">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">

            @php
                $bnAdminSettings = app(\Pterodactyl\Contracts\Repository\SettingsRepositoryInterface::class);
                $bnAdminAccent = $bnAdminSettings->get('settings::bytenodes:ui:accent_color', '#BFA89E');
                $bnAdminCustomCss = $bnAdminSettings->get('settings::bytenodes:ui:custom_css', '');
                $bnAdminFooterText = $bnAdminSettings->get('settings::bytenodes:ui:footer_credit_text', 'Powered By ByteNodes.id');
                $bnAdminFooterUrl = $bnAdminSettings->get('settings::bytenodes:ui:footer_credit_url', 'https://bytenodes.id');
            @endphp
            @if($bnAdminAccent !== '#BFA89E')
            <style>
                :root {
                    --bn-accent: {{ $bnAdminAccent }} !important;
                    --bn-khaki: {{ $bnAdminAccent }} !important;
                }
            </style>
            @endif
            @if(!empty($bnAdminCustomCss))
            <style id="bytenodes-admin-custom-css">
                {!! $bnAdminCustomCss !!}
            </style>
            @endif

            <!--[if lt IE 9]>
            <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
            <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
            <![endif]-->
        @show

        @yield("blueprint.import")
    </head>
    <body class="hold-transition skin-blue fixed sidebar-mini">
        @yield('blueprint.cache')
        <div class="wrapper">

            <header class="main-header">
                <a href="{{ route('index') }}" class="logo">
                    <span>Bytenodes</span> <small style="font-size: 11px; color: var(--bn-text-muted); font-family: 'JetBrains Mono', monospace; font-weight: 500; letter-spacing: 0.05em;">ADMIN</small>
                </a>
                <nav class="navbar navbar-static-top">
                    <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </a>

                    <!-- Omnibar Quick Search -->
                    <div class="bytenodes-omnibar-wrapper">
                        <i class="fa fa-search bytenodes-omnibar-icon"></i>
                        <input type="text" id="bytenodesOmnibarInput" class="form-control bytenodes-omnibar-input" placeholder="Search servers, users, nodes... (Ctrl+K)" autocomplete="off">
                        <div id="bytenodesOmnibarResults" class="bytenodes-omnibar-results"></div>
                    </div>

                    <div class="navbar-custom-menu">
                        <ul class="nav navbar-nav">
                            <li class="user-menu">
                                <a href="{{ route('account') }}">
                                    <img src="https://www.gravatar.com/avatar/{{ md5(strtolower(Auth::user()->email)) }}?s=160" class="user-image" alt="User Image">
                                    <span class="hidden-xs">{{ Auth::user()->name_first }} {{ Auth::user()->name_last }}</span>
                                </a>
                            </li>
                            @yield("blueprint.navigation")
                            <li>
                                <a href="{{ route('index') }}" data-toggle="tooltip" data-placement="bottom" title="Return to Client Portal">
                                    <i class="fa fa-desktop"></i> <span class="hidden-xs" style="margin-left: 5px; font-size: 12px;">Client Area</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('auth.logout') }}" id="logoutButton" data-toggle="tooltip" data-placement="bottom" title="Logout">
                                    <i class="fa fa-sign-out"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>
            </header>
            <aside class="main-sidebar">
                <section class="sidebar">
                    <ul class="sidebar-menu">
                        <li class="header">OVERVIEW</li>
                        <li class="{{ Route::currentRouteName() !== 'admin.index' ?: 'active' }}">
                            <a href="{{ route('admin.index') }}">
                                <i class="fa fa-dashboard"></i> <span>Overview</span>
                            </a>
                        </li>
                        <li class="{{ ! starts_with(Route::currentRouteName(), 'admin.announcements') ?: 'active' }}">
                            <a href="{{ route('admin.announcements') }}">
                                <i class="fa fa-bullhorn"></i> <span>Announcements</span>
                            </a>
                        </li>
                        <li class="{{ ! starts_with(Route::currentRouteName(), 'admin.finance') ?: 'active' }}">
                            <a href="{{ route('admin.finance') }}">
                                <i class="fa fa-credit-card"></i> <span>Financials</span>
                            </a>
                        </li>
                        <li class="{{ ! starts_with(Route::currentRouteName(), 'admin.api') ?: 'active' }}">
                            <a href="{{ route('admin.api.index') }}">
                                <i class="fa fa-terminal"></i> <span>Application API</span>
                            </a>
                        </li>

                        <li class="header">FLEET & INFRASTRUCTURE</li>
                        <li class="{{ ! starts_with(Route::currentRouteName(), 'admin.servers') ?: 'active' }}">
                            <a href="{{ route('admin.servers') }}">
                                <i class="fa fa-server"></i> <span>Servers</span>
                            </a>
                        </li>
                        <li class="{{ Route::currentRouteName() === 'admin.nodes' || (starts_with(Route::currentRouteName(), 'admin.nodes') && !starts_with(Route::currentRouteName(), 'admin.nodes.splitter')) ? 'active' : '' }}">
                            <a href="{{ route('admin.nodes') }}">
                                <i class="fa fa-sitemap"></i> <span>Nodes</span>
                            </a>
                        </li>
                        <li class="{{ starts_with(Route::currentRouteName(), 'admin.nodes.splitter') ? 'active' : '' }}">
                            <a href="{{ route('admin.nodes.splitter') }}">
                                <i class="fa fa-code-fork" style="color: #52b788;"></i> <span>Node Splitter</span>
                            </a>
                        </li>
                        <li class="{{ ! starts_with(Route::currentRouteName(), 'admin.locations') ?: 'active' }}">
                            <a href="{{ route('admin.locations') }}">
                                <i class="fa fa-globe"></i> <span>Locations</span>
                            </a>
                        </li>
                        <li class="{{ ! starts_with(Route::currentRouteName(), 'admin.databases') ?: 'active' }}">
                            <a href="{{ route('admin.databases') }}">
                                <i class="fa fa-database"></i> <span>Databases</span>
                            </a>
                        </li>

                        <li class="header">EXTENSIONS & MODULES</li>
                        @yield("blueprint.sidenav")
                        @if(Route::has('admin.settings.cloudbackups'))
                        <li class="{{ ! starts_with(Route::currentRouteName(), 'admin.settings.cloudbackups') ?: 'active' }}">
                            <a href="{{ route('admin.settings.cloudbackups') }}">
                                <i class="fa fa-cloud-upload"></i> <span>Auto Backup</span>
                            </a>
                        </li>
                        @endif
                        @if(Route::has('admin.extensions.ticketpro.index'))
                        <li class="{{ ! starts_with(Route::currentRouteName(), 'admin.extensions.ticketpro') ?: 'active' }}">
                            <a href="{{ route('admin.extensions.ticketpro.index') }}">
                                <i class="fa fa-ticket"></i> <span>TicketPro</span>
                                @if(isset($unreadAdminCount) && $unreadAdminCount > 0)
                                    <span class="label label-danger pull-right">{{ $unreadAdminCount }}</span>
                                @endif
                            </a>
                        </li>
                        @endif
                        @if(Route::has('admin.settings.splitter'))
                        <li class="{{ ! starts_with(Route::currentRouteName(), 'admin.settings.splitter') ?: 'active' }}">
                            <a href="{{ route('admin.settings.splitter') }}">
                                <i class="fa fa-columns"></i> <span>Server Splitter</span>
                            </a>
                        </li>
                        @endif
                        @if(Route::has('admin.sso.index'))
                        <li class="{{ ! starts_with(Route::currentRouteName(), 'admin.sso') ?: 'active' }}">
                            <a href="{{ route('admin.sso.index') }}">
                                <i class="fa fa-key"></i> <span>Magic SSO</span>
                            </a>
                        </li>
                        @endif
                        @if(Route::has('admin.settings.discordwebhooks'))
                        <li class="{{ ! starts_with(Route::currentRouteName(), 'admin.settings.discordwebhooks') ?: 'active' }}">
                            <a href="{{ route('admin.settings.discordwebhooks') }}">
                                <i class="fa fa-bell"></i> <span>Discord Webhooks</span>
                            </a>
                        </li>
                        @endif

                        <li class="header">SERVICES & MOUNT</li>
                        <li class="{{ ! starts_with(Route::currentRouteName(), 'admin.mounts') ?: 'active' }}">
                            <a href="{{ route('admin.mounts') }}">
                                <i class="fa fa-folder-open"></i> <span>Mounts</span>
                            </a>
                        </li>
                        <li class="{{ ! starts_with(Route::currentRouteName(), 'admin.nests') ?: 'active' }}">
                            <a href="{{ route('admin.nests') }}">
                                <i class="fa fa-cubes"></i> <span>Nests & Eggs</span>
                            </a>
                        </li>

                        <li class="header">SYSTEM & UI SETTINGS</li>
                        <li class="{{ ! starts_with(Route::currentRouteName(), 'admin.users') ?: 'active' }}">
                            <a href="{{ route('admin.users') }}">
                                <i class="fa fa-users"></i> <span>Users</span>
                            </a>
                        </li>
                        <li class="{{ starts_with(Route::currentRouteName(), 'admin.billing') ? 'active' : '' }}">
                            <a href="{{ route('admin.billing.index') }}">
                                <i class="fa fa-credit-card" style="color: #52b788;"></i> <span>Billing & TriPay</span>
                            </a>
                        </li>
                        <li class="{{ in_array(Route::currentRouteName(), ['admin.settings', 'admin.settings.mail', 'admin.settings.advanced']) ? 'active' : '' }}">
                            <a href="{{ route('admin.settings') }}">
                                <i class="fa fa-sliders"></i> <span>Settings</span>
                            </a>
                        </li>
                        @if(Route::has('admin.settings.links'))
                        <li class="{{ starts_with(Route::currentRouteName(), 'admin.settings.links') ? 'active' : '' }}">
                            <a href="{{ route('admin.settings.links') }}">
                                <i class="fa fa-paint-brush" style="color: #BFA89E;"></i> <span>ByteNodes UI Editor</span>
                            </a>
                        </li>
                        @endif
                        @if(Route::has('admin.settings.integrations'))
                        <li class="{{ starts_with(Route::currentRouteName(), 'admin.settings.integrations') ? 'active' : '' }}">
                            <a href="{{ route('admin.settings.integrations') }}">
                                <i class="fa fa-plug" style="color: #52b788;"></i> <span>API &amp; Integrations</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </section>
            </aside>
            <div class="content-wrapper">
                <section class="content-header">
                    @yield('blueprint.introduction')
                    @yield('content-header')
                </section>
                <section class="content">
                    <div class="row">
                        <div class="col-xs-12">
                            @if (isset($errors) && count($errors) > 0)
                                <div class="alert alert-danger" style="background: rgba(239, 68, 68, 0.15) !important; border: 1px solid rgba(248, 113, 113, 0.3) !important; color: #f87171 !important; border-radius: 8px;">
                                    <strong>There was an error validating the data provided:</strong><br><br>
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            @foreach (Alert::getMessages() as $type => $messages)
                                @foreach ($messages as $message)
                                    <div class="alert alert-{{ $type }} alert-dismissable" role="alert" style="border-radius: 8px;">
                                        {{ $message }}
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                    @yield('content')
                </section>
            </div>
            <footer class="main-footer">
                <div class="pull-right small text-gray" style="margin-right:10px;margin-top:-7px;">
                    <strong><i class="fa fa-fw {{ $appIsGit ? 'fa-git-square' : 'fa-code-fork' }}"></i></strong> {{ $appVersion }}<br />
                    <strong><i class="fa fa-fw fa-clock-o"></i></strong> {{ round(microtime(true) - (defined('LARAVEL_START') ? LARAVEL_START : microtime(true)), 3) }}s
                </div>
                <strong>Bytenodes Fleet Command</strong> &copy; {{ date('Y') }} &middot; <a href="{{ $bnAdminFooterUrl }}" target="_blank" style="color: {{ $bnAdminAccent }}; font-weight: 600; text-decoration: none;">{{ $bnAdminFooterText }}</a>
            </footer>
        </div>
        @section('footer-scripts')
            <script src="/js/keyboard.polyfill.js" type="application/javascript"></script>
            <script>keyboardeventKeyPolyfill.polyfill();</script>

            {!! Theme::js('vendor/jquery/jquery.min.js?t={cache-version}') !!}
            {!! Theme::js('vendor/sweetalert/sweetalert.min.js?t={cache-version}') !!}
            {!! Theme::js('vendor/bootstrap/bootstrap.min.js?t={cache-version}') !!}
            {!! Theme::js('vendor/slimscroll/jquery.slimscroll.min.js?t={cache-version}') !!}
            {!! Theme::js('vendor/adminlte/app.min.js?t={cache-version}') !!}
            {!! Theme::js('vendor/bootstrap-notify/bootstrap-notify.min.js?t={cache-version}') !!}
            {!! Theme::js('vendor/select2/select2.full.min.js?t={cache-version}') !!}
            {!! Theme::js('js/admin/functions.js?t={cache-version}') !!}
            <script src="/js/autocomplete.js" type="application/javascript"></script>

            @if(Auth::user()->root_admin)
                <script>
                    $('#logoutButton').on('click', function (event) {
                        event.preventDefault();

                        var that = this;
                        swal({
                            title: 'Do you want to log out?',
                            type: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d9534f',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Log out'
                        }, function () {
                             $.ajax({
                                type: 'POST',
                                url: '{{ route('auth.logout') }}',
                                data: {
                                    _token: '{{ csrf_token() }}'
                                },complete: function () {
                                    window.location.href = '{{route('auth.login')}}';
                                }
                        });
                    });
                });
                </script>
            @endif

            <!-- Omnibar Search JavaScript Handler -->
            <script>
                $(function () {
                    $('[data-toggle="tooltip"]').tooltip();

                    var $input = $('#bytenodesOmnibarInput');
                    var $results = $('#bytenodesOmnibarResults');
                    var searchTimer = null;

                    // Global shortcut Ctrl+K or Cmd+K
                    $(document).on('keydown', function (e) {
                        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                            e.preventDefault();
                            $input.focus().select();
                        }
                    });

                    $input.on('input', function () {
                        clearTimeout(searchTimer);
                        var query = $(this).val().trim();

                        if (query.length < 2) {
                            $results.hide().empty();
                            return;
                        }

                        searchTimer = setTimeout(function () {
                            $.ajax({
                                url: '{{ route('admin.quick-search') }}',
                                data: { q: query },
                                dataType: 'json',
                                success: function (data) {
                                    var html = '';
                                    var hasResults = false;

                                    if (data.servers && data.servers.length > 0) {
                                        hasResults = true;
                                        html += '<div class="bytenodes-omnibar-section"><i class="fa fa-server"></i> Servers</div>';
                                        data.servers.forEach(function (s) {
                                            html += '<a href="' + s.url + '" class="bytenodes-omnibar-item">' +
                                                '<div><strong>' + s.name + '</strong> <small style="color:var(--bn-text-muted);">[' + s.uuidShort + ']</small>' +
                                                '<div style="font-size:11px;color:var(--bn-text-muted);">' + s.owner + ' ÃÂÃÂ· Node ' + s.node + '</div></div>' +
                                                '<span class="label label-primary">' + s.memory + ' MB</span></a>';
                                        });
                                    }

                                    if (data.users && data.users.length > 0) {
                                        hasResults = true;
                                        html += '<div class="bytenodes-omnibar-section"><i class="fa fa-users"></i> Users</div>';
                                        data.users.forEach(function (u) {
                                            html += '<a href="' + u.url + '" class="bytenodes-omnibar-item">' +
                                                '<div><strong>' + u.username + '</strong><div style="font-size:11px;color:var(--bn-text-muted);">' + u.email + '</div></div>' +
                                                (u.root_admin ? '<span class="label label-danger">ADMIN</span>' : '<span class="label label-default">USER</span>') + '</a>';
                                        });
                                    }

                                    if (data.nodes && data.nodes.length > 0) {
                                        hasResults = true;
                                        html += '<div class="bytenodes-omnibar-section"><i class="fa fa-sitemap"></i> Nodes</div>';
                                        data.nodes.forEach(function (n) {
                                            html += '<a href="' + n.url + '" class="bytenodes-omnibar-item">' +
                                                '<div><strong>Node ' + n.name + '</strong><div style="font-size:11px;color:var(--bn-text-muted);">' + n.fqdn + '</div></div>' +
                                                '<i class="fa fa-arrow-right text-muted"></i></a>';
                                        });
                                    }

                                    if (!hasResults) {
                                        html = '<div style="padding: 16px; text-align: center; color: var(--bn-text-muted); font-size: 12px;">No matches found for "' + query + '"</div>';
                                    }

                                    $results.html(html).show();
                                }
                            });
                        }, 200);
                    });

                    $(document).on('click', function (e) {
                        if (!$(e.target).closest('.bytenodes-omnibar-wrapper').length) {
                            $results.hide();
                        }
                    });

                    // =========================================================================
                    // 1. Sidebar Scroll Persistence across page transitions
                    // =========================================================================
                    var $sidebar = $('.main-sidebar');
                    if ($sidebar.length) {
                        var savedScroll = sessionStorage.getItem('bytenodes_admin_sidebar_scroll');
                        if (savedScroll !== null) {
                            $sidebar.scrollTop(parseInt(savedScroll, 10));
                        } else {
                            var $activeNav = $sidebar.find('li.active').last();
                            if ($activeNav.length) {
                                var offset = $activeNav.position().top;
                                if (offset > $sidebar.height() - 140) {
                                    $sidebar.scrollTop(offset - 100);
                                }
                            }
                        }

                        $sidebar.on('click', 'a', function() {
                            sessionStorage.setItem('bytenodes_admin_sidebar_scroll', $sidebar.scrollTop());
                        });

                        var scrollDebounce = null;
                        $sidebar.on('scroll', function() {
                            clearTimeout(scrollDebounce);
                            scrollDebounce = setTimeout(function() {
                                sessionStorage.setItem('bytenodes_admin_sidebar_scroll', $sidebar.scrollTop());
                            }, 100);
                        });
                    }

                    // =========================================================================
                    // 2. Global Select2 Auto-Initializer (Replaces Windows native blue dropdown)
                    // =========================================================================
                    function initSelect2Fields(scope) {
                        var $container = scope ? $(scope) : $(document);
                        $container.find('select.bn-select, select.select2, select.form-control:not(.no-select2)').each(function() {
                            var $sel = $(this);
                            if ($sel.hasClass('select2-hidden-accessible')) return;

                            var count = $sel.find('option').length;
                            $sel.select2({
                                theme: 'default',
                                width: '100%',
                                minimumResultsForSearch: count > 8 ? 0 : -1,
                                dropdownParent: $sel.closest('.modal').length ? $sel.closest('.modal') : $('body')
                            });
                        });
                    }

                    initSelect2Fields();

                    $(document).on('shown.bs.modal', function(e) {
                        initSelect2Fields(e.target);
                    });
                });
            </script>
        @show
        @yield('blueprint.wrappers')
    </body>
</html>
