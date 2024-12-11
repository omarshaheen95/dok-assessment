<?php

namespace App\Http\Controllers\Manager;

use App\Exports\StudentTermExport;
use App\Http\Controllers\Controller;
use App\Models\ArticleQuestionResult;
use App\Models\FillBlankAnswer;
use App\Models\MatchQuestionResult;
use App\Models\OptionQuestionResult;
use App\Models\Question;
use App\Models\QuestionStandard;
use App\Models\School;
use App\Models\SortQuestionResult;
use App\Models\Student;
use App\Models\StudentTerm;
use App\Models\StudentTermStandard;
use App\Models\Subject;
use App\Models\Term;
use App\Models\TFQuestionResult;
use App\Models\Year;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class StudentTermController extends Controller
{


    public function __construct()
    {
        $this->middleware('permission:show students terms')->only('index');
        $this->middleware('permission:edit students terms')->only(['edit', 'updateTerm']);
        $this->middleware('permission:restore deleted students terms')->only('restore');
        $this->middleware('permission:delete students terms')->only('deleteStudentTerm');
        $this->middleware('permission:restore deleted students terms')->only('restore');
        $this->middleware('permission:auto correct students terms')->only('autoCorrect');
    }

    public function index(Request $request, $status)
    {
        if ($status == 'corrected') {
            $request['corrected'] = 1;
            $title = t('Corrected Student Assessments');
        } else if ($status == 'uncorrected') {
            $request['corrected'] = 2;
            $title = t('Uncorrected Student Assessments');

        } else {
            return redirect()->route('school.home');
        }

        if ($request->ajax()) {
            $rows = StudentTerm::with(['student.school', 'term.level.year'])->search($request)->latest();
            return DataTables::make($rows)
                ->escapeColumns([])
                ->addColumn('student_id', function ($row) {
                    return $row->student->id ?? '-';
                })
                ->addColumn('name', function ($row) {
                    return $row->student->name ?? '-';
                })
                ->addColumn('email', function ($row) {
                    return $row->student->email ?? '-';
                })
                ->addColumn('grade_name', function ($row) {
                    return $row->student->grade_name ?? '-';
                })->addColumn('school', function ($row) {
                    return $row->student->school->name ?? '-';
                })
                ->addColumn('year', function ($row) {
                    return $row->term->level->year->name ?? '-';
                })->addColumn('round', function ($row) {
                    return $row->term->round ?? '-';
                })
                ->addColumn('corrected', function ($row) {
                    if ($row->corrected == 0) {
                        return '<a><span class="badge badge-danger">' . t('Uncorrected') . '</span></a>';
                    }
                    return '<a><span class="badge badge-success">' . t('Corrected') . '</span></a>';
                })
                ->addColumn('actions', function ($row) {
                    return $row->action_data;
                })
                ->make();
        }
        $years = Year::query()->get();
        $schools = School::query()->active()->get();
        return view('manager.student_term.index', compact('title', 'years', 'schools'));
    }

    //correcting
    public function edit($id)
    {
        $student_term = StudentTerm::with(['student', 'term'])->where('id', $id)->first();
        $student = $student_term->student;
        $questions = Question::with(
            ['option_question',
                'option_question_result' => function ($query) use ($student, $student_term) {
                    $query->where('student_term_id', '=', $student_term->id);
                },

            ])->where('term_id', $student_term->term_id)->get();

        foreach ($questions as $question) {
            switch ($question['type']) {
                case 'multiple_choice':
                    $question['result'] = count($question->option_question_result) > 0 ? $question->option_question_result[0] : null;
                    break;

            }

        }

        $questions_count = count($questions);
        $marks = 100;
        $correct_mode = true;
        $term = $student_term->term;


        return view('manager.student_term.term_correcting.index', compact('term', 'student', 'questions', 'student_term', 'questions_count', 'marks', 'correct_mode'));
    }

    //correcting
    public function updateTerm(Request $request, $id)
    {
        $request->validate(['questions' => 'required|array']);
        //dd($request['questions']);
        $student_term = StudentTerm::query()->where('id', $id)->first();
        $student_id = $student_term->student_id;

        $term_questions = Question::with(['option_question'])
            ->where('term_id', $student_term->term_id)->get();
        // dd($term_questions->toArray());

        DB::transaction(function () use ($request, $id, $student_term, $student_id, $term_questions) {

            $total = 0;

            foreach ($request['questions'] as $question_id => $question) {
                $mark = 0;

                switch ($question['type']) {

                    case 'multiple_choice':
                        if (isset($question['answer_option_id'])) {
                            $mark = $this->saveOptionResultAndCorrect($student_id, $id, $question_id, $question, $term_questions);
                        }
                        break;
                }

                $total += $mark;

                $this->saveStudentTermStandard($id, $question_id, $mark);

            }
            if (!is_null($student_term->dates_at)) {
                $dates_at = $student_term->dates_at;
            } else {
                $dates_at = [
                    'started_at' => null,
                    'submitted_at' => null,
                ];
            }
            $dates_at['corrected_at'] = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
            $dates_at['corrected_by'] = Auth::guard('manager')->user()->id;
            $student_term->update([
                'total' => $total,
                'corrected' => true,
                'dates_at' => $dates_at
            ]);

        });
        return redirect()->route('manager.student_term.index', ['status' => 'corrected'])->with('message', t('The term has been corrected'));
    }


    private function saveOptionResultAndCorrect($student_id, $student_term_id, $question_id, $result, $term_questions)
    {
        if (isset($result['question_result_id'])) {
            //update
            OptionQuestionResult::query()
                ->where('id', $result['question_result_id'])
                ->update(['option_id' => $result['answer_option_id']]);

        } else {
            //create
            OptionQuestionResult::query()->create([
                'student_id' => $student_id,
                'student_term_id' => $student_term_id,
                'question_id' => $question_id,
                'option_id' => $result['answer_option_id'],
            ]);
        }

        //correct question
        $question = $term_questions->where('id', $question_id)->first();
        $correct_option = collect($question->option_question)->where('id', $result['answer_option_id'])->first();

        if ($correct_option->result == 1) {
            return $question->mark;
        }

        return 0;
    }


    private function saveStudentTermStandard($student_term_id, $question_id, $mark)
    {
        $question_standard = QuestionStandard::query()->where('question_id', $question_id)->first();
        if ($question_standard) {
            StudentTermStandard::query()->updateOrCreate(
                [
                    'student_term_id' => $student_term_id,
                    'question_standard_id' => $question_standard->id,
                ],
                [
                    'student_term_id' => $student_term_id,
                    'question_standard_id' => $question_standard->id,
                    'mark' => $mark,
                ]
            );
        }
    }

    public function deleteStudentTerm(Request $request)
    {
        $request->validate(['row_id' => 'required']);
        StudentTerm::query()->whereIn('id', $request->get('row_id'))->delete();
        return $this->sendResponse(null, t('Student term deleted successfully'));
    }


    public function studentsTermsExport(Request $request)
    {
        return (new StudentTermExport($request))->download('Students Terms Information.xlsx');
    }

    public function autoCorrect(Request $request)
    {
        $request->validate(['year_id' => 'required']);
        //get student term doesnt have article question
        $students_terms = StudentTerm::query()->search($request)
            ->whereDoesntHave('term.question', function (Builder $query) {
                $query->where('type', '=', 'article');
            })->get();


        foreach ($students_terms as $student_term) {
            $student_term_id = $student_term->id;
            $student_id = $student_term->student_id;
            $term_questions = Question::with(
                ['option_question',
                    'option_question_result' => function ($query) use ($student_term_id) {
                        $query->where('student_term_id', $student_term_id);
                    }
                ])
                ->where('term_id', $student_term->term_id)->get();

            DB::transaction(function () use ($request, $student_term_id, $student_term, $term_questions) {

                $total = 0;

                foreach ($term_questions as $question) {
                    $mark = 0;

                    if ($question->type == 'multiple_choice' && $question->option_question_result->isNotEmpty()) {
                        //correct question
                        $correct_option = collect($question->option_question)->where('id', $question->option_question_result[0]->option_id)->first();
                        if ($correct_option->result == 1) {
                            $mark = $question->mark;
                        }
                    }

                    $total += $mark;


                    $this->saveStudentTermStandard($student_term_id, $question->id, $mark);

                }
                if (!is_null($student_term->dates_at)) {
                    $dates_at = $student_term->dates_at;
                } else {
                    $dates_at = [
                        'started_at' => null,
                        'submitted_at' => null,
                    ];
                }
                $dates_at['corrected_at'] = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
                $dates_at['corrected_by'] = Auth::guard('manager')->user()->id;
                $student_term->update([
                    'total' => $total,
                    'corrected' => true,
                    'dates_at' => $dates_at
                ]);

            });
        }
        return $this->sendResponse(null, t('Students Terms Corrected Successfully, Corrected Terms Number') . ' (' . $students_terms->count() . ')');

    }


    public function restore($id)
    {
        $student_term = StudentTerm::query()->where('id', $id)->withTrashed()->first();

        $has_term = StudentTerm::query()
            ->where('term_id', $student_term->term_id)
            ->where('student_id', $student_term->student_id)
            ->get();

        if ($has_term->count() > 0) {
            return $this->sendError(t('Student has term and student term not restored'), 402);
        }
        if ($student_term) {
            $student_term->restore();
            //restore result
            OptionQuestionResult::query()->where('student_term_id', $id)->withTrashed()->update(['deleted_at' => null]);
            StudentTermStandard::query()->where('student_term_id', $id)->withTrashed()->update(['deleted_at' => null]);
            return $this->sendResponse(null, t('Successfully Restored'));
        } else {
            return $this->sendError(t('Student Term Not Restored'), 402);
        }
    }
}
