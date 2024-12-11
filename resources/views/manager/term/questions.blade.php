@extends('manager.layout.container')

@section('title')
    {{$title}} / {{$term->name}}

@endsection
@push('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{route('manager.term.index')}}" class="text-muted">
            {{t('Assessments')}}
        </a>
    </li>
    <li class="breadcrumb-item text-muted">
        {{$term->name}}
    </li>
@endpush
@section('content')

    <form id="questions_form" class="questions-form" method="POST"
              action="{{route('manager.term.update-questions',['id'=>request()['id']])}}"
              enctype="multipart/form-data">
            @csrf
            @if(isset($questions) && $questions->count()>0)
                @foreach($questions as $question)
                    @include('manager.term.questions_types.'.$question->type)
                @endforeach
                    <button type="submit" class="btn btn-primary">{{t('Save')}}</button>
            @else
                <h4 class="text-center">{{t('The assessment not have any question')}}</h4>
            @endif

        </form>


@endsection
@section('script')

    <script>
        let IMAGE = '{{t('Image')}}';
        let OPTION = '{{t('Option')}}';
        let ANSWER = '{{t('Answer')}}';
        let DELETE = '{{t('Delete')}}';
        let OK = '{{t('OK')}}';
        let DELETE_MESSAGE_TITLE = '{{t('Delete')}}';
        let DELETE_MESSAGE_BODY = '{{t('Do you want to delete this option?')}}';

        {{--let SUBMIT_FORM_URL = "{{route('manager.term.delete-question-option')}}"--}}
        let DELETE_OPTION_URL = "{{route('manager.term.delete-question-option')}}"
        let DELETE_QUESTION_FILE_URL = "{{route('manager.term.delete-question-file')}}"
        let DELETE_OPTION_IMAGE_URL = "{{route('manager.term.delete-option-image')}}"

    </script>
    <script src="{{asset('assets_v1/js/manager/questions_management/questions.js')}}"></script>
    <script src="{{asset('assets_v1/js/jquery-validation/dist/jquery.validate.js')}}"></script>
    <script src="{{asset('assets_v1/js/jquery-validation/dist/additional-methods.js')}}"></script>
    @if(app()->getLocale()=='ar')
        <script src="{{asset('assets_v1/js/jquery-validation/dist/localization/messages_ar.js')}}"></script>
    @endif

@endsection
