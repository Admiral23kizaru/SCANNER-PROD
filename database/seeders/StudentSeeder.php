<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\School;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * // Description: run - Generates 10 dummy students for testing the dashboard and teacher filters.
     * // Author: System Agent
     */
    public function run(): void
    {
        // Require an admin or arbitrary user to associate as created_by
        $admin = User::first();
        if (!$admin) {
            $this->command->info("No users found. Ensure users exist before seeding students.");
            return;
        }

        $school = School::first();
        $schoolId = $school ? $school->id : 1;
        $teacher = User::where('role_id', '!=', 1)->first() ?? $admin;

        $dummyStudents = [];
        $grades = ['Grade 1', 'Grade 3', 'Grade 6'];
        $sections = ['Section A', 'Section B', 'Section C'];
        $genders = ['Male', 'Female'];

        for ($i = 1; $i <= 10; $i++) {
            $dummyStudents[] = [
                'teacher_id'     => $teacher->id,
                'created_by'     => $admin->id,
                'student_number' => str_pad(mt_rand(1, 999999999999), 12, '0', STR_PAD_LEFT),
                'first_name'     => "DummyFirst{$i}",
                'last_name'      => "DummyLast{$i}",
                'gender'         => $genders[array_rand($genders)],
                'grade'          => $grades[array_rand($grades)],
                'section'        => $sections[array_rand($sections)],
                'grade_section'  => 'G-Sec ' . $i,
                'school_id'      => $schoolId,
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        }

        DB::table('students')->insert($dummyStudents);
        $this->command->info("Inserted 10 dummy students layout into the students table!");
    }
}
