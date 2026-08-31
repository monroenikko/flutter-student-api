<?php

namespace App\Services;

use App\Models\{StudentInformation, SiblingGroupMember};
use App\Traits\{ResponseApi, HasSiblingAccess};
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class SiblingService
{
    use ResponseApi, HasSiblingAccess;

    /**
     * Get all linked siblings for the authenticated user's student.
     *
     * Returns the logged-in student plus all students in the same sibling group.
     */
    public function index()
    {
        $ownStudent = StudentInformation::where('user_id', Auth::id())->first();

        if (!$ownStudent) {
            return $this->error('Student record not found.', Response::HTTP_NOT_FOUND);
        }

        $playerId = request('player_id');
        $this->syncSiblingSubscriptions(Auth::user(), $playerId);

        $member = SiblingGroupMember::where('student_information_id', $ownStudent->id)->first();

        if (!$member) {
            // No sibling group — return only the current student
            return $this->success('No sibling group found.', Response::HTTP_OK, [
                'has_siblings' => false,
                'group_code' => null,
                'students' => [
                    $this->formatStudentSummary($ownStudent),
                ],
            ]);
        }

        // Fetch all students in the sibling group
        $siblingStudentIds = SiblingGroupMember::where('sibling_group_id', $member->sibling_group_id)
            ->pluck('student_information_id');

        $students = StudentInformation::whereIn('id', $siblingStudentIds)
            ->get()
            ->map(fn($s) => $this->formatStudentSummary($s));

        return $this->success('Siblings fetched successfully.', Response::HTTP_OK, [
            'has_siblings' => $students->count() > 1,
            'group_code' => $member->siblingGroup->group_code,
            'current_student_id' => $ownStudent->id,
            'students' => $students,
        ]);
    }

    /**
     * Format a student record into a summary for the sibling list.
     */
    private function formatStudentSummary(StudentInformation $student): array
    {
        return [
            'id' => $student->id,
            'full_name' => $student->full_name,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'photo' => $student->photo,
            'user_id' => $student->user_id,
        ];
    }
}
