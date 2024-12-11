<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\OptionQuestionResult;
use App\Models\Question;
use App\Models\SchoolGrade;
use App\Models\Student;
use App\Models\StudentTerm;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TermController extends Controller
{

    public function termStart(Request $request, $id)
    {
        $student = Student::query()->with(['level'])->find(Auth::guard('student')->user()->id);


        if (!$student->demo) {
            //get term and check if available for student
            $term = Term::query()
                ->where('id', $id)
                ->where('level_id', $student->level_id)
                ->first();
            if (!$term) {
                return redirect()->route('student.home')->with('term-message', t('Assessment Not Found'));
            }


            //check if term round is available in studentSchool
            $round_is_available = SchoolGrade::with('school')
                ->where('school_id', $student->school_id)
                ->where($term->round, true)
                ->where('arab', $student->level->arab)
                ->whereHas('school', function ($query) use ($student) {
                    $query->where('available_year_id', $student->level->year_id);
                })->first();

            if (!$round_is_available) {
                return redirect()->route('student.home')->with('term-message', t('Assessment Not Available For You'));
            }

            //check if the term is passed
            $is_passed = StudentTerm::query()
                ->where('student_id', $student->id)
                ->where('term_id', $term->id)
//            ->whereNotNull('end_at')
                ->first();

            if ($is_passed) {
                return redirect()->route('student.home')->with('term-message', t('Assessment Passed Previously'));
            }
        } else {
            $term = Term::query()
                ->where('id', $id)
                ->whereHas('level', function ($query) use ($student) {
                    $query->whereIn('id', $student->demo_data->levels);
                })
                ->whereIn('round', $student->demo_data->rounds)
                ->first();

            if (!$term) {
                return redirect()->route('student.home')->with('term-message', t('Assessment Not Found'));
            }

        }

        $questions = Question::with(['option_question'])->where('term_id', $id)->get();
        $questions_count = count($questions);
        $marks = '100';

        return view('student.term.index', compact('student', 'term', 'questions', 'questions_count', 'marks'));
    }

    public function termLeave()
    {
        StudentTerm::query()->where('id', \session()->get('student_term_id'))->forceDelete();
        \session()->forget('student_term_id');
        return redirect()->route('student.home');
    }


    public function termSave(Request $request, $id)
    {
        $request->validate(['questions' => 'required|array']);

        $student = Auth::guard('student')->user();

        if (!$student->demo) {

            DB::transaction(function () use ($request, $id, $student) {

                $student_term = StudentTerm::query()->create([
                    'student_id' => $student->id,
                    'term_id' => $id,
                    "dates_at" => [
                        'started_at' => $request->get('started_at', \Carbon\Carbon::now()->format('Y-m-d H:i:s')),
                        'submitted_at' => \Carbon\Carbon::now('Asia/Dubai')->format('Y-m-d H:i:s'),
                        'corrected_at' => null,
                        'corrected_by' => null,
                    ]
                ]);

                foreach ($request['questions'] as $key => $question) {
                    switch ($question['type']) {
                        case 'multiple_choice':
                            if (isset($question['answer_option_id'])) {
                                $this->saveOptionResult($student->id, $student_term->id, $key, $question['answer_option_id']);
                            }
                            break;

                    }
                }
            });
        }
        return redirect()->route('student.home')->with('term-message', t('Assessment passed successfully'));
    }



    private function saveOptionResult($student_id, $student_term_id, $question_id, $option_id)
    {
        OptionQuestionResult::query()->create([
            'student_id' => $student_id,
            'student_term_id' => $student_term_id,
            'question_id' => $question_id,
            'option_id' => $option_id,
        ]);
        return true;
    }





}
