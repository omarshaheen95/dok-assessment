<?php

namespace Database\Seeders;

use App\Models\Level;
use App\Models\Question;
use App\Models\Term;
use App\Models\Year;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class YearTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::transaction(function () {
            $years = [
                ['name' => '2023/2024', 'default' => 0],
                ['name' => '2024/2025', 'default' => 1],
            ];
            foreach ($years as $year) {
                $year = Year::query()->updateOrCreate([
                    'name' => $year['name'],
                ], $year);

                $level = Level::query()->updateOrCreate([
                    'name' => 'Level' . $year['name'],
                    'year_id' => $year->id,
                    'arab' => 1,
                    'grade' => 1,
                    'active' => 1,
                ]);


                $term = Term::query()->updateOrCreate([
                    'name' =>  'Term' . $year['name'],
                    'level_id' => $level->id,
                    'round' => 'may',
                    'active' => true,
                    'duration' => 40
                ]);

                if ($term->wasRecentlyCreated) {
                    $questions = [];
                    foreach (range(1, 40) as $item) {
                        $questions [] = [
                            'term_id' => $term->id,
                            'mark' => 2.5,
                            'content' => 'Q' . $item,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    Question::insert($questions);
                }
            }

        });
    }
}
