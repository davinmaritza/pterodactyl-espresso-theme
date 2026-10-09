@extends('layouts.admin')
@include('partials/admin.settings.nav', ['activeTab' => 'integrations'])

@section('title')
    API & Integrations Settings
@endsection

@section('content-header')
    <h1>API & Integrations<small>Kelola seluruh API Key, AI Engine, Discord Bot, OAuth2, dan Webhook langsung dari Admin Panel.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.settings') }}">Settings</a></li>
        <li class="active">API & Integrations</li>
    </ol>
@endsection

@section('content')
    @yield('settings::nav')
    <div class="row">
        <div class="col-xs-12">
            <form action="{{ route('admin.settings.integrations.update') }}" method="POST" id="integrationsSettingsForm">
                {!! csrf_field() !!}
                <input type="hidden" name="_method" value="PATCH" />

                <!-- TAB NAVIGATION -->
                <div class="nav-tabs-custom" style="background: transparent; box-shadow: none; margin-bottom: 20px;">
                    <ul class="nav nav-tabs" style="border-bottom: 1px solid rgba(191, 168, 158, 0.15);">
                        <li class="active">
                            <a href="#tab_ai" data-toggle="tab" style="font-weight: 600; font-size: 13.5px;">
                                <i class="fa fa-magic" style="color: #52b788; margin-right: 6px;"></i> AI Assistant & Diagnostic Engine
                            </a>
                        </li>
                        <li>
                            <a href="#tab_discord" data-toggle="tab" style="font-weight: 600; font-size: 13.5px;">
                                <i class="fa fa-comments" style="color: #7289da; margin-right: 6px;"></i> Discord Bot & Auto-Role
                            </a>
                        </li>
                        <li>
                            <a href="#tab_webhooks" data-toggle="tab" style="font-weight: 600; font-size: 13.5px;">
                                <i class="fa fa-bell" style="color: #dd9754; margin-right: 6px;"></i> Webhook Notifications
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content" style="background: transparent; padding: 20px 0;">
                        <!-- TAB 1: AI ASSISTANT -->
                        <div class="tab-pane active" id="tab_ai">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="box box-primary" style="background: #1c1917; border-top-color: #52b788; border: 1px solid rgba(191, 168, 158, 0.15); border-radius: 8px;">
                                        <div class="box-header with-border" style="border-bottom: 1px solid rgba(191, 168, 158, 0.1);">
                                            <h3 class="box-title" style="color: #EBF5EE; font-weight: 600;"><i class="fa fa-microchip" style="margin-right: 8px; color: #52b788;"></i>Konfigurasi AI Engine</h3>
                                            <div class="box-tools pull-right">
                                                <span class="label {{ $ai_enabled ? 'label-success' : 'label-default' }}">
                                                    {{ $ai_enabled ? 'STATUS: AKTIF' : 'STATUS: NONAKTIF' }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="box-body" style="padding: 20px;">
                                            <div class="form-group">
                                                <label class="control-label" style="color: #EBF5EE;">Aktifkan Layanan AI Assistant</label>
                                                <div class="checkbox" style="margin-top: 0;">
                                                    <label style="color: #BFA89E;">
                                                        <input type="checkbox" name="ai_enabled" value="1" {{ $ai_enabled ? 'checked' : '' }}>
                                                        Izinkan klien menggunakan AI Diagnostic & Assistant di konsol server
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="control-label" style="color: #EBF5EE;">AI Gateway / Base URL</label>
                                                <input type="text" name="ai_base_url" id="ai_base_url" class="form-control" value="{{ $ai_base_url }}" placeholder="https://routernines.bytenodes.icu/v1" style="background: #141211; border-color: rgba(191, 168, 158, 0.25); color: #EBF5EE;">
                                                <p class="text-muted small" style="color: #8B786D; margin-top: 5px;">
                                                    Endpoint API kompatibel OpenAI/Gemini (misal: <code>https://routernines.bytenodes.icu/v1</code> atau <code>https://api.openai.com/v1</code>).
                                                </p>
                                            </div>

                                            <div class="form-group">
                                                <label class="control-label" style="color: #EBF5EE;">AI API Key</label>
                                                <div class="input-group">
                                                    <input type="password" name="ai_api_key" id="ai_api_key" class="form-control" value="{{ $ai_api_key }}" placeholder="sk-..." style="background: #141211; border-color: rgba(191, 168, 158, 0.25); color: #EBF5EE;">
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default toggle-password" type="button" data-target="#ai_api_key" style="background: #25211e; border-color: rgba(191, 168, 158, 0.25); color: #BFA89E;">
                                                            <i class="fa fa-eye"></i>
                                                        </button>
                                                    </span>
                                                </div>
                                                <p class="text-muted small" style="color: #8B786D; margin-top: 5px;">
                                                    API Key untuk otentikasi Bearer token ke AI Gateway.
                                                </p>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-7">
                                                    <div class="form-group">
                                                        <label class="control-label" style="color: #EBF5EE;">Model AI Utama</label>
                                                        <div class="input-group">
                                                            <input type="text" name="ai_model" id="ai_model" class="form-control" value="{{ $ai_model }}" placeholder="ag/gemini-3.8-flash" style="background: #141211; border-color: rgba(191, 168, 158, 0.25); color: #EBF5EE;">
                                                            <div class="input-group-btn">
                                                                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" style="background: #25211e; border-color: rgba(191, 168, 158, 0.25); color: #BFA89E;">
                                                                    Pilih Cepat <span class="caret"></span>
                                                                </button>
                                                                <ul class="dropdown-menu dropdown-menu-right" style="background: #1c1917; border: 1px solid rgba(191, 168, 158, 0.2);">
                                                                    <li><a href="#" class="preset-model" data-val="ag/gemini-3.8-flash" style="color: #EBF5EE;">ag/gemini-3.8-flash (Default Bytenodes)</a></li>
                                                                    <li><a href="#" class="preset-model" data-val="ag/gemini-3.7-flash-medium" style="color: #EBF5EE;">ag/gemini-3.7-flash-medium</a></li>
                                                                    <li><a href="#" class="preset-model" data-val="gemini-2.0-flash" style="color: #EBF5EE;">gemini-2.0-flash</a></li>
                                                                    <li><a href="#" class="preset-model" data-val="gpt-4o-mini" style="color: #EBF5EE;">gpt-4o-mini</a></li>
                                                                    <li><a href="#" class="preset-model" data-val="claude-3-5-sonnet" style="color: #EBF5EE;">claude-3-5-sonnet</a></li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                        <p class="text-muted small" style="color: #8B786D; margin-top: 5px;">Model yang digunakan untuk menganalisa error Minecraft dan konsultasi teknis.</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-5">
                                                    <div class="form-group">
                                                        <label class="control-label" style="color: #EBF5EE;">Batas Kuota / Server</label>
                                                        <input type="number" name="ai_max_attempts" class="form-control" value="{{ $ai_max_attempts }}" min="1" max="100" style="background: #141211; border-color: rgba(191, 168, 158, 0.25); color: #EBF5EE;">
                                                        <p class="text-muted small" style="color: #8B786D; margin-top: 5px;">Jumlah prompt per server dalam satu siklus.</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="control-label" style="color: #EBF5EE;">Siklus Reset Kuota (Jam)</label>
                                                <input type="number" name="ai_window_hours" class="form-control" value="{{ $ai_window_hours }}" min="1" max="720" style="background: #141211; border-color: rgba(191, 168, 158, 0.25); color: #EBF5EE;">
                                                <p class="text-muted small" style="color: #8B786D; margin-top: 5px;">Periode waktu reset kuota (misal: <code>72</code> jam = 3 hari).</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="box box-info" style="background: #1c1917; border-top-color: #BFA89E; border: 1px solid rgba(191, 168, 158, 0.15); border-radius: 8px;">
                                        <div class="box-header with-border" style="border-bottom: 1px solid rgba(191, 168, 158, 0.1);">
                                            <h3 class="box-title" style="color: #EBF5EE; font-weight: 600;"><i class="fa fa-wrench" style="margin-right: 8px; color: #BFA89E;"></i>Tes Koneksi AI</h3>
                                        </div>
                                        <div class="box-body" style="padding: 20px;">
                                            <p style="color: #BFA89E; font-size: 13px;">Uji keabsahan Base URL dan API Key secara langsung sebelum menyimpannya.</p>
                                            <button type="button" id="btnTestAi" class="btn btn-block btn-default" style="background: #25211e; border-color: #52b788; color: #52b788; font-weight: 600;">
                                                <i class="fa fa-play" style="margin-right: 6px;"></i> Ping &amp; Test AI Model
                                            </button>
                                            <div id="aiTestResult" style="margin-top: 15px; display: none; padding: 12px; border-radius: 6px; font-size: 12px; line-height: 1.4;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: DISCORD BOT & ROLE -->
                        <div class="tab-pane" id="tab_discord">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="box box-primary" style="background: #1c1917; border-top-color: #7289da; border: 1px solid rgba(191, 168, 158, 0.15); border-radius: 8px;">
                                        <div class="box-header with-border" style="border-bottom: 1px solid rgba(191, 168, 158, 0.1);">
                                            <h3 class="box-title" style="color: #EBF5EE; font-weight: 600;"><i class="fa fa-robot" style="margin-right: 8px; color: #7289da;"></i>Discord Bot &amp; Role Sync</h3>
                                        </div>
                                        <div class="box-body" style="padding: 20px;">
                                            <div class="form-group">
                                                <label class="control-label" style="color: #EBF5EE;">Discord Bot Token</label>
                                                <div class="input-group">
                                                    <input type="password" name="discord_bot_token" id="discord_bot_token" class="form-control" value="{{ $discord_bot_token }}" placeholder="MTQ..." style="background: #141211; border-color: rgba(191, 168, 158, 0.25); color: #EBF5EE;">
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default toggle-password" type="button" data-target="#discord_bot_token" style="background: #25211e; border-color: rgba(191, 168, 158, 0.25); color: #BFA89E;">
                                                            <i class="fa fa-eye"></i>
                                                        </button>
                                                    </span>
                                                </div>
                                                <p class="text-muted small" style="color: #8B786D; margin-top: 5px;">Bot Token dari Discord Developer Portal untuk otomatisasi role dan sinkronisasi status.</p>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label" style="color: #EBF5EE;">Discord Guild (Server) ID</label>
                                                        <input type="text" name="discord_guild_id" id="discord_guild_id" class="form-control" value="{{ $discord_guild_id }}" placeholder="1445078072414965985" style="background: #141211; border-color: rgba(191, 168, 158, 0.25); color: #EBF5EE;">
                                                        <p class="text-muted small" style="color: #8B786D; margin-top: 5px;">ID Server Discord Bytenodes.</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label" style="color: #EBF5EE;">Discord Client Role ID</label>
                                                        <input type="text" name="discord_role_id" class="form-control" value="{{ $discord_role_id }}" placeholder="1445078072830197841" style="background: #141211; border-color: rgba(191, 168, 158, 0.25); color: #EBF5EE;">
                                                        <p class="text-muted small" style="color: #8B786D; margin-top: 5px;">Role yang otomatis diberikan saat member mengeklaim role pelanggan.</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <hr style="border-color: rgba(191, 168, 158, 0.1);">
                                            <h4 style="color: #EBF5EE; font-size: 14px; font-weight: 600; margin-bottom: 15px;"><i class="fa fa-sign-in" style="margin-right: 6px; color: #BFA89E;"></i>OAuth2 Web Login Discord</h4>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label" style="color: #EBF5EE;">Discord OAuth2 Client ID</label>
                                                        <input type="text" name="discord_client_id" class="form-control" value="{{ $discord_client_id }}" placeholder="1484124938708324363" style="background: #141211; border-color: rgba(191, 168, 158, 0.25); color: #EBF5EE;">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label" style="color: #EBF5EE;">Discord OAuth2 Client Secret</label>
                                                        <div class="input-group">
                                                            <input type="password" name="discord_client_secret" id="discord_client_secret" class="form-control" value="{{ $discord_client_secret }}" placeholder="..." style="background: #141211; border-color: rgba(191, 168, 158, 0.25); color: #EBF5EE;">
                                                            <span class="input-group-btn">
                                                                <button class="btn btn-default toggle-password" type="button" data-target="#discord_client_secret" style="background: #25211e; border-color: rgba(191, 168, 158, 0.25); color: #BFA89E;">
                                                                    <i class="fa fa-eye"></i>
                                                                </button>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group" style="margin-top: 10px;">
                                                <label class="control-label" style="color: #EBF5EE;">OAuth2 Redirect Callback URL (Salin ke Developer Portal Discord)</label>
                                                <div class="input-group">
                                                    <input type="text" id="discord_redirect_url" readonly class="form-control" value="{{ $discord_redirect_url }}" style="background: #141211; border-color: rgba(191, 168, 158, 0.25); color: #52b788; font-family: monospace;">
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default copy-btn" type="button" data-target="#discord_redirect_url" style="background: #25211e; border-color: rgba(191, 168, 158, 0.25); color: #BFA89E;">
                                                            <i class="fa fa-copy"></i> Salin
                                                        </button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="box box-info" style="background: #1c1917; border-top-color: #7289da; border: 1px solid rgba(191, 168, 158, 0.15); border-radius: 8px;">
                                        <div class="box-header with-border" style="border-bottom: 1px solid rgba(191, 168, 158, 0.1);">
                                            <h3 class="box-title" style="color: #EBF5EE; font-weight: 600;"><i class="fa fa-check-circle" style="margin-right: 8px; color: #7289da;"></i>Uji Bot Discord</h3>
                                        </div>
                                        <div class="box-body" style="padding: 20px;">
                                            <p style="color: #BFA89E; font-size: 13px;">Periksa apakah token Bot dapat membaca identitas akun dan server Discord.</p>
                                            <button type="button" id="btnTestDiscord" class="btn btn-block btn-default" style="background: #25211e; border-color: #7289da; color: #7289da; font-weight: 600;">
                                                <i class="fa fa-robot" style="margin-right: 6px;"></i> Test Bot Connection
                                            </button>
                                            <div id="discordTestResult" style="margin-top: 15px; display: none; padding: 12px; border-radius: 6px; font-size: 12px; line-height: 1.4;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: WEBHOOKS -->
                        <div class="tab-pane" id="tab_webhooks">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="box box-primary" style="background: #1c1917; border-top-color: #dd9754; border: 1px solid rgba(191, 168, 158, 0.15); border-radius: 8px;">
                                        <div class="box-header with-border" style="border-bottom: 1px solid rgba(191, 168, 158, 0.1);">
                                            <h3 class="box-title" style="color: #EBF5EE; font-weight: 600;"><i class="fa fa-bell" style="margin-right: 8px; color: #dd9754;"></i>Discord Webhooks Hub</h3>
                                        </div>
                                        <div class="box-body" style="padding: 20px;">
                                            <div class="form-group">
                                                <label class="control-label" style="color: #EBF5EE;">Customer Review / Testimonial Webhook</label>
                                                <input type="url" name="discord_review_webhook" class="form-control" value="{{ $discord_review_webhook }}" placeholder="https://discord.com/api/webhooks/..." style="background: #141211; border-color: rgba(191, 168, 158, 0.25); color: #EBF5EE;">
                                                <p class="text-muted small" style="color: #8B786D; margin-top: 5px;">Menerima notifikasi embed bintang dan ulasan testimoni saat klien mengirim rating di panel.</p>
                                            </div>

                                            <div class="form-group">
                                                <label class="control-label" style="color: #EBF5EE;">Panel Activity &amp; Audit Logs Webhook</label>
                                                <input type="url" name="discord_activity_webhook" class="form-control" value="{{ $discord_activity_webhook }}" placeholder="https://discord.com/api/webhooks/..." style="background: #141211; border-color: rgba(191, 168, 158, 0.25); color: #EBF5EE;">
                                                <p class="text-muted small" style="color: #8B786D; margin-top: 5px;">Menerima log aktivitas server, node, dan tindakan administratif.</p>
                                            </div>

                                            <div class="form-group">
                                                <label class="control-label" style="color: #EBF5EE;">Deploy &amp; System Maintenance Webhook</label>
                                                <input type="url" name="discord_deploy_webhook" class="form-control" value="{{ $discord_deploy_webhook }}" placeholder="https://discord.com/api/webhooks/..." style="background: #141211; border-color: rgba(191, 168, 158, 0.25); color: #EBF5EE;">
                                                <p class="text-muted small" style="color: #8B786D; margin-top: 5px;">Menerima notifikasi otomatis saat pembaruan fitur panel atau webhook deploy dieksekusi.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="box" style="background: #1c1917; border-top-color: #BFA89E; border: 1px solid rgba(191, 168, 158, 0.15); border-radius: 8px;">
                                        <div class="box-header with-border" style="border-bottom: 1px solid rgba(191, 168, 158, 0.1);">
                                            <h3 class="box-title" style="color: #EBF5EE; font-weight: 600;"><i class="fa fa-info-circle" style="margin-right: 8px; color: #BFA89E;"></i>Informasi Webhook</h3>
                                        </div>
                                        <div class="box-body" style="padding: 20px; color: #BFA89E; font-size: 13px; line-height: 1.6;">
                                            <p>Semua webhook di atas dapat dibuat langsung di Discord Channel Settings &rarr; Integrations &rarr; Webhooks.</p>
                                            <p>Data yang tersimpan di sini akan diprioritaskan oleh sistem panel menggantikan nilai default yang ada di file <code>.env</code>.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FLOATING / BOTTOM SUBMIT BAR -->
                <div style="background: #1c1917; border: 1px solid rgba(191, 168, 158, 0.2); padding: 15px 20px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                    <span style="color: #8B786D; font-size: 13px;"><i class="fa fa-shield" style="margin-right: 6px; color: #52b788;"></i>Perubahan akan langsung berlaku pada seluruh layanan tanpa perlu restart server.</span>
                    <button type="submit" class="btn btn-primary" style="background: #BFA89E; border-color: #BFA89E; color: #141211; font-weight: 700; padding: 8px 24px;">
                        <i class="fa fa-save" style="margin-right: 6px;"></i> Simpan Semua Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $(function() {
            // Password Toggle
            $('.toggle-password').on('click', function() {
                var targetId = $(this).data('target');
                var $target = $(targetId);
                var isPassword = $target.attr('type') === 'password';
                $target.attr('type', isPassword ? 'text' : 'password');
                $(this).find('i').toggleClass('fa-eye fa-eye-slash');
            });

            // Preset Model Dropdown
            $('.preset-model').on('click', function(e) {
                e.preventDefault();
                $('#ai_model').val($(this).data('val'));
            });

            // Copy Button
            $('.copy-btn').on('click', function() {
                var targetId = $(this).data('target');
                var $target = $(targetId);
                $target.select();
                document.execCommand('copy');
                var $btn = $(this);
                $btn.html('<i class="fa fa-check"></i> Tersalin!');
                setTimeout(function() {
                    $btn.html('<i class="fa fa-copy"></i> Salin');
                }, 2000);
            });

            // Test AI Connection AJAX
            $('#btnTestAi').on('click', function() {
                var $btn = $(this);
                var $res = $('#aiTestResult');
                $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menguji koneksi AI...');
                $res.hide().removeClass('bg-green bg-red');

                $.ajax({
                    url: '{{ route("admin.settings.integrations.test_ai") }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        base_url: $('#ai_base_url').val(),
                        api_key: $('#ai_api_key').val(),
                        model: $('#ai_model').val()
                    },
                    success: function(response) {
                        $btn.prop('disabled', false).html('<i class="fa fa-play"></i> Ping &amp; Test AI Model');
                        $res.show().css({
                            'background': 'rgba(82, 183, 136, 0.15)',
                            'border': '1px solid #52b788',
                            'color': '#EBF5EE'
                        }).html('<strong><i class="fa fa-check-circle" style="color: #52b788;"></i> Berhasil Terhubung!</strong><br>' +
                                'Model: <code>' + response.model + '</code><br>' +
                                'Latency: <strong>' + response.latency_ms + ' ms</strong><br>' +
                                'Respon: <em>' + response.reply + '</em>');
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).html('<i class="fa fa-play"></i> Ping &amp; Test AI Model');
                        var err = xhr.responseJSON ? (xhr.responseJSON.error || JSON.stringify(xhr.responseJSON)) : 'Gagal menghubungi endpoint AI.';
                        $res.show().css({
                            'background': 'rgba(201, 75, 75, 0.15)',
                            'border': '1px solid #c94b4b',
                            'color': '#ffb4b4'
                        }).html('<strong><i class="fa fa-times-circle" style="color: #c94b4b;"></i> Uji Coba Gagal!</strong><br><small>' + err + '</small>');
                    }
                });
            });

            // Test Discord Bot AJAX
            $('#btnTestDiscord').on('click', function() {
                var $btn = $(this);
                var $res = $('#discordTestResult');
                $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memeriksa bot...');
                $res.hide();

                $.ajax({
                    url: '{{ route("admin.settings.integrations.test_discord") }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        bot_token: $('#discord_bot_token').val(),
                        guild_id: $('#discord_guild_id').val()
                    },
                    success: function(response) {
                        $btn.prop('disabled', false).html('<i class="fa fa-robot"></i> Test Bot Connection');
                        $res.show().css({
                            'background': 'rgba(82, 183, 136, 0.15)',
                            'border': '1px solid #52b788',
                            'color': '#EBF5EE'
                        }).html('<strong><i class="fa fa-check-circle" style="color: #52b788;"></i> Bot Terverifikasi!</strong><br>' +
                                'Username: <strong>' + response.bot_username + '</strong><br>' +
                                'Server: <strong>' + (response.guild_name || 'Terhubung (Guild Valid)') + '</strong>');
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).html('<i class="fa fa-robot"></i> Test Bot Connection');
                        var err = xhr.responseJSON ? (xhr.responseJSON.error || JSON.stringify(xhr.responseJSON)) : 'Gagal menghubungi Discord API.';
                        $res.show().css({
                            'background': 'rgba(201, 75, 75, 0.15)',
                            'border': '1px solid #c94b4b',
                            'color': '#ffb4b4'
                        }).html('<strong><i class="fa fa-times-circle" style="color: #c94b4b;"></i> Bot Tidak Valid!</strong><br><small>' + err + '</small>');
                    }
                });
            });
        });
    </script>
@endsection
