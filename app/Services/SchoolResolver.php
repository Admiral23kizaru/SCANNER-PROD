<?php

namespace App\Services;

use App\Models\Ehris\EhrisDepartment;
use App\Models\School;

class SchoolResolver
{
    public function findExistingOrEhrisDepartment(string $depedId): ?array
    {
        $depedId = $this->normalizeDepedId($depedId);

        if ($depedId === '') {
            return null;
        }

        $school = School::where('deped_school_id', $depedId)->first();

        if ($school) {
            return [
                'school' => $school,
                'name' => $school->name,
                'deped_id' => $school->deped_school_id,
                'source' => 'tbl_scanup_schools',
            ];
        }

        $department = EhrisDepartment::where('department_id', $depedId)->first();

        if (! $department) {
            return null;
        }

        return [
            'school' => null,
            'name' => $this->departmentName($department, $depedId),
            'deped_id' => $depedId,
            'source' => 'tbl_depart',
        ];
    }

    public function resolveForScanUpWrite(string $depedId): ?School
    {
        $depedId = $this->normalizeDepedId($depedId);

        if ($depedId === '') {
            return null;
        }

        $school = School::where('deped_school_id', $depedId)->first();

        if ($school) {
            return $school;
        }

        $department = EhrisDepartment::where('department_id', $depedId)->first();

        if (! $department) {
            return null;
        }

        return School::firstOrCreate(
            ['deped_school_id' => $depedId],
            ['name' => $this->departmentName($department, $depedId)]
        );
    }

    private function normalizeDepedId(string $depedId): string
    {
        $depedId = trim($depedId);

        if (strlen($depedId) > 50 || ! preg_match('/^[A-Za-z0-9\-]+$/', $depedId)) {
            return '';
        }

        return $depedId;
    }

    private function departmentName(EhrisDepartment $department, string $depedId): string
    {
        $name = trim((string) ($department->department_name ?? ''));

        return $name !== '' ? $name : 'School ' . $depedId;
    }
}
