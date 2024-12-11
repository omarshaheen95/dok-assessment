<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

class Year extends Model
{
    use SoftDeletes, LogsActivity;
    protected static $logAttributes = ['name', 'slug','default'];
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    protected $fillable = [
        'name', 'slug','default'
    ];

    public function scopeSearch(Builder $query, Request $request)
    {
        return $query
            ->when($name = $request->get('name', false), function (Builder $query) use ($name) {
                $query->where('name', 'like', '%' . $name . '%');
            })->when($value = $request->get('row_id',[]),function (Builder $query) use ($value){
                $query->whereIn('id', $value);
            });
    }

    public function getActionButtonsAttribute()
    {
        $actions =  [
            ['key'=>'edit','name'=>t('Edit'),'route'=>route('manager.year.edit', $this->id),'permission'=>'edit years'],
            ['key'=>'delete','name'=>t('Delete'),'route'=>$this->id,'permission'=>'delete years'],
        ];
        return view('general.action_menu')->with('actions',$actions);

    }
}
