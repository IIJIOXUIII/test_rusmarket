<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\ShiftTemplate;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Идемпотентный сид: только если база ещё пуста
        if (Employee::query()->exists()) {
            return;
        }
        $employees = [
            ['Кузнецова Алина', '#8B5CF6'],
            ['Волков Тимур', '#0EA5E9'],
            ['Морозова Виктория', '#F59E0B'],
            ['Соколов Артём', '#10B981'],
            ['Лебедева Полина', '#EC4899'],
            ['Новиков Кирилл', '#F97316'],
            ['Ерохина Дарья', '#6366F1'],
            ['Гаврилов Егор', '#14B8A6'],
        ];

        foreach ($employees as [$name, $color]) {
            Employee::create([
                'name' => $name,
                'position' => 'агент',
                'hours_limit' => 24,
                'avatar_color' => $color,
            ]);
        }

        foreach ([
            ['Утро', '07:00', '15:00', 1],
            ['День', '10:00', '18:00', 2],
            ['Вечер', '15:00', '23:00', 3],
        ] as [$name, $start, $end, $sort]) {
            ShiftTemplate::create([
                'name' => $name,
                'start_time' => $start,
                'end_time' => $end,
                'sort' => $sort,
            ]);
        }
    }
}
