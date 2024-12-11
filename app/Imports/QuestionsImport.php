<?php

namespace App\Imports;

use App\Exceptions\GeneralException;
use App\Models\MatchQuestion;
use App\Models\OptionQuestion;
use App\Models\Question;
use App\Models\FillBlankQuestion;
use App\Models\QuestionFile;
use App\Models\SortQuestion;
use App\Models\Subject;
use App\Models\TFQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use Maatwebsite\Excel\Validators\Failure;

HeadingRowFormatter::default('none');

class QuestionsImport implements ToModel, WithHeadingRow, SkipsOnFailure, SkipsOnError, WithValidation,ToCollection
{
    private $created_rows_count = 0;
    private $updated_rows_count = 0;
    private $deleted_rows_count = 0;
    private $failed_rows_count = 0;
    private $error = null;
    private $failures = [];
    private $log_errors = [];

    private $request;

    private $questionFile;

    private $rowNumber = 1;

    public function __construct(Request $request, QuestionFile $questionFile)
    {
        $this->request = $request;
        $this->questionFile = $questionFile;
    }

    public function model(array $row)
    {
        $this->rowNumber++; // Increment row number for tracking

        // Check if 'Question Title' is present and valid for all types
        if (empty($row['Question Title'])) {
            $this->failures[$this->rowNumber] = [ 'Question Title is required.'];
            return null; // Skip this row
        }


        // check if question type is Multiple choice and there are 4 options and at least one has '(Yes)'
        if ($row['Question Type'] === 'Multiple choice') {
            $yesCount = 0;
            foreach (range(1, 4) as $i) {
                if (!empty($row['Option ' . $i])) {
                    if (strpos($row['Option ' . $i], '(Yes)') !== false) {
                        $yesCount++;
                    }
                }
            }
            if ($yesCount === 0) {
                $this->failures[$this->rowNumber] = [
                    'At least one option must have (Yes) for Multiple choice type.'
                ];
                $this->failed_rows_count++;
                return null; // Skip this row
            }
        }



        // Create the question based on the validated data
        $question = Question::create([
            'content' => $row['Question Title'],
            'term_id' => $this->questionFile->term_id,
            'type' => snake_case($row['Question Type']),
            'author_id' => auth()->guard(getGuard())->user()->id,
            'author_type' => auth()->guard(getGuard())->user()->getMorphClass(),
            'mark' => $row['Mark'], // Assuming 1 mark for each question
            'question_file_id' => $this->questionFile->id,
        ]);

        // Process options and create related models (True/False, Multiple Choice, etc.)
        $options_count = $this->processOptions($row, $question);
//
//
//        $question->update(['min_mark' => $options_count]); // Update min_mark based on the number of options

        $this->created_rows_count++;
    }

    public function prepareForValidation(array $row)
    {
        // Trim both keys (headers) and values (cell data)
        $trimmedKeys = array_map('trim', array_keys($row));
        $trimmedValues = array_map('trim', array_values($row));

        //replace '[Blank]' with '[blank]'
        $trimmedValues = array_map(function ($value) {
            return str_replace('[Blank]', '[blank]', $value);
        }, $trimmedValues);

        // Rebuild the row with the trimmed keys and values
        return array_combine($trimmedKeys, $trimmedValues);
    }


    public function rules(): array
    {
        return [
            'Question Title' => [
                'required',
            ],
            'Mark' => [
                'required','integer'
            ],

            // Multiple choice validation (4 options are required)
            'Option 1' => 'required',
            'Option 2' => 'required',
            'Option 3' => 'required',
            'Option 4' => 'required',


        ];
    }

    /**
     * @return int
     */
    public function getUpdatedRowsCount(): int
    {
        return $this->updated_rows_count;
    }

    public function getDeletedRowsCount(): int
    {
        return $this->deleted_rows_count;
    }

    /**
     * @return int
     */
    public function getRowsCount(): int
    {
        return $this->created_rows_count;
    }

    /**
     * @return int
     */
    public function getFailedRowCount(): int
    {
        return $this->failed_rows_count;
    }

    /**
     * @return null
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return array
     */
    public function getFailures(): array
    {
        return $this->failures;
    }

    protected function processOptions(array $row, Question $question)
    {
        foreach (range(1, 4) as $i) {
            if (!empty($row['Option ' . $i])) {
                OptionQuestion::create([
                    'question_id' => $question->id,
                    'content' => trim(Str::replace('(Yes)','',$row['Option ' . $i])),
                    'result' => strpos($row['Option ' . $i], '(Yes)') !== false,
                ]);
            }
        }

    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->log_errors[] = "Row " . $failure->row() . ' : ' . $failure->errors()[0];
            $this->failures[$failure->row()] = $failure->errors();
            $this->failed_rows_count++;
        }
    }

    public function onError(\Throwable $e)
    {
        $this->error = $e->getMessage();
    }

    public function collection(Collection $rows)
    {

        $total =  collect($rows)->sum('Mark');

        if ($total != 100){

            $this->failures['Marks Total'] = ['The marks total not equal 100'];
        }
    }

}
