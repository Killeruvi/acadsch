<?php

namespace App\Services;

use App\Models\InAppNotification;
use App\Models\User;
use App\Models\UserBadge;
use Carbon\Carbon;

class BirthdayService
{
    /**
     * Check if today is the user's birthday and handle automated celebrations.
     */
    public static function checkAndCelebrate(User $user): ?array
    {
        if (! $user->date_of_birth) {
            return null;
        }

        $today = Carbon::today();
        $dob = Carbon::parse($user->date_of_birth);

        if ($today->month !== $dob->month || $today->day !== $dob->day) {
            return null;
        }

        $school = SchoolBrandingService::get();
        $schoolName = $school['name'] ?? config('app.name', 'School ERP');
        $currentYear = $today->year;
        $badgeKey = 'birthday_star_'.$currentYear;

        // Check if badge already given for this year
        $existingBadge = UserBadge::where('user_id', $user->id)
            ->where('badge_key', $badgeKey)
            ->first();

        $alreadyAwardedThisYear = (bool) $existingBadge;

        if (! $alreadyAwardedThisYear) {
            // 1. Award +100 Birthday Bonus XP
            $user->increment('xp_points', 100);
            $user->refreshTier();
            $user->save();

            // 2. Award Digital "Birthday Star" Badge
            UserBadge::create([
                'user_id' => $user->id,
                'badge_key' => $badgeKey,
                'title' => "Birthday Star {$currentYear}",
                'icon' => '🎂',
                'category' => 'milestones',
                'description' => "Celebrated another year of intellectual growth at {$schoolName}!",
                'awarded_at' => now(),
            ]);

            // 3. Dispatch Celebratory Notification
            InAppNotification::create([
                'user_id' => $user->id,
                'type' => 'general',
                'title' => "🎉 Happy Birthday from {$schoolName}!",
                'message' => "Wishing you an exceptional birthday, {$user->name}! We have credited +100 XP to your profile as an institutional birthday gift. Aim, Ascend, Achieve! 🎂🔥",
                'icon' => '🎂',
                'action_url' => '/profile',
            ]);
        }

        return [
            'is_birthday' => true,
            'name' => $user->name,
            'age' => $today->year - $dob->year,
            'bonus_xp' => 100,
            'already_awarded' => $alreadyAwardedThisYear,
            'message' => "Happy Birthday, {$user->name}! 🎂 The entire {$schoolName} community celebrates your special day. +100 XP gifted!",
        ];
    }
}
