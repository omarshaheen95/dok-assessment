<div class="action-content">
    <a class="btn btn-prev d-none" id="btn-prev" data-tab-index="0">{{$term->level->arab?'السابق':'Previous'}}</a>

    <a class="btn btn-next" id="btn-next" data-tab-index="1">{{$term->level->arab?'التالي':'Next'}}</a>

    <div class="btn btn-submit d-none" id="btn-submit">
        <span class="spinner-border spinner-border-sm me-2 d-none"></span>
        {{$term->level->arab?'إنهاء':'Finish'}}
    </div>

</div>

