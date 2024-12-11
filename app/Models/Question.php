<?php

namespace App\Models;

use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Spatie\Activitylog\Traits\LogsActivity;

class Question extends Model
{
    use SoftDeletes,CascadeSoftDeletes, LogsActivity;
    protected static $logAttributes = ['term_id','type','content','image','audio','mark','question_reader', 'question_file_id'];
    protected static $recordEvents = ['updated', 'deleted'];
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;


    protected $fillable = ['term_id','type','content','image','subject_id','audio','mark','question_reader','question_file_id'];
    protected $cascadeDeletes = ['option_question','option_question_result', 'question_standard'];

    /**
     * Questions types [id]
     * 1=> Choose Correct Answer
     */
    public static function getQuestionTypes(){
        return[
            ['name'=>t('Choose the correct answer'),'value'=>'multiple_choice'],
        ];
    }




    public function question_standard():HasOne{
        return $this->hasOne(QuestionStandard::class,'question_id');
    }
    public function option_question():HasMany{
        return $this->hasMany(OptionQuestion::class,'question_id');
    }


    public function option_question_result():HasMany{
        return $this->hasMany(OptionQuestionResult::class,'question_id');
    }

    public function term():BelongsTo
    {
        return $this->belongsTo(Term::class);
    }


    public function scopeSearch(Builder $query,Request $request): Builder
    {
        return $query->when($value = $request->get('content'),function (Builder $query) use ($value){
            $query->where('content','LIKE','%'.$value.'%');
        })->when($value = $request->get('term_name'),function (Builder $query) use ($value){
            $query->whereHas('term',function (Builder $query) use ($value){
                $query->where('name','LIKE','%'.$value.'%');
            });
        })->when($value = $request->get('year_id'),function (Builder $query) use ($value){
            $query->whereHas('term.level',function (Builder $query) use ($value){
                $query->where('year_id',$value);
            });
        })->when($value = $request->get('level_id'),function (Builder $query) use ($value){
            $query->whereHas('term',function (Builder $query) use ($value){
                $query->where('level_id',$value);
            });
        })->when($value = $request->get('term_id'),function (Builder $query) use ($value){
            $query->where('term_id',$value);
        })->when($value = $request->get('type'),function (Builder $query) use ($value){
            $query->where('type',$value);
        })->when($value = $request->get('row_id',[]),function (Builder $query) use ($value){
            $query->whereIn('id', $value);
        });
    }


    public function getImageAttribute($value){
        if ($value){
            return asset($value);
        }
        return $value;
    }

    public function getAudioAttribute($value){
        if ($value){
        return asset($value);
        }
        return $value;
    }

    public function getQuestionReaderAttribute($value){
        if ($value){
            return asset($value);
        }
        return $value;
    }
}
