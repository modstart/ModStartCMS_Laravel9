<header class="ub-header-b">
    <div class="ub-container">
        <div class="menu">
            @if(\Module\Member\Auth\MemberUser::id())
                <a href="{{modstart_web_url('member')}}"><i class="iconfont icon-user"></i> {{\Module\Member\Auth\MemberUser::get('username')}}</a>
            @else
                <a href="{{modstart_web_url('login',['redirect'=>\ModStart\Core\Input\Request::redirectUrl()])}}" rel="nofollow">
                    <i class="iconfont icon-user"></i> 
                    登录
                </a>
            @endif
        </div>
        <div class="logo">
            <a href="{{modstart_web_url('')}}">
                <img src="{{\ModStart\Core\Assets\AssetsUtil::fix(modstart_config('siteLogo'))}}"/>
            </a>
        </div>
        <div class="nav-mask" onclick="MS.header.hide()"></div>
        <div class="nav">
            <div class="search">
                <div class="box">
                    <form action="{{modstart_web_url('search')}}" method="get">
                        <input type="text" name="keywords" value="{{empty($keywords)?'':$keywords}}" placeholder="搜索内容"/>
                        <button type="submit"><i class="iconfont icon-search"></i></button>
                    </form>
                </div>
            </div>
            {!! \Module\Nav\View\NavView::position('head') !!}
        </div>
        <a class="nav-toggle" href="javascript:;" onclick="MS.header.trigger()">
            <i class="show iconfont icon-list"></i>
            <i class="close iconfont icon-close"></i>
        </a>
    </div>
</header>
