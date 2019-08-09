@extends('layouts.main')

@section('styles')
    <style>
        .input-sm {
            height: 30px;
            line-height: 30px;
        }

        .label_item {
            text-align: left !important;
            padding-left: 50px !important;
        }

        .project-information-table tr td {
            border-bottom: 1px solid !important;
            border-left: 0 !important;
            border-right: 0 !important;
            border-top: 0 !important;
            font-size: 13px !important;
            padding: 5px !important;
        }

        .project-information-table tr td:first-child {
            font-weight: bold;
        }

        #buyer_profile_chart_div {
            width: 100%;
            height: 500px;
        }

        .configure_panel label {
            font-weight: bold;
        }


        input[type="checkbox"]:checked + label::before {
            background-color: #428bca;
            border-color: #428bca;
            margin-top: 3px !important;
        }

        .checkbox label::before {
            margin-top: 3px !important;
        }

        input[type="checkbox"]:checked + label::after {
            color: #fff;
        }

        g[aria-labelledby="id-66-title"] {
            display: none;
        }
    </style>

    <link href="{{ asset('plugin/checkbox/build.less.css') }}" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.10.19/css/dataTables.bootstrap.min.css" rel="stylesheet">
@endsection

@section('page_title', 'REPORT | LANDED RESIDENTIAL')

@section('contents')
    @php
           $config_data_josn =  \Illuminate\Support\Facades\Cookie::get(\App\Service\GlobalConstant::REPORT_LANDED_CONFIG_COOKIE);
               $config_data = json_decode($config_data_josn, true);
           if ($config_data) {
               if ($config_data['timeframe']) {
                   $timeframe = 'Last ' . $config_data['timeframe'] . ' Years';
               } else {
                   $timeframe = 'All Years';
               }
           } else {
               $timeframe = 'Last 5 Years';
           }


           $project_list = \App\Service\LandedService::getTransactionProjectList($project['Project Name']);
           $project_detail = \App\Service\GlobalService::getProject($project['Project Name']);
           $project_list_6_month = $project_list->filter(function ($item){
                      return \Carbon\Carbon::parse(\App\Service\GlobalService::getNormalDateString($item['Sale Date']))->diffInMonths(\Carbon\Carbon::now()) <= 6;
                })->values();



            $profile_data = \App\Service\LandedService::getBuyerProfileData($project['Project Name']);


           $residental_rental = \App\Service\LandedService::getRentalData($project['Project Name']);
           $residental_rental = $residental_rental->map(function ($item) {
               if ($item['Floor Area ll']) {
                $item['rental'] = $item['Monthly Gross Rent($)']/$item['Floor Area ll'];
               } else {
                $item['rental'] = null;
               }

                return $item;
           });



           $residental_rental_6_month = $residental_rental->filter(function ($item){
                  return \Carbon\Carbon::parse($item['Lease Commencement Date'])->diffInMonths(\Carbon\Carbon::now()) <= 6;
            })->values();


           $average_rental = $residental_rental->sortBy('Floor Area (sq ft)')->groupBy('Floor Area (sq ft)')->values();

           $nearby_items = \App\Service\LandedService::getNearByProperties($project['Address']);
           $nearby_items = \App\Service\GlobalService::getDistanceAndMarker($nearby_items, $project_detail);


            /*if(!isset($config_data) || (isset($config_data) && isset($config_data['hide_unit_numbers']))) {
                $project_list = $project_list->map(function ($item) {

                });
            }*/

    @endphp
    <!-- Title, Breadcrumb Start-->
    <div class="breadcrumb-wrapper">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 col-md-6 col-xs-12 col-sm-6">
                    <h2 class="title">{{ $project['Project Name'] }}</h2>
                </div>
                <div class="col-lg-6 col-md-6 col-xs-12 col-sm-6">
                    <div class="breadcrumbs pull-right">
                        <ul>
                            <li>You are here:</li>
                            <li><a href="index.html">Residential and Analysis</a></li>
                            {{-- <li><a href="#">Pages</a></li> --}}
                            <li>Report</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content start-->
    <div class="content">
        <div class="container">
            <div class="row">
                <div class="posts-block col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <article>
                        <h3 class="title">Overview</h3>
                        <div class="post-content">
                            <div class="accordionMod panel-group">
                                <div class="accordion-item">
                                    <h4 class="accordion-toggle">CONFIGURE REPORT</h4>
                                    <section class="accordion-inner panel-body form-horizontal configure_panel">
                                        <form method="POST"
                                              action="{{ url('trends-and-analysis/landed/report/refresh_setting') }}"
                                              class="report_setting_form">
                                            @csrf
                                            <div class="form-group">
                                                <label class="control-label col-sm-2 text-left label_item">1. TIME
                                                    PERIOD:</label>
                                                <div class="col-sm-2">
                                                    <select id="timeframe" name="timeframe"
                                                            class="form-control input-sm">
                                                        <option value="">All data</option>
                                                        <option value="10">Last 10 years</option>
                                                        <option value="9">Last 9 years</option>
                                                        <option value="8">Last 8 years</option>
                                                        <option value="7">Last 7 years</option>
                                                        <option value="6">Last 6 years</option>
                                                        <option value="5"  selected>Last 5 years</option>
                                                        <option value="4">Last 4 years</option>
                                                        <option value="3">Last 3 years</option>
                                                        <option value="2">Last 2 years</option>
                                                        <option value="1">Last 1 year</option>
                                                    </select>
                                                </div>
                                            </div>


                                            <div class="form-group">
                                                <label class="control-label col-sm-4 text-left label_item">2. PROPERTY
                                                    TYPES:</label>

                                            </div>
                                            <div class="form-group" style="padding: 0 30px;">
                                                <div class="col-md-3 col-sm-3 checkbox">
                                                    <input type="checkbox" id="detached_house" name="detached_house"
                                                           @if(isset($config_data) && isset($config_data['detached_house']))
                                                           {{ $config_data['detached_house'] }}
                                                           @elseif(isset($config_data) && !isset($config_data['detached_house']))
                                                           @else checked @endif>
                                                    <label for="detached_house">Detached House</label>
                                                </div>

                                                <div class="col-md-3 col-sm-3 checkbox">
                                                    <input type="checkbox" id="semi_detached_house" name="semi_detached_house"
                                                           @if(isset($config_data) && isset($config_data['semi_detached_house'])) {{ $config_data['semi_detached_house'] }} @elseif(isset($config_data) && !isset($config_data['semi_detached_house'])) @else checked @endif>
                                                    <label for="semi_detached_house">Semi-Detached House</label>
                                                </div>
                                                <div class="col-md-3 col-sm-3 checkbox">
                                                    <input type="checkbox" id="terrace_house"
                                                           name="terrace_house"
                                                           @if(isset($config_data) && isset($config_data['terrace_house'])) {{ $config_data['terrace_house'] }} @elseif(isset($config_data) && !isset($config_data['terrace_house'])) @else checked @endif>
                                                    <label for="terrace_house">Terrace House</label>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="control-label text-left label_item">3. INFORMATION TO
                                                    INCLUDE: </label>
                                            </div>
                                            <div class="form-group" style="padding: 0 30px;">
                                                {{--<div class="col-md-3 col-sm-3 checkbox">--}}
                                                    {{--<input type="checkbox" id="developer_sales" name="developer_sales"--}}
                                                           {{--@if(isset($config_data) && isset($config_data['developer_sales']))--}}
                                                           {{--{{ $config_data['developer_sales'] }}--}}
                                                           {{--@elseif(isset($config_data) && !isset($config_data['developer_sales']))--}}
                                                           {{--@else checked @endif>--}}
                                                    {{--<label for="developer_sales">Developer Sales</label>--}}
                                                {{--</div>--}}

                                                <div class="col-md-3 col-sm-3 checkbox">
                                                    <input type="checkbox" id="buyer_profile" name="buyer_profile"
                                                           @if(isset($config_data) && isset($config_data['buyer_profile'])) {{ $config_data['buyer_profile'] }} @elseif(isset($config_data) && !isset($config_data['buyer_profile'])) @else checked @endif>
                                                    <label for="buyer_profile">Buyer Profile</label>
                                                </div>
                                                <div class="col-md-3 col-sm-3 checkbox">
                                                    <input type="checkbox" id="historical_prices_chart"
                                                           name="historical_prices_chart"
                                                           @if(isset($config_data) && isset($config_data['historical_prices_chart'])) {{ $config_data['historical_prices_chart'] }} @elseif(isset($config_data) && !isset($config_data['historical_prices_chart'])) @else checked @endif>
                                                    <label for="historical_prices_chart">Historical
                                                        Prices(Chart)</label>
                                                </div>
                                                <div class="col-md-3 col-sm-3 checkbox">
                                                    <input type="checkbox" id="historical_range_chart"
                                                           name="historical_range_chart"
                                                           @if(isset($config_data) && isset($config_data['historical_range_chart'])) {{ $config_data['historical_range_chart'] }} @elseif(isset($config_data) && !isset($config_data['historical_range_chart'])) @else checked @endif>
                                                    <label for="historical_range_chart">Historical Range(Chart)</label>
                                                </div>
                                                <div class="col-md-3 col-sm-3 checkbox">
                                                    <input type="checkbox" id="profitable_transactions"
                                                           name="profitable_transactions"
                                                           @if(isset($config_data) && isset($config_data['profitable_transactions'])) {{ $config_data['profitable_transactions'] }} @elseif(isset($config_data) && !isset($config_data['profitable_transactions'])) @else checked @endif>
                                                    <label for="profitable_transactions">Profitable Transactions</label>
                                                </div>
                                                <div class="col-md-3 col-sm-3 checkbox">
                                                    <input type="checkbox" id="unprofitable_transactions"
                                                           name="unprofitable_transactions"
                                                           @if(isset($config_data) && isset($config_data['unprofitable_transactions'])) {{ $config_data['unprofitable_transactions'] }} @elseif(isset($config_data) && !isset($config_data['unprofitable_transactions'])) @else checked @endif>
                                                    <label for="unprofitable_transactions">Unprofitable
                                                        Transactions</label>
                                                </div>

                                                <div class="col-md-3 col-sm-3 checkbox">
                                                    <input type="checkbox" id="rental_contracts" name="rental_contracts"
                                                           @if(isset($config_data) && isset($config_data['rental_contracts'])) {{ $config_data['rental_contracts'] }} @elseif(isset($config_data) && !isset($config_data['rental_contracts'])) @else checked @endif>
                                                    <label for="rental_contracts">Rental Contracts</label>
                                                </div>

                                                <div class="col-md-3 col-sm-3 checkbox">
                                                    <input type="checkbox" id="quanterly_rental" name="quanterly_rental"
                                                           @if(isset($config_data) && isset($config_data['quanterly_rental'])) {{ $config_data['quanterly_rental'] }} @elseif(isset($config_data) && !isset($config_data['quanterly_rental'])) @else checked @endif>
                                                    <label for="quanterly_rental">Quarterly Rental</label>
                                                </div>
                                                <div class="col-md-3 col-sm-3 checkbox">
                                                    <input type="checkbox" id="street_rental" name="street_rental"
                                                           @if(isset($config_data) && isset($config_data['street_rental'])) {{ $config_data['street_rental'] }} @elseif(isset($config_data) && !isset($config_data['street_rental'])) @else checked @endif>
                                                    <label for="street_rental">Street Rental</label>
                                                </div>
                                                {{--<div class="col-md-3 col-sm-3 checkbox">--}}
                                                    {{--<input type="checkbox" id="unit_size_distribution"--}}
                                                           {{--name="unit_size_distribution"--}}
                                                           {{--@if(isset($config_data) && isset($config_data['unit_size_distribution'])) {{ $config_data['unit_size_distribution'] }} @elseif(isset($config_data) && !isset($config_data['unit_size_distribution'])) @else checked @endif>--}}
                                                    {{--<label for="unit_size_distribution">Unit Size Distribution</label>--}}
                                                {{--</div>--}}
                                                <div class="col-md-3 col-sm-3 checkbox">
                                                    <input type="checkbox" id="nearby_prices_chart"
                                                           name="nearby_prices_chart"
                                                           @if(isset($config_data) && isset($config_data['nearby_prices_chart'])) {{ $config_data['nearby_prices_chart'] }} @elseif(isset($config_data) && !isset($config_data['nearby_prices_chart'])) @else checked @endif>
                                                    <label for="nearby_prices_chart">Nearby Prices (Chart)</label>
                                                </div>
                                                <div class="col-md-3 col-sm-3 checkbox">
                                                    <input type="checkbox" id="nearby_rental_chart"
                                                           name="nearby_rental_chart"
                                                           @if(isset($config_data) && isset($config_data['nearby_rental_chart'])) {{ $config_data['nearby_rental_chart'] }} @elseif(isset($config_data) && !isset($config_data['nearby_rental_chart'])) @else checked @endif>
                                                    <label for="nearby_rental_chart">Nearby Rental (Chart)</label>
                                                </div>
                                                <div class="col-md-3 col-sm-3 checkbox">
                                                    <input type="checkbox" id="nearby_comparison"
                                                           name="nearby_comparison"
                                                           @if(isset($config_data) && isset($config_data['nearby_comparison'])) {{ $config_data['nearby_comparison'] }} @elseif(isset($config_data) && !isset($config_data['nearby_comparison'])) @else checked @endif>
                                                    <label for="nearby_comparison">Nearby Comparison</label>
                                                </div>
                                                <div class="col-md-3 col-sm-3 checkbox">
                                                    <input type="checkbox" id="historical_transactions"
                                                           name="historical_transactions"
                                                           @if(isset($config_data) && isset($config_data['historical_transactions'])) {{ $config_data['historical_transactions'] }} @elseif(isset($config_data) && !isset($config_data['historical_transactions'])) @else checked @endif>
                                                    <label for="historical_transactions">Historical Transactions</label>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="control-label col-sm-3 text-left label_item">4. HIDE UNIT
                                                    NUMBERS:</label>
                                                <div class="col-sm-2">
                                                    <select id="hide_unit_numbers" name="hide_unit_numbers"
                                                            class="form-control input-sm">
                                                        <option value="0" selected="selected">No</option>
                                                        <option value="1">Yes</option>
                                                    </select>
                                                </div>
                                            </div>

                                            {{--<div class="form-group">--}}
                                            {{--<label class="control-label col-sm-3 text-left label_item">7. ANONYMISE--}}
                                            {{--LISTINGS:</label>--}}
                                            {{--<div class="col-sm-2">--}}
                                            {{--<select id="anonymise_listings" name="anonymise_listings"--}}
                                            {{--class="form-control input-sm">--}}
                                            {{--<option value="0" selected="selected">No</option>--}}
                                            {{--<option value="1">Yes</option>--}}
                                            {{--</select>--}}
                                            {{--</div>--}}
                                            {{--</div>--}}

                                            <div class="form-group">
                                                <label class="control-label text-left label_item">
                                                    <button type="submit" class="btn btn-sm btn-primary"
                                                            id="refresh_report">REFRESH REPORT
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-success"
                                                            id="printer_friendly">
                                                        PRINTER FRIENDLY
                                                    </button>
                                                    <a type="button" class="btn btn-sm btn-danger"
                                                       id="print_to_pdf"
                                                       href="{{ url('/trends-and-analysis/residential/report/pdf?p=' . $project['Project Name']) }}">PRINTER
                                                        TO PDF
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-info"
                                                            id="save_report_settings">SAVE REPORT SETTINGS
                                                    </button>
                                                </label>
                                            </div>
                                        </form>
                                    </section>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </div>
    <div class="divider"></div>
    <!-- Project Information -->
    <div class="services-big">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <h3 class="title"> Project information&nbsp;</h3>
                </div>
                <div class="clearfix"></div>
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                    <div class="content-box">
                        <h4 class="text-center">PROJECT INFORMATION</h4>
                        <table class="table project-information-table">
                            <tbody>
                            <tr>
                                <td>Project Name</td>
                                <td>{{ $project['Project Name'] }}</td>
                            </tr>
                            <tr>
                                <td>STREET NAME</td>
                                <td>{{ \App\Service\GlobalService::getStreetFromAddress($project['Address']) }}</td>
                            </tr>
                            <tr>
                                <td>PROPERTY TYPE</td>
                                <td>{{ $project['Property Type'] }}</td>
                            </tr>
                            <tr>
                                <td>TENURE</td>
                                <td>{{ $project['Tenure'] }}</td>
                            </tr>
                            <tr>
                                <td>DISTRICT / PLANNING AREA</td>
                                <td>{{ 'D' . $project['Postal District'] . ' / ' . $project['Planning Area'] }}</td>
                            </tr>
                            <tr>
                                <td>COMPLETION</td>
                                <td>{{ $project['Completion Date'] }}</td>
                            </tr>
                            <tr>
                                <td>NUMBER OF UNITS</td>
                                <td>{{ $project['No_of_Unit'] }} UNITS</td>
                            </tr>
                            <tr>
                                <td>INDICATIVE PRICE RANGE / AVERAGE*</td>
                                <td>
                                    @if(count($project_list_6_month) > 0)
                                        S${{ $project_list_6_month->min('Unit Price ($ psf)') }}
                                        -
                                        S$ {{ $project_list_6_month->max('Unit Price ($ psf)') }}
                                        PSF /
                                        S$ {{ round($project_list_6_month->average('Unit Price ($ psf)'), 2) }}
                                        PSF
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>INDICATIVE RENTAL RANGE / AVERAGE*</td>
                                <td>
                                    @if(count($residental_rental_6_month) > 0)
                                        S$ {{ $residental_rental_6_month->min('rental') }}
                                        -
                                        S$ {{ $residental_rental_6_month->max('rental') }}
                                        PSF PM /
                                        S$ {{ round($residental_rental_6_month->average('rental'), 2) }}
                                        PSF PM
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>IMPLIED RENTAL YIELD</td>
                                <td>
                                    @if(count($residental_rental_6_month) > 0 && count($project_list_6_month)>0)
                                        {{ round($residental_rental_6_month->average('rental')*12/$project_list_6_month->average('Unit Price ($ psf)')*100, 2) }}
                                        %
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            @php
                                $historical_high_project = $project_list->where('Unit Price ($ psf)', $project_list->max('Unit Price ($ psf)'))->first();
                                $historical_low_project = $project_list->where('Unit Price ($ psf)', $project_list->min('Unit Price ($ psf)'))->first();
                            @endphp
                            <tr>
                                <td>HISTORICAL HIGH</td>
                                <td>
                                    @if($historical_high_project)
                                        S$ {{ $historical_high_project['Unit Price ($ psf)'] }} PSF
                                        IN {{ \App\Service\GlobalService::getNormalDateString($historical_high_project['Sale Date']) }}
                                        FOR A {{ round($historical_high_project['Area (sqm)'] * 10.7639) }}-SQFT UNIT
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>INDICATIVE AVERAGE PRICE FROM HISTORICAL HIGH</td>
                                <td>@if($historical_high_project)
                                        {{ round(($project_list->average('Unit Price ($ psf)')-$historical_high_project['Unit Price ($ psf)'])/$historical_high_project['Unit Price ($ psf)'] * 100,2) }}
                                        %
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>HISTORICAL LOW</td>
                                <td>@if($historical_high_project)
                                        S$ {{ $historical_low_project['Unit Price ($ psf)'] }} PSF
                                        IN {{ \App\Service\GlobalService::getNormalDateString($historical_low_project['Sale Date']) }}
                                        FOR A {{ round($historical_low_project['Area (sqm)'] * 10.7639) }}-SQFT UNIT
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>BUYER PROFILE BY STATUS#</td>
                                <td>SINGAPOREAN {{ $profile_data['Singaporean'] }}%, PR {{ $profile_data['Pr'] }}%,
                                    FOREIGNER {{ $profile_data['Foreigner (NPR)'] }}%,
                                    COMPANY {{ $profile_data['Company'] }}%
                                </td>
                            </tr>
                            @php

                                $buyer_profile_by_purchaser_address = $project_list->groupBy('Purchaser Address Indicator');
                            @endphp
                            <tr>
                                <td>BUYER PROFILE BY PURCHASER ADDRESS#</td>

                                @php
                                    if (isset($buyer_profile_by_purchaser_address['HDB']))
                                        $percent_hdb_profile =  round(count($buyer_profile_by_purchaser_address['HDB'])/count($project_list)*100, 2);
                                    else $percent_hdb_profile = null;
                                    if (isset($buyer_profile_by_purchaser_address['Private']))
                                        $percent_private_profile =  round(count($buyer_profile_by_purchaser_address['Private'])/count($project_list)*100, 2);
                                    else $percent_private_profile = null;
                                @endphp
                                <td>{{ $percent_hdb_profile }}
                                    % -
                                    HDB {{ $percent_private_profile }}
                                    % - Private
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <h6>Note: *Based on contracts in the last 6 months. #Based on all available caveats, it does not
                            represent the breakdown of current owners.</h6>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                    <div class="content-box">
                        <h4 class="text-center">Location</h4>
                        <div id="maps" class="google-maps">
                        </div>
                    </div>
                </div>

                <!-- 3 Column Services End-->
                <div class="clearfix"></div>
            </div>
        </div>
    </div>
    <!-- End Project Information -->


    @if(\App\Service\GlobalService::checkUserPermission())
    <!-- Search by unit -->
    <div class="content">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group form-inline">
                        <label>Unit Search</label>
                        <input class="form-control input-sm" id="units_for_address" placeholder="All Units">
                        <button class="btn btn-primary btn-sm" style="margin-left: -4px" id="btn_search_unit"><i
                                class="fa fa-search"></i></button>
                    </div>
                    <i>Usage: '20-07', '20-' for storey 20, '-07' for stack 07</i>
                </div>
            </div>
        </div>
    </div>
    <div class="divider"></div>
    <!-- End Search by unit -->

    {{-- Buyer Profile--}}
    @if(!isset($config_data) || (isset($config_data) && isset($config_data['buyer_profile'])))
        <div class="services-big">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <h3 class="title">Bulk purchase and buyer profile&nbsp;</h3>
                    </div>
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <div id="buyer_profile_chart_div"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="divider"></div>
    @endif
    {{-- End buyer profile--}}

    {{-- HISTORICAL TRANSACTION --}}
    <div class="slogan bottom-pad-small p-t-50 p-b-30">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <h3 class="title"> Transactions </h3>
                </div>

                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <h4 class="text-center">HISTORICAL TRANSACTION PRICES</h4>
                    <div id="historical_transaction_chart_scatter" style="height: 500px" class="google-maps">

                    </div>
                </div>

                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <h4 class="text-center">HISTORICAL MONTHLY PRICE RANGE</h4>
                    <div id="historical_monthly_chart_range" style="height: 500px" class="google-maps">

                    </div>
                </div>
                <div class="clearfix"></div>
            </div>
        </div>
    </div>



    {{-- Calculate Profit --}}
    @if(!isset($config_data) || ((isset($config_data) && isset($config_data['profitable_transactions'])) || (isset($config_data) && isset($config_data['unprofitable_transactions']))))
        @php
            $profit_result = \App\Service\LandedService::getProfit($project_list);
            $profit_list = $profit_result['profit_list'];
            $unprofit_list = $profit_result['unprofit_list'];
        @endphp
        <div class="slogan bottom-pad-small p-t-50 p-b-30">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <h3 class="title">Profitable and unprofitable transactions&nbsp;</h3>
                    </div>

                    @if(!isset($config_data) || (isset($config_data) && isset($config_data['profitable_transactions'])))
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <h4 class="text-center">PROFITABLE TRANSACTIONS (TOTAL OF {{ count($profit_list) }}
                                TRANSACTIONS)</h4>
                            <div class="content-box">
                                @if(count($profit_list) > 0)
                                    <table
                                        class="table-striped table-bordered table minimalist profitable-datatable-component"
                                        width="100%"
                                        id="atidph-table">
                                        <thead>
                                        <tr>
                                            <th class="headerSortUp">Sold on</th>
                                            <th class="">Address</th>
                                            <th class="">Unit area<br>(sqft)</th>
                                            <th class="">Sale price<br>(S$ psf)</th>
                                            <th class="">Bought on</th>
                                            <th class="">Purchase price<br>(S$ psf)</th>
                                            <th class="">Profit<br>(S$)</th>
                                            <th class="">Holding period<br>(days)</th>
                                            <th class="">Annualised<br>(%)</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($profit_list as $item)
                                            <tr>
                                                <td>{{ $item['sold_on'] }}</td>
                                                <td>{{ $item['Address_filtered'] }}</td>
                                                <td>{{ $item['unit_area'] }}</td>
                                                <td>{{ $item['sale_price_psf'] }}</td>
                                                <td>{{ $item['bought_on'] }}</td>
                                                <td>{{ $item['purchase_price_psf'] }}</td>
                                                <td>{{ $item['profit'] }}</td>
                                                <td>{{ $item['holding_period'] }}</td>
                                                <td>{{ $item['annualized'] }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <p class="text-center">There is no data for this option.</p>
                                @endif
                            </div>
                        </div>
                        <div class="clearfix"></div>
                    @endif

                    @if(!isset($config_data) || (isset($config_data) && isset($config_data['unprofitable_transactions'])))
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <h4 class="text-center">UNPROFITABLE TRANSACTIONS
                                (TOTAL OF {{ count($unprofit_list) }} TRANSACTION)</h4>
                            <div class="content-box">
                                {{--<h3 class="text-center">Location</h3>--}}
                                @if(count($unprofit_list) > 0)
                                    <table
                                        class="table-striped table-bordered table minimalist profitable-datatable-component"
                                        width="100%"
                                        id="atidph-table">
                                        <thead>
                                        <tr>
                                            <th class="headerSortUp">Sold<br>on</th>
                                            <th class="">Address</th>
                                            <th class="">Unit area<br>(sqft)</th>
                                            <th class="">Sale price<br>(S$ psf)</th>
                                            <th class="">Bought<br>on</th>
                                            <th class="">Purchase price<br>(S$ psf)</th>
                                            <th class="">Profit<br>(S$)</th>
                                            <th class="">Holding period<br>(days)</th>
                                            <th class="">Annualised<br>(%)</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($unprofit_list as $item)
                                            <tr>
                                                <td>{{ $item['sold_on'] }}</td>
                                                <td>{{ $item['Address_filtered'] }}</td>
                                                <td>{{ $item['unit_area'] }}</td>
                                                <td>{{ $item['sale_price_psf'] }}</td>
                                                <td>{{ $item['bought_on'] }}</td>
                                                <td>{{ $item['purchase_price_psf'] }}</td>
                                                <td>{{ $item['profit'] }}</td>
                                                <td>{{ $item['holding_period'] }}</td>
                                                <td>{{ $item['annualized'] }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <p class="text-center">There is no data for this option.</p>
                                @endif
                            </div>
                        </div>
                        <div class="clearfix"></div>
                    @endif

                </div>
            </div>
        </div>
        <div class="divider"></div>
    @endif
    {{-- End Calculate Profit --}}

    {{-- Rental --}}
    @php
        $historical_rental = \App\Service\LandedService::getHistoricalRental($project['Address']);
    @endphp
    @if(!isset($config_data) || ((isset($config_data) && isset($config_data['profitable_transactions'])) || (isset($config_data) && isset($config_data['reportctrlj'])) || (isset($config_data) && isset($config_data['street_rental']))))
        <div class="slogan bottom-pad-small p-t-50 p-b-30">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <h3 class="title">Rental &nbsp;{{ $timeframe }}</h3>
                    </div>
                    @if(!isset($config_data) || (isset($config_data) && isset($config_data['rental_contracts'])))
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <h4 class="text-center">RENTAL CONTRACTS</h4>
                            <div class="content-box">
                                {{--<h3 class="text-center">Location</h3>--}}
                                <table
                                    class="table-striped table-bordered table minimalist rental-container-datatable-component"
                                    width="100%">
                                    <thead>
                                    <tr>
                                        <th class="headerSortUp">Lease Start</th>
                                        <th class="">Street</th>
                                        <th class="">Type</th>
                                        <th class="">Unit size (sqft)</th>
                                        <th class="">Number of bedrooms</th>
                                        <th class="">Monthly rent (S$)</th>
                                        <th class="">Monthly rent <br>(Est. S$ psf)</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($residental_rental as $item)
                                        <tr>
                                            <td>{{ $item['Lease Commencement Date'] }}</td>
                                            <td>{{ $item['Street Name'] }}</td>
                                            <td>{{ $item['Type'] }}</td>
                                            <td>{{ $item['Floor Area (sq ft)'] }}</td>
                                            <td>{{ $item['No. of Bedroom(for Non-Landed Only)'] }}</td>
                                            <td>{{ number_format($item['Monthly Gross Rent($)']) }}</td>
                                            @php
                                                if (strpos($item['Floor Area (sq ft)'], 'to')) {
                                                    $monthly_rent_est = $item['Monthly Gross Rent($)']/(((int)trim(explode('to', $item['Floor Area (sq ft)'])[0]) + (int)trim(explode('to', $item['Floor Area (sq ft)'])[1]))/2);
                                                } else {
                                                    $monthly_rent_est = null;
                                                }
                                            @endphp
                                            <td>{{ round($monthly_rent_est, 1) }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if(!isset($config_data) || (isset($config_data) && isset($config_data['reportctrlj'])))
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <h4 class="text-center">Average rental yield analysis</h4>
                            <div class="content-box">
                                {{--<h3 class="text-center">Location</h3>--}}
                                @if(count($average_rental) > 0)
                                    <table
                                        class="table-striped table-bordered table minimalist rental-yield-datatable-component "
                                        width="100%">
                                        <thead>
                                        <tr>
                                            <td style="display: none"></td>
                                            <th>Unit size (sqft)</th>
                                            <th>Average monthly rent* (S$)</th>
                                            <th>No. of rental contracts*</th>
                                            <th>Average price* (S$)</th>
                                            <th>No. of transactions*</th>
                                            <th>Rental yield* (%)</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($average_rental as $item)
                                            <tr>
                                                <td style="display: none">{{ explode('to', $item[0]['Floor Area (sq ft)'])[0] }}</td>
                                                <td>{{ $item[0]['Floor Area (sq ft)'] }}</td>
                                                <td>{{ number_format(round($item->average('Monthly Gross Rent($)'))) }}</td>
                                                <td>{{ $item->sum('Count') }}</td>
                                                @php
                                                    $average_price = \App\Service\ResidentialService::getAveragePrice($project_list, $item[0]['Floor Area (sq ft)']);
                                                    $number_of_transaction = \App\Service\ResidentialService::getTransactionPerUnit($project_list, $item[0]['Floor Area (sq ft)']);
                                                    if ($average_price) {
                                                        $rental_yield = round($item->average('Monthly Gross Rent($)')* 12 / $average_price * 100, 2);
                                                    } else {
                                                        $rental_yield  = null;
                                                    }
                                                @endphp
                                                <td>{{ number_format(round($average_price)) }}</td>
                                                <td>{{ $number_of_transaction }}</td>
                                                <td>{{ $rental_yield }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if(!isset($config_data) || (isset($config_data) && isset($config_data['street_rental'])))
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <h4 class="text-center">Historical rental
                            along {{ \App\Service\GlobalService::getStreetFromAddress($project['Address'])}}</h4>
                        <div class="content-box">
                            {{--<h3 class="text-center">Location</h3>--}}

                            @if(count($historical_rental) > 0)
                                <table
                                    class="table-striped table-bordered table minimalist datatable-component datatable-component"
                                    width="100%">
                                    <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Street</th>
                                        <th>Type</th>
                                        <th>Lowest Rental (S$ psf pm)</th>
                                        <th>Rental 25th (S$ psf pm)</th>
                                        <th>Median Rental (S$ psf pm)</th>
                                        <th>Rental 75th (S$ psf pm)</th>
                                        <th>Highest Rental (S$ psf pm)</th>
                                        <th>Contracts</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($historical_rental as $item)
                                        <tr>
                                            <td>{{ $item['Month'] }}</td>
                                            <td>{{ $item['Street Name'] }}</td>
                                            <td>{{ $item['Type'] }}</td>
                                            <td>{{ $item['Minimum'] }}</td>
                                            <td>{{ $item['25th Percentile'] }}</td>
                                            <td>{{ $item['Median'] }}</td>
                                            <td>{{ $item['75th Percentile'] }}</td>
                                            <td>{{ $item['Maximum'] }}</td>
                                            <td>1</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
    {{-- End Rental --}}

    {{-- Near By Properties--}}
    @if(!isset($config_data) || ((isset($config_data) && isset($config_data['nearby_prices_chart'])) || (isset($config_data) && isset($config_data['nearby_rental_chart'])) || (isset($config_data) && isset($config_data['nearby_comparison']))))
        <div class="divider"></div>
        <div class="slogan p-t-50 p-b-30">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <h3 class="title"> NEARBY PROPERTIES &nbsp;</h3>
                    </div>

                    @if(!isset($config_data) || (isset($config_data) && isset($config_data['nearby_prices_chart'])))
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                            <div class="content-box">
                                <h4 class="text-center">PRICE COMPARISON (UP TO 50)</h4>
                                <div id="nearby_price_compare_chart" class="google-maps">
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(!isset($config_data) || (isset($config_data) && isset($config_data['nearby_rental_chart'])))
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                            <div class="content-box">
                                <h4 class="text-center">RENTAL COMPARISON (UP TO 50)</h4>
                                <div id="nearby_rental_compare_chart" class="google-maps">
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(!isset($config_data) || (isset($config_data) && isset($config_data['nearby_comparison'])))
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <div class="content-box">
                                <h4 class="text-center">PRICE AND RENTAL COMPARISONS (UP TO 50)</h4>
                                <table class="table-striped table-bordered table minimalist datatable-component"
                                       width="100%">
                                    <thead>
                                    <tr>
                                        <th>Marker</th>
                                        <th>Project</th>
                                        <th>Tenure</th>
                                        <th>Completion</th>
                                        <th>Distance <br>(m)</th>
                                        <th>Lowest price* <br>(S$ psf)</th>
                                        <th>Average price* <br>(S$ psf)</th>
                                        <th>Highest price* <br>(S$ psf)</th>
                                        <th>Lowest rental* <br>(S$ psf pm)</th>
                                        <th>Average rental* <br>(S$ psf pm)</th>
                                        <th>Highest rental* <br>(S$ psf pm)</th>
                                        <th>Rental yield <br>(%)</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td><img src="{{ asset('img/marker/marker0.png') }}"></td>
                                        <td>{{ $project['Project Name'] }}</td>
                                        <td>{{ $project['Tenure'] }}</td>
                                        <td>{{ $project['Completion Date'] }}</td>
                                        <td>-</td>
                                        <td>{{ round($residental_rental->min('Monthly Gross Rent($)'), 2) }}</td>
                                        <td>{{ round($residental_rental->average('Monthly Gross Rent($)'), 2) }}</td>
                                        <td>{{ round($residental_rental->max('Monthly Gross Rent($)'), 2) }}</td>
                                        <td>{{ round($residental_rental->min('rental'), 2) }}</td>
                                        <td>{{ round($residental_rental->average('rental'), 2) }}</td>
                                        <td>{{ round($residental_rental->max('rental'), 2) }}</td>
                                        @php
                                            if ($residental_rental->average('Monthly Gross Rent($)')) {
                                                $rental_yield = round($residental_rental->average('rental')* 12 / $residental_rental->average('Monthly Gross Rent($)') * 100, 2);
                                            } else {
                                                $rental_yield = null;
                                            }

                                            $marker_index = 0;
                                        @endphp
                                        <td>{{ $rental_yield }}</td>
                                    </tr>
                                    @foreach($nearby_items as $item)
                                        @if($item['Project Name'] != $project['Project Name'])
                                            @php
                                                $nearby_projects_list = \App\Service\LandedService::getRentalData($item['Project Name']);
                                                $nearby_projects_list = $nearby_projects_list->map(function($s_item) {
                                                    if ($s_item['Floor Area ll']) {
                                                     $s_item['rental'] = $s_item['Monthly Gross Rent($)']/$s_item['Floor Area ll'];
                                                    } else {
                                                     $s_item['rental'] = null;
                                                    }

                                                     return $s_item;
                                                });

                                            if ($nearby_projects_list->average('Monthly Gross Rent($)')) {
                                                $nearby_projects_list_rental_yield = round($nearby_projects_list->average('rental')* 12 / $nearby_projects_list->average('Monthly Gross Rent($)') * 100, 2);
                                            } else {
                                                $nearby_projects_list_rental_yield = null;
                                            }
                                            $marker_index ++;
                                            $item['marker'] = "img/marker/marker" . $marker_index . ".png";
                                            @endphp
                                            <tr>
                                                <td><img
                                                        src="{{ asset('img/marker/marker' . $marker_index . '.png') }}">
                                                </td>
                                                <td>{{ $item['Project Name'] }}</td>
                                                <td>{{ $item['Tenure'] }}</td>
                                                <td>{{ $item['Completion Date'] }}</td>
                                                <td>{{ $item['distance'] }}</td>
                                                <td>{{ round($nearby_projects_list->min('Monthly Gross Rent($)'), 2) }}</td>
                                                <td>{{ round($nearby_projects_list->average('Monthly Gross Rent($)'), 2) }}</td>
                                                <td>{{ round($nearby_projects_list->max('Monthly Gross Rent($)'), 2) }}</td>
                                                <td>{{ round($nearby_projects_list->min('rental'), 2) }}</td>
                                                <td>{{ round($nearby_projects_list->average('rental'), 2) }}</td>
                                                <td>{{ round($nearby_projects_list->max('rental'), 2) }}</td>
                                                <td>{{ $nearby_projects_list_rental_yield }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <div class="google-maps" id="nearby_map"></div>
                        </div>
                @endif
                <!-- 3 Column Services End-->
                    <div class="clearfix"></div>
                </div>
            </div>
        </div>
    @endif
    {{-- End Near by Properties--}}
    @endif

@endsection

@section('scripts')
    @include('TrendsAnalysis.footer_report_landed')
@endsection
