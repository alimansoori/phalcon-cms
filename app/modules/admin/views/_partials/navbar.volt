<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <a href="{{html.admin_url('')}}" class="navbar-brand">{{config.name}} ADMIN</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item {{dispatcher.getControllerName() == 'user' ? ' active': ''}}">
                <a class="nav-link" href="{{html.admin_url('user')}}">
                    {{t._('user')}}
                </a>
            </li>
            <li class="nav-item {{dispatcher.getControllerName() == 'site' ? 'active': ''}}">
                <a class="nav-link" href="{{html.admin_url('site')}}">
                    {{t._('site')}}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    Menu3
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    Menu4
                </a>
            </li>
        </ul>
    </div>
</nav>
