
//cache result in session
function saveResult(id) {
    // let value = $('#'+id).val()
    // sessionStorage.setItem(id,value)
    sessionStorage.clear()
    $('input[type="radio"]:checked').each(function(index,item){
        sessionStorage.setItem(item.id,'1')
    });
    $("input[type='text'], textarea").each(function(index,item){
        if($(this).val()){
            sessionStorage.setItem(item.id,$(this).val())
        }
    });
}

function getAndSetResults() {
    $('input[type="radio"]').each(function(index,item){
        let result = sessionStorage.getItem(item.id)
        if (result){
            $(this).attr('checked', 1);
        }
    });
    $("input[type='text'], textarea").each(function(index,item){
        let result = sessionStorage.getItem(item.id)
        if (result){
            $(this).val(result)
        }
    });
}


//Validation
function validation(){
    let errors = 0
    $('.question-card').each(function () {
        let questionId = $(this).attr('data-id');
        let questionType = $('input[name="questions['+questionId+'][type]"]').val()
        // console.log(questionId+'***'+questionType)

        switch (questionType) {
            case 'multiple_choice':{
                let hasAnswer ;
                if ($(this).find('.answer-group input[type="radio"]').length > 0){
                    hasAnswer = $(this).find('.answer-group input[type="radio"]:checked').length > 0
                }

                if (!hasAnswer){
                    $(this).find(".answer-group").addClass("border border-danger");
                    errors++;
                }else {
                    $(this).find(".answer-group").removeClass("border border-danger");
                }

                break;
            }
        }
    })
    return errors <= 0;
}

//-----------------------------------------------------------------------------
function examFormSubmit(){
    if (validation()){
        $("#submit-term").modal("hide");
        $("#exam-form").addClass("d-none");
        $("#save-form").removeClass("d-none");
        $("#exams").submit();
    }else {
        $("#submit-term").modal("hide");
        showToastify("You must answered for all questions", "error");

    }
}


$(document).ready(function () {

    /*---------------------------------------------------
        timer
    ---------------------------------------------------*/
    let clock = $('#clock');
    if (clock.length > 0 && typeof TIME !== 'undefined' && TIME) {
        // `TIME` is now in minutes
        var qnt = TIME,
            val = qnt * 60 * 1000, // Convert minutes to milliseconds
            selectedDate = new Date().valueOf() + val;

        clock.countdown(selectedDate.toString())
            .on('update.countdown', function (event) {
                var format = '%H:%M:%S'; // Display format
                $(this).html(event.strftime(format)); // Update the clock display
                $("#timer-ago").val(event.strftime(format)); // Update a hidden input field
                //localStorage.setItem("timer_val", event.offset.totalSeconds); // Optional, if needed
            })
            .on('finish.countdown', function (event) {
                $(this).parent().addClass('disabled').html('This Time has expired!');
                showToastify("The Time has expired!", "error"); // Show a notification
            });
    }



    /*---------------------------------------------------
        navigation
    ---------------------------------------------------*/
    $('.btn-nav').click(function () {
        let index = $(this).data('index')
        if (index === $('.btn-nav').length) {
            btnSubmit.removeClass('d-none')
            btnNext.addClass('d-none')
            btnPrev.removeClass('d-none')
            btnPrev.data('tab-index', index-1 )

        }else {
            if (index === 1) {
                btnPrev.addClass('d-none')
                btnSubmit.addClass('d-none')
                btnNext.removeClass('d-none')
            }else {
                btnPrev.removeClass('d-none')
            }
            btnNext.data('tab-index', index )
            btnPrev.data('tab-index', index-1 )
        }
    })
    var btnNext = $('#btn-next'),
        btnPrev = $('#btn-prev'),
        btnSubmit = $('#btn-submit'),
        btnLeave = $('#leave-exam'),
        navCount = $('.btn-nav').length;

    btnNext.click(function () {
        let tab_index = $(this).data('tab-index')+1
        if (tab_index === navCount) {
            btnSubmit.removeClass('d-none')
            btnNext.addClass('d-none')
        }
        if (tab_index >= 1) {
            btnPrev.removeClass('d-none')
        }else {
            btnPrev.addClass('d-none')
        }
        $(this).data('tab-index', tab_index )
        btnPrev.data('tab-index', tab_index-1 )
        document.getElementById('btn-nav-' + tab_index ).click()

    });

    btnPrev.click(function () {
        let tab_index = $(this).data('tab-index')
        if (tab_index < navCount) {
            btnNext.removeClass('d-none')
            btnSubmit.addClass('d-none')
        }

        if(tab_index-1 === 0){
            btnPrev.addClass('d-none')
        }
        $(this).data('tab-index', tab_index-1)
        btnNext.data('tab-index', tab_index )

        document.getElementById('btn-nav-'+ tab_index).click()
    });

    btnSubmit.click(function (e) {
        e.preventDefault();
        $("#submit-term").modal("show");
    });

    btnLeave.click(function (e) {
        e.preventDefault();
        $("#leave-term-modal").modal("show");
    });



    /*---------------------------------------------------
        word count
    ---------------------------------------------------*/

    $(document).on("keyup", ".textarea textarea", function () {

        var value = $.trim($(this).val()),
            count = value === '' ? 0 : value.split(' ').length;
        $(this).parent().find(".word-count span").text(count);
    });


});

