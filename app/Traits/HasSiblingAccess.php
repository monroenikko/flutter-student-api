<?php

namespace App\Traits;

use App\Models\{StudentInformation, SiblingGroupMember, Subscription, User};
use Illuminate\Support\Facades\Auth;

trait HasSiblingAccess
{
    /**
     * Get the authenticated user's student record,
     * or a sibling's record if student_id is provided and authorized.
     *
     * @param int|null $studentId
     * @return StudentInformation|null
     */
    protected function getAuthorizedStudent($studentId = null)
    {
        $ownStudent = StudentInformation::where('user_id', Auth::id())->first();

        if (!$ownStudent) {
            return null;
        }

        // If no student_id provided, or it matches the logged-in student
        if (!$studentId || $ownStudent->id == $studentId) {
            return $ownStudent;
        }

        // Check if student belongs to the same user account directly
        $sameUserStudent = StudentInformation::where('user_id', Auth::id())->where('id', $studentId)->first();
        if ($sameUserStudent) {
            return $sameUserStudent;
        }

        // Check if the requested student is in the same sibling group
        $ownMember = SiblingGroupMember::where('student_information_id', $ownStudent->id)->first();

        if (!$ownMember) {
            abort(403, 'Unauthorized access to student data.');
        }

        $isSibling = SiblingGroupMember::where('sibling_group_id', $ownMember->sibling_group_id)
            ->where('student_information_id', $studentId)
            ->exists();

        if (!$isSibling) {
            abort(403, 'Unauthorized access to student data.');
        }

        return StudentInformation::findOrFail($studentId);
    }

    /**
     * Sync mobile push notification subscriptions across all students in the sibling group.
     *
     * @param User|null $user
     * @param string|null $playerId
     * @return void
     */
    public function syncSiblingSubscriptions($user = null, $playerId = null)
    {
        $user = $user ?? Auth::user();
        if (!$user) {
            return;
        }

        $userClass = get_class($user);

        // Collect all player_ids from the current user and/or incoming parameter
        $existingPlayerIds = Subscription::where('subscribable_type', $userClass)
            ->where('subscribable_id', $user->id)
            ->pluck('player_id')
            ->filter();

        $playerIds = collect([$playerId])
            ->merge($existingPlayerIds)
            ->filter()
            ->unique();

        if ($playerIds->isEmpty()) {
            return;
        }

        // Find own student
        $ownStudent = StudentInformation::where('user_id', $user->id)->first();
        if (!$ownStudent) {
            return;
        }

        // Check if own student is in a sibling group
        $member = SiblingGroupMember::where('student_information_id', $ownStudent->id)->first();
        if (!$member) {
            // Also check if multiple students share this user_id
            $sameUserStudentIds = StudentInformation::where('user_id', $user->id)->pluck('id');
            $member = SiblingGroupMember::whereIn('student_information_id', $sameUserStudentIds)->first();
        }

        if (!$member) {
            return;
        }

        // Fetch all student records in the sibling group
        $siblingStudentIds = SiblingGroupMember::where('sibling_group_id', $member->sibling_group_id)
            ->pluck('student_information_id');

        $siblingUserIds = StudentInformation::whereIn('id', $siblingStudentIds)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->unique();

        foreach ($siblingUserIds as $siblingUserId) {
            foreach ($playerIds as $pid) {
                Subscription::firstOrCreate([
                    'subscribable_type' => $userClass,
                    'subscribable_id' => $siblingUserId,
                    'player_id' => $pid,
                ]);
            }
        }
    }
}
