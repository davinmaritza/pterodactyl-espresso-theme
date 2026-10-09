@include('partials/admin.settings.notice')

@section('settings::nav')
    @yield('settings::notice')
    <div class="row">
        <div class="col-xs-12">
            <div class="nav-tabs-custom nav-tabs-floating">
                <ul class="nav nav-tabs">
                    <li @if($activeTab === 'basic')class="active"@endif><a href="{{ route('admin.settings') }}">General</a></li>
                    <li @if($activeTab === 'links')class="active"@endif><a href="{{ route('admin.settings.links') }}"><i class="fa fa-paint-brush" style="margin-right: 4px; color: #BFA89E;"></i> ByteNodes UI Editor</a></li>
                    <li @if($activeTab === 'integrations')class="active"@endif><a href="{{ route('admin.settings.integrations') }}"><i class="fa fa-plug" style="margin-right: 4px; color: #52b788;"></i> API &amp; Integrations</a></li>
                    <li @if($activeTab === 'mail')class="active"@endif><a href="{{ route('admin.settings.mail') }}">Mail</a></li>
                    <li @if($activeTab === 'advanced')class="active"@endif><a href="{{ route('admin.settings.advanced') }}">Advanced</a></li>
                </ul>
            </div>
        </div>
    </div>
@endsection
