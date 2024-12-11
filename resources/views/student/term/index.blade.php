@extends('student.layout.container')
@section('title')
    {{$term->name}}
@endsection


@section('navbar')
    <nav class="navbard-top">
        <div class="container">
            <div class="navbar-container">
                <a href="#!" class="back-card">
                    <span class="text ms-2">{{$term->name}}</span>
                </a>

                <a href="#!" class="leave-exam">
                    <img src="{{asset('web_assets/img/leave.svg')}}" alt="">
                    <span class="text ms-2">{{$term->level->arab?'مغادرة الإختبار':'Leave Assessment'}}</span>
                </a>
            </div>
        </div>
    </nav>
@endsection

@section('style')
    <link href="{{asset('web_assets/css/custom2.css')}}?v{{time()}}" rel="stylesheet">
    <link href="{{asset('web_assets/css/exam_questions.css')}}?v{{time()}}" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{asset('web_assets/css/arabic-keyboard.css')}}?v={{time()}}">
        <style>
            #keyboardInputLayout {
                direction: ltr !important;
            }

            #keyboardInputMaster tbody tr td div#keyboardInputLayout table tbody tr td {
                font: normal 30px 'Lucida Console', monospace;
            }

            .keyboardInputInitiator {
                width: 50px
            }
        </style>

@endsection

@section('content')
    <section class="exam-view">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="header-card">
                        <div class="info">
                            <div class="exam-details ar">
                                <ul>
                                    <li>  عدد الأسئلة {{$questions_count}}  .</li>
                                    <li> يجب الإجابة على جميع الأسئلة. </li>
                                    <li> .مجموعة الدرجات {{$marks}} درجة</li>
                                </ul>
                            </div>
                            <div class="exam-timer">
                                <div class="countdown mb-3">
                                    <div class="icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M20.75 13.25C20.75 14.9806 20.2368 16.6723 19.2754 18.1112C18.3139 19.5502 16.9473 20.6717 15.3485 21.3339C13.7496 21.9962 11.9903 22.1695 10.293 21.8319C8.59563 21.4943 7.03653 20.6609 5.81282 19.4372C4.58911 18.2135 3.75575 16.6544 3.41813 14.957C3.08051 13.2597 3.25379 11.5004 3.91606 9.90152C4.57832 8.30267 5.69983 6.9361 7.13876 5.97464C8.57769 5.01318 10.2694 4.5 12 4.5C14.3204 4.5008 16.5455 5.42292 18.1863 7.06369C19.8271 8.70446 20.7492 10.9296 20.75 13.25V13.25Z" stroke="#0076A4" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                            <path d="M12 8V13" stroke="#0076A4" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                            <path d="M9 2H15" stroke="#0076A4" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                        </svg>
                                    </div>
                                    <div id="clock">00:59:49</div>
                                    <input type="hidden" id="timer-ago" value="00:59:49">
                                </div>
                            </div>
                            <div class="exam-details en">
                                <ul>
                                    <li> No. of questions is {{$questions_count}}. </li>
                                    <li> All questions must be answered . </li>
                                    <li> The total marks is {{$marks}}. </li>
                                </ul>
                            </div>

                        </div>

                        <div class="progress-card">
                            <ul class="nav nav-tabs nav-progress" role="tablist">

                                @foreach($questions as $question)
                                    <li class="nav-item" role="presentation">
                                        <a href="#question-{{$question->id}}" class="btn-nav nav-link {{$loop->index==0?'active':''}}"
                                           id="btn-nav-{{$loop->iteration}}" data-index="{{$loop->iteration}}" data-bs-toggle="tab" aria-selected="true" role="tab">
                                            <div class="question-number"> {{$loop->index+1}} @if($loop->index<9) 0 @endif</div>
                                            <div class="question-dot"> </div>
                                        </a>
                                    </li>
                                @endforeach

                            </ul>
                        </div>

                    </div>
                </div>

            </div>



            <form id="exams" action="{{route('student.term-save', ['id'=>$term->id])}}"  method="post" enctype="multipart/form-data">
                <input type="hidden" name="started_at" value="{{now()}}">
                @csrf
                <div class="tab-content" id="myTabContent">
                    @foreach($questions as $question)
                        @php
                            $index = $loop->index
                        @endphp

                        <input type="hidden" value="{{$question->type}}" name="questions[{{$question->id}}][type]">

                        @if($question->type == 'multiple_choice')
                            @include('student.term.questions.choose', compact('question','index'))
                        @endif
                    @endforeach

                        @include('student.term.parts.action_buttons')

                </div>
            </form>


        </div>
    </section>

<!--exam confirm modal-->
@include('student.term.parts.submit-term-modal')
@include('student.term.parts.leave-term-modal')



@endsection

@section('script')
    <script>
        @if($term->level->arab)
        @php
            app()->setLocale('ar')
        @endphp
        $('html').attr('lang', 'ar').attr('dir', 'rtl');
        @else
        @php
            app()->setLocale('en')
        @endphp
        $('html').attr('lang', 'en').attr('dir', 'ltr');
        @endif
    </script>
    <script src="{{asset('web_assets/js/student_term.js')}}?v={{time()}}"></script>

    <script>
        let TIME = "{{$term->duration}}";
        getAndSetResults() //cache results
    </script>

@endsection
