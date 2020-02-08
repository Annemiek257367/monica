<?php

namespace App\Helpers;

use App\Models\Account\Account;
use Illuminate\Support\Collection;

class AccountHelper
{
    /**
     * Indicates whether the given account has limitations with her current
     * plan.
     *
     * @return bool
     */
    public static function hasLimitations(Account $account): bool
    {
        if ($account->has_access_to_paid_version_for_free) {
            return false;
        }

        if (! config('monica.requires_subscription')) {
            return false;
        }

        if ($account->isSubscribed()) {
            return false;
        }

        return true;
    }

    /**
     * Indicate whether an account has reached the contact limit if the account
     * is on a free trial.
     *
     * @param Account $account
     * @return bool
     */
    public static function hasReachedContactLimit(Account $account): bool
    {
        return $account->contacts()->real()->active()->count() >= config('monica.number_of_allowed_contacts_free_account');
    }

    /**
     * Check if the account can be downgraded, based on a set of rules.
     *
     * @param Account $account
     * @return bool
     */
    public static function canDowngrade(Account $account): bool
    {
        $canDowngrade = true;
        $numberOfUsers = $account->users()->count();
        $numberPendingInvitations = $account->invitations()->count();
        $numberContacts = $account->contacts()->count();

        // number of users in the account should be == 1
        if ($numberOfUsers > 1) {
            $canDowngrade = false;
        }

        // there should not be any pending user invitations
        if ($numberPendingInvitations > 0) {
            $canDowngrade = false;
        }

        // there should not be more than the number of contacts allowed
        if ($numberContacts > config('monica.number_of_allowed_contacts_free_account')) {
            $canDowngrade = false;
        }

        return $canDowngrade;
    }

    /**
     * Get the number of activities grouped by year.
     *
     * @param Account $account
     * @return Collection
     */
    public static function getYearlyActivitiesStatistics(Account $account): Collection
    {
        $activitiesStatistics = collect([]);
        $activities = $account->activities()
            ->select('happened_at')
            ->latest('happened_at')
            ->get();
        $years = [];

        foreach ($activities as $activity) {
            $yearStatistic = $activity->happened_at->format('Y');
            $foundInYear = false;

            foreach ($years as $year => $number) {
                if ($year == $yearStatistic) {
                    $years[$year] = $number + 1;
                    $foundInYear = true;
                }
            }

            if (! $foundInYear) {
                $years[$yearStatistic] = 1;
            }
        }

        foreach ($years as $year => $number) {
            $activitiesStatistics->put($year, $number);
        }

        return $activitiesStatistics;
    }

    /**
     * Get the number of calls grouped by year.
     *
     * @return Collection
     */
    public static function getYearlyCallStatistics(Account $account): Collection
    {
        $callsStatistics = collect([]);
        $calls = $account->calls()
            ->select('called_at')
            ->latest('called_at')
            ->get();
        $years = [];

        foreach ($calls as $call) {
            $yearStatistic = $call->called_at->format('Y');
            $foundInYear = false;

            foreach ($years as $year => $number) {
                if ($year == $yearStatistic) {
                    $years[$year] = $number + 1;
                    $foundInYear = true;
                }
            }

            if (! $foundInYear) {
                $years[$yearStatistic] = 1;
            }
        }

        foreach ($years as $year => $number) {
            $callsStatistics->put($year, $number);
        }

        return $callsStatistics;
    }
}
