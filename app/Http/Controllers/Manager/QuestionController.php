<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\OptionQuestion;
use App\Models\Question;
use App\Models\QuestionStandard;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuestionController extends Controller
{

    public function __construct()
    {

        $this->middleware('permission:show questions content')->only('showQuestions');
        $this->middleware('permission:edit questions content')
            ->only(['updateQuestions','deleteOption','deleteMatchOptionImage','deleteQuestionFile']);
    }


    public function showQuestions($id){
        $title = t('Assessment Questions Content');
        $term = Term::query()->where('id',$id)->first();
        $questions = Question::with(['option_question','question_standard'])->where('term_id', $id)->get();
        return view('manager.term.questions', compact('title','term','questions'));

    }

    /**
     * Crate or update questions with upload files
     * @param Request $request
     * @param $id
     */
    public function updateQuestions(Request $request,$id){
       $request->validate(['question_data'=>'required|array']);

        DB::transaction(function () use ($request,$id){
            foreach ($request['question_data'] as $question){
                //update main question
                $this->updateQuestion($question,$id);

                //update
                switch ($question['type']){
                    case 'multiple_choice':
                        $this->createOrUpdateOptionQuestion($question);
                        break;
                }

            }
        });
        return $this->sendResponse(null,t('Successfully Updated'));
    }


    /**
     * update the main question
     * @param $question
     * @param $term_id
     */
    private function updateQuestion($question,$term_id){

        $question_data = [];
//        dd($question);

        if (isset($question['image'])){
            $this->deleteQF($question['id'],'image');
            $image = $this->uploadFile($question['image']);
            $question_data['image'] = $image;
        }
        if (isset($question['audio'])){
            $this->deleteQF($question['id'],'audio');
            $audio = $this->uploadFile($question['audio']);
            $question_data['audio'] = $audio;
        }
        if (isset($question['question_reader'])){
            $this->deleteQF($question['id'],'question_reader');
            $question_reader = $this->uploadFile($question['question_reader']);
            $question_data['question_reader'] = $question_reader;
        }

        //update or create question standard
        if (isset($question['question_standard']) && $question['question_standard']){
            QuestionStandard::query()->updateOrCreate(
                ['question_id' => $question['id']],
                [
                    'question_id' => $question['id'],
                    'standard' => $question['question_standard'],
                    'mark' => $question['mark']
                ]
            );
        }

        //update the main question
        if (isset($question['content']) && $question['content']){
            $question_data['content'] = strip_tags($question['content']);
            Question::query()
                ->where('id', $question['id'])
                ->where('term_id', $term_id)
                ->update($question_data);
        }else{
            throw ValidationException::withMessages(['question_data['.$question["id"].'][content]' => t('The question content is required')]);
        }



    }
    private function createOrUpdateOptionQuestion($question){

        if (isset($question['options']) && is_array($question['options']) && $question['options']){
          foreach ($question['options'] as $key=>$option){

              if (!isset($question['correct_answer_index'])){
                  throw ValidationException::withMessages([t('The correct_answer_index is required')]);
              }

              $path = null;
              if (isset($option['image'])){
               $path = $this->uploadFile($option['image']);
              }
              if (isset($option['id']) && $option['id']){
                  $data = ['content'=>$option['content'],'result'=>$key==$question['correct_answer_index']?1:0];
                  if ($path){
                      $data['image'] = $path;
                  }
                  OptionQuestion::query()->where('id',$option['id'])->update($data);
              }else{
                  $data = [
                      'question_id'=>$question['id'],
                      'content'=>$option['content'],
                      'result'=>$key==$question['correct_answer_index']?1:0,
                  ];
                  if ($path){
                      $data['image'] = $path;
                  }
                  OptionQuestion::query()->create($data);
              }
          }
        }
    }



    public function deleteOption(Request $request)
    {
        $request->validate(['id' => 'required', 'type' => 'required']);
        $result = false;
        if ($request['type'] == 'multiple_choice') {
            $this->deleteOptionImage($request['id'],2);
            $result = OptionQuestion::query()->where('id', $request['id'])->delete();
        }
        return response()->json([
            'status' => (bool)$result,
            'message' => (bool)$result ? t('Option Deleted Successfully') : t('Option Not Deleted')
        ]);
    }



    public function deleteQuestionFile(Request $request){
        $request->validate(['id'=>'required','file_type'=>'required']);
        return $this->deleteQF($request['id'],$request['file_type']);
    }
    //delete question file [image - audio]
    private function deleteQF($id,$file_type){
        $type = null;
        if ($file_type == 'image') {
            $type = 'image';
        } else if ($file_type == 'audio') {
            $type = 'audio';
        } else if ($file_type == 'question_reader') {
            $type = 'question_reader';
        }

        $question = Question::query()->findOrFail($id);

        if ($question[$type]){
            deleteFile($question[$type]);
            $question->update([$type => null]);
        }

        return response()->json([
            'status' => (bool)$question,
            'message' => (bool)$question ? t('Deleted Successfully') : t('Not Deleted')
        ]);
    }


    //delete image directly from option
    public function deleteOptionImageRequest(Request $request){
        $request->validate(['id'=>'required','type'=>'required']);
        return $this->deleteOptionImage($request['id'],$request['type']);
    }
    private function deleteOptionImage($id,$type){
        $option = null;
        if ($type == 'multiple_choice') {
            $option = OptionQuestion::query()->findOrFail($id);

        }
        if ($option['image']){
            deleteFile($option['image']);
            $option->update(['image' => null]);

        }
        return response()->json([
            'status' => (bool)$option,
            'message' => (bool)$option ? t('Deleted Successfully').$type : t('Not Deleted')
        ]);
    }


    private function uploadFile($file, $path = '/questions')
    {
        $result = uploadNewFile($file,$path);
        return $result['path'];
    }




}
