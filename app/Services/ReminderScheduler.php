<?php

namespace App\Services;

use App\Models\License;
use App\Models\ReminderLog;
use Carbon\Carbon;

class ReminderScheduler
{
    /**
     * Generate or regenerate reminder schedules for a given license.
     * Idempotent: respects existing sent/failed logs and adjusts pending/skipped ones.
     */
    public function generateFor(License $license): void
    {
        $milestones = config('reminder.milestones', [90, 60, 45, 30, 21, 14, 7, 6, 5, 4, 3, 2, 1, 0, -7, -14]);
        $sendTime = setting('reminder_send_time', config('reminder.send_time', '09:00'));
        
        // Parse the send_time config to get hour and minute
        [$hour, $minute] = explode(':', $sendTime);

        $now = Carbon::now('Asia/Jakarta');

        foreach ($milestones as $milestone) {
            // Calculate scheduled_at: end_date - milestone days, at the specified send time
            $scheduledAt = $license->end_date->copy()
                ->setTimezone('Asia/Jakarta')
                ->subDays($milestone)
                ->setTime((int)$hour, (int)$minute);

            // Determine what status this schedule SHOULD have based on time
            $expectedStatus = $scheduledAt->isPast() ? 'skipped' : 'pending';

            // Find existing log for this milestone
            $existingLog = ReminderLog::where('license_id', $license->id)
                ->where('milestone', $milestone)
                ->first();

            if ($existingLog) {
                // Do not touch historical logs that have already been processed
                if (in_array($existingLog->status, ['sent', 'failed'])) {
                    continue;
                }

                // For pending/queued/skipped, update the schedule and status
                $existingLog->update([
                    'scheduled_at' => $scheduledAt,
                    'status'       => $expectedStatus,
                ]);
            } else {
                // Create new log
                ReminderLog::create([
                    'license_id'   => $license->id,
                    'milestone'    => $milestone,
                    'scheduled_at' => $scheduledAt,
                    'status'       => $expectedStatus,
                ]);
            }
        }
    }
}
