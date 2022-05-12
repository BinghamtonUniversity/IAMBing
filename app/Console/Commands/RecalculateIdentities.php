<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use App\Models\Group;
use App\Models\Identity;

class InitBulkLoad extends Command
{
    protected $name = 'recalculate';
    protected $description = 'Recalculate Identities in the specified group';

    public function handle() {
        ini_set('memory_limit','1024M');
        $all_groups = Group::select('id','name','slug')->get();
        if (count($all_groups) == 0) {
            $this->error("No Available Groups... Exiting");
            return;
        }
        $group_name = $this->choice(
            'Which group would you like to recalculate?',
            $all_groups->pluck('name')->toArray(),
        );
        $target_group = $all_groups->firstWhere('name',$group_name);

        if (!$this->confirm('Are you sure you want recalcuate all Identities within the "'.$group_name.'" group?  This action can not be undone!')) {
            $this->error("Exiting");
            return;
        }
        $target_identities = Identity::whereHas('group_memberships',function ($query) use ($target_group) {
            $query->where('group_id',$target_group->id);
        })->get();

        $num_members = count($target_identities);

        $bar = $this->output->createProgressBar($num_members);
        // ProgressBar::setFormatDefinition('custom', ' %current%/%max% -- %message%');
        // $bar->setFormat('custom');
        foreach($target_identities as $index => $target_identity) {
            $percent_complete = floor(($index / $num_members)*100).'%';
            $bar->setMessage($target_identity->first_name.' '.$target_identity->last_name);
            $target_identity->recalculate_entitlements();
            $bar->advance();
        }
    }
}