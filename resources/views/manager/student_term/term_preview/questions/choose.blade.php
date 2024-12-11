<!-- Choose The Correct Answer -->
<div class="tab-pane fade {{$index==0?'active show':''}}" id="question-{{$question->id}}" role="tabpanel">
    <div class="row">
        <div class="col-lg-12">
            <div class="question-card">

                <div class="answer-content bg-transparent border-0">
                    <div class="answer-card">
                        <div class="title">
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="fw-bold m-0 pb-1 q-number">{{$index+1}}</h6>
                                <p class="m-0 pb-1"> {{$question->content}}</p>
                            </div>

                        </div>

                    </div>

                </div>
                <div class="question-content my-2">
                    @if($question->image)
                        <div class="pic">
                            <img src="{{asset($question->image)}}" class="q-image">
                        </div>
                    @endif

                </div>
                <div class="answer-group d-flex justify-content-center align-items-start py-2">
                    @foreach($question->option_question as $option)
                        <div class="form-check form-check-inline align-items-center">
                            @if($term->level->arab)
                                @if(!$option->image)
                                    <label class="form-check-label ms-2" for="choose-option-{{$option->id}}">
                                        {{$option->content}}
                                    </label>
                                @else
                                    <div class="option-pic ms-2">
                                        <img src="{{asset($option->image)}}"/>
                                    </div>
                                @endif
                            @endif

                            <input class="form-check-input" type="radio"
                                   name="questions[{{$question->id}}][answer_option_id]"
                                   id="choose-option-{{$option->id}}" value="{{$option->id}}"
                                   @if($option->result)checked @endif>

                            @if(!$term->level->arab)
                                @if(!$option->image)
                                    <label class="form-check-label ms-2" for="choose-option-{{$option->id}}">
                                        {{$option->content}}
                                    </label>
                                @else
                                    <div class="option-pic ms-2">
                                        <img src="{{asset($option->image)}}"/>
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endforeach

                </div>
            </div>
        </div>
    </div>
</div>


