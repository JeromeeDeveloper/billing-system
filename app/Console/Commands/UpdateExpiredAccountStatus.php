<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Member;

class UpdateExpiredAccountStatus extends Command
{
    protected $signature = 'members:update-expired-status';
    protected $description = 'Update account_status to non-deduction if expiry_date has passed';

    public function handle()
    {
        $updated = Member::whereDate('expiry_date', '<=', now())
            ->where('account_status', 'non-deduction')
            ->update(['account_status' => 'deduction']);

        $this->info("Updated {$updated} members to deduction.");
    }
}
