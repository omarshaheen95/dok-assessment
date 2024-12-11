@extends('manager.layout.container')
@section('title')
    {{t('Assessments Questions')}}
@endsection

@section('actions')
    <div class="dropdown" id="actions_dropdown">
        <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            {{t('Actions')}}
        </button>

        @can('export terms questions')
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#!" onclick="excelExport('{{route('manager.term.terms-questions-export')}}')">{{t('Export')}}</a></li>
            </ul>
        @endcan

    </div>
@endsection
@section('filter')
    <div class="row">
        <div class="col-lg-3 mb-2">
            <label>{{t('Question Content')}}:</label>
            <input autocomplete="off" type="text"  name="content" class="form-control direct-search" placeholder="{{t('Question Content')}}">
        </div>
        <div class="col-lg-3 mb-2">
            <label>{{t('Assessments Name')}}:</label>
            <input type="text" id="term_name" name="term_name" class="form-control direct-search" placeholder="{{t('Assessments Name')}}">
        </div>


        <div class="col-lg-3 mb-2">
            <label>{{t('Year')}} :</label>
            <select name="year_id" id="year_id" class="form-select" data-control="select2" data-placeholder="{{t('Select Year')}}" data-allow-clear="true">
                <option></option>
                @foreach($years as $year)
                    <option value="{{ $year->id }}">{{ $year->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-lg-3 mb-2">
            <label>{{t('Levels')}} :</label>
            <select name="level_id" id="levels_id" class="form-select" data-control="select2" data-placeholder="{{t('Select Level')}}" data-allow-clear="true">
            </select>
        </div>

        <div class="col-lg-3 mb-2">
            <label>{{t('Assessments')}} :</label>
            <select name="term_id" id="terms" class="form-select" data-control="select2" data-placeholder="{{t('Select Assessment')}}" data-allow-clear="true">
                <option></option>
            </select>
        </div>




    </div>

@endsection

@push('breadcrumb')
    <li class="breadcrumb-item text-muted">{{t('Assessments Questions')}}</li>
@endpush


@section('content')



    <div class="row">

        <table class="table table-row-bordered gy-5" id="datatable">
            <thead>
            <tr class="fw-semibold fs-6 text-gray-800">
                <th class="text-start"></th>
                <th class="text-start">#</th>
                <th class="text-start">{{t('Assessment Name')}}</th>
                <th class="text-start">{{t('Content')}}</th>
                <th class="text-start">{{t('Level')}}</th>
                <th class="text-start">{{t('Mark')}}</th>
                <th class="text-start">{{t('Actions')}}</th>
            </tr>
            </thead>
        </table>
    </div>
@endsection


@section('script')

    <script>
        {{--var DELETE_URL = "{{route('manager.term.delete')}}";--}}
        var TABLE_URL = "{{route('manager.term.terms-questions')}}";

        var TABLE_COLUMNS = [
            {data: 'id'},
            {data: 'id'},
            {data: 'term_name'},
            {data: 'content'},
            {data: 'level'},
            {data: 'mark'},
            {data: 'actions'}
        ];

        $('#levels_id').on('change',function (){
            $.ajax({
                url: "{{route('manager.term.terms-names',['level'=>':level'])}}".replace(':level',$(this).val()),
                type: 'GET',
                dataType: 'json',
                success: function (data) {
                    $('#terms').empty();
                    $('#terms').append(data.html);
                }
            });
        })


    </script>

    <script src="{{asset('assets_v1/js/datatable.js')}}?v={{time()}}"></script>
    <script src="{{asset('assets_v1/js/manager/models/general.js')}}?v1"></script>


@endsection


