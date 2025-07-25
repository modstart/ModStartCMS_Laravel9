@extends($_viewFrame)

@section('pageTitleMain')开通VIP会员@endsection
@section('pageKeywords')开通VIP会员@endsection
@section('pageDescription')开通VIP会员@endsection

{!! \ModStart\ModStart::css('vendor/Member/style/member.css') !!}
{!! \ModStart\ModStart::css('asset/vendor/swiper/swiper.css') !!}
{!! \ModStart\ModStart::js('asset/vendor/swiper/swiper.js') !!}

@section('headAppend')
    @parent
    <style>
        .vip-list .item .item-active-show{ display:none; }
        .vip-list .item.active .item-active-show{ display:block; }
    </style>
@endsection

@section('bodyAppend')
    @parent
    <script>
        window.__data = {
            countDownSeconds: {!! \ModStart\Core\Util\SerializeUtil::jsonEncode(modstart_config('Member_VipCountDown',1800)) !!}
        };
    </script>
    <script src="@asset('vendor/Member/script/memberVip.js')"></script>
@endsection

@section('bodyContent')

    <div class="ub-container lg:tw-mt-4 tw-mt-1">

        <div class="pb-page-member-vip">
            <div class="top">
                <div>
                    <div class="member-info tw-flex tw-items-center">
                        <div class="tw-w-12 tw-mr-3">
                            <div class="ub-cover-1-1 tw-w-12 tw-rounded-full"
                                 style="background-image:url({{\ModStart\Core\Assets\AssetsUtil::fix($_memberUser?$_memberUser['avatar']:'asset/image/avatar.svg')}});"></div>
                        </div>
                        <div class="tw-mr-3">
                            <div class="">
                                {{$_memberUser?\Module\Member\Util\MemberUtil::viewName($_memberUser):'未登录'}}
                            </div>
                            <div class="tw-mt-1">
                                @if(empty($_memberUser))
                                    <a href="{{modstart_web_url('login',['redirect'=>\ModStart\Core\Input\Request::currentPageUrl()])}}"
                                       class="vip-button">
                                        注册/登录
                                    </a>
                                @else
                                    <a href="{{modstart_web_url('member')}}"
                                       class="vip-button">
                                        用户中心
                                    </a>
                                    @if(!empty(\Module\Member\Auth\MemberVip::get()))
                                        <div class="tw-inline-block tw-ml-3">
                                            您当前是
                                            <span class="vip-text ub-text-bold">
                                            {{\Module\Member\Auth\MemberVip::get('title')}}
                                        </span>
                                            @if(!\Module\Member\Auth\MemberVip::isDefault())
                                                ，
                                                过期时间为：{{\Module\Member\Auth\MemberUser::get('vipExpire')}}
                                            @endif
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="body">
                <div class="pb-member-vip">
                    @if(count($memberVipGroupNames)>1)
                        <div class="vip-group">
                            @foreach($memberVipGroupNames as $mvIndex=>$mv)
                                <a class="vip-group-item {{$mvIndex===0?'active':''}}" data-vip-group="{{$mv}}" href="javascript:;">
                                    {{$mv}}
                                </a>
                            @endforeach
                        </div>
                    @endif
                    <form action="{{$__msRoot}}api/member_vip/buy" method="post" data-ajax-form>
                        <input type="hidden" name="redirect" value="{{\ModStart\Core\Input\Request::currentPageUrl()}}" />
                        <input type="hidden" name="vipId" value="0" />
                        <input type="hidden" name="voucherId" value="0" />
                        <div class="vip-list-container-box margin-bottom">
                            <div class="vip-list-container">
                                <div class="vip-list vip-bg tw-rounded-xl">
                                    @foreach($memberVips as $memberVip)
                                        @if(!$memberVip['isDefault'])
                                            <div class="item tw-relative tw-invisible" data-vip-group="{{$memberVip['groupName']?$memberVip['groupName']:'VIP'}}" data-vip-id="{{$memberVip['id']}}">
                                                <div class="tw-text-xl tw-font-bold margin-bottom tw-pt-2">
                                                    {{$memberVip['title']}}
                                                </div>
                                                <div class="margin-bottom">
                                                    <div class="tw-text-lg tw-font-bold">
                                                        ￥{{$memberVip['price']}}
                                                        <span class="ub-text-xs">元</span>
                                                    </div>
                                                    <div class="tw-line-through tw-text-gray-500 ub-text-sm">
                                                        ￥{{$memberVip['priceMarket']}}
                                                    </div>
                                                </div>
                                                <div>
                                                    {{$memberVip['desc']?$memberVip['desc']:'[会员简要说明]'}}
                                                </div>
                                                @if($memberVip['priceMarket']>$memberVip['price'])
                                                    <div class="item-active-show tw-absolute tw-left-0 tw-top-0 tw-p-1 tw-text-sm tw-bg-red-500 tw-rounded-tl-lg tw-rounded-br-lg tw-text-white">
                                                        限时立减 {{bcsub($memberVip['priceMarket'],$memberVip['price'],2)}}
                                                    </div>
                                                @endif
                                                <div class="tw-mt-4">
                                                    @if(\Module\Member\Auth\MemberUser::isLogin())
                                                        <a href="javascript:;"
                                                           onclick="__openVip({{$memberVip['id']}})"
                                                           class="btn btn-block btn-lg btn-vip btn-round">
                                                            <i class="iconfont icon-vip"></i>
                                                            立即开通
                                                        </a>
                                                    @else
                                                        <a class="btn btn-block btn-lg btn-vip btn-round"
                                                           href="{{modstart_web_url('login',['redirect'=>\ModStart\Core\Input\Request::currentPageUrl()])}}">
                                                            <i class="iconfont icon-vip"></i>
                                                            登录后开通
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                                <a href="javascript:;" class="nav left">
                                    <i class="iconfont icon-angle-left"></i>
                                </a>
                                <a href="javascript:;" class="nav right">
                                    <i class="iconfont icon-angle-right"></i>
                                </a>
                            </div>
                        </div>
                        <div class="lg:tw-px-12 lg:tw-text-left tw-text-center tw-py-4 vip-bg tw-rounded-xl margin-bottom tw-flex lg:tw-flex-row tw-flex-col tw-items-start">
                            <div class="tw-flex-grow tw-w-full">
                                @if(!empty($memberVipRights))
                                    <div class="vip-bg tw-rounded-lg lg:tw-mx-0 tw-mx-3 tw-text-left" data-vip-right-list>
                                        <div class="row">
                                            @foreach($memberVipRights as $r)
                                                <div class="col-md-4 col-6" style="display:none;" data-vip-right="{{join(',',$r['vipIds'])}}">
                                                    <div class="tw-flex tw-py-2 tw-items-center ub-text-sm margin-bottom">
                                                        <div class="tw-pr-2">
                                                            <img alt="用户头像" class="tw-w-8 tw-h-8 tw-object-cover tw-rounded-full" src="{{$r['image']}}" />
                                                        </div>
                                                        <div>
                                                            <div class="vip-text">{{$r['title']}}</div>
                                                            <div class="ub-text-tertiary">{{$r['desc']}}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                                @if(!empty($memberFunctions))
                                    <div class="tw-mx-3 lg:tw-mx-0 tw-mb-3 tw-mt-3 tw-overflow-auto">
                                        <table class="ub-table border tw-bg-white ub-text-truncate ub-text-center">
                                            <thead>
                                            <tr>
                                                @foreach($memberFunctions[0] as $mf)
                                                    <th>
                                                        {{$mf}}
                                                    </th>
                                                @endforeach
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @for($i=1;$i<count($memberFunctions);$i++)
                                                <tr>
                                                    @foreach($memberFunctions[$i] as $mf)
                                                        <td>
                                                            {{$mf}}
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endfor
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                            <div>
                                <div class="tw-inline-block tw-py-1 tw-px-3">
                                    <div data-vip-open-list class="swiper tw-overflow-hidden tw-h-48 tw-w-64 tw--mb-3" style="overflow:hidden;">
                                        <div class="swiper-wrapper">
                                            @foreach(modstart_config('Member_VipOpenUsers',[]) as $u)
                                                <div class="swiper-slide">
                                                    <div class="tw-flex tw-items-center tw-bg-white tw-rounded-full tw-px-3 tw-py-1">
                                                        <div class="tw-w-20 tw-text-left">{{mb_substr($u['name'],0,2)}}******</div>
                                                        <div class="tw-w-8 tw-text-yellow-400">{{$u['time']}}</div>
                                                        <div class="tw-w-32 tw-text-right tw-truncate">购买了 {{$u['title']}}</div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tw-rounded-xl lg:tw-px-8 vip-bg vip-content-list">
                            @foreach($memberVips as $memberVip)
                                @if(!$memberVip['isDefault'])
                                    <div class="item tw-hidden">
                                        <div class="tw-p-4 tw-text-lg vip-text">{{$memberVip['title']}}</div>
                                        <div class="tw-pb-4 tw-px-4 ">
                                            <div class="ub-html lg">
                                                {!! $memberVip['content'] !!}
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="tw-h-3"></div>

    </div>

    @include('module::Member.View.pc.memberVip.openDialog')

@endsection
