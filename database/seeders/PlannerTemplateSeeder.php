<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ProjectMilestoneTemplate;
use App\Models\ProjectPhaseTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlannerTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $phaseName = 'Implementation (site and supplier schedule checkpoints)';
        $milestones = [
            'Estimated Completion Date',
            'First Fix Plumb/Elec Required',
            'Wood Flooring Required',
            'Second Fix Plumbing Required',
            'Decorative Lighting Required',
            'Plastering Completed',
            'Hard Flooring Laid',
            'Paints & Grouts Confirmed',
            'Joinery Templating',
            'Curtain Templating',
            'Carpet Templating',
            'Bespoke Headboard Measure',
            'Appliances to Joiners',
            'Sinks and Taps to Site',
            'Furniture Access Check',
            'Long Lead Time Furniture Ordered',
            'Curtain Check Measure',
            'Joinery Installation',
            'Kitchen Installation',
            'Worktop Templating',
            'Joinery Handles To Joiners',
            '2nd Fix Plumb / Elec Completed',
            'Worktop Installation',
            'Carpet Installation',
            'Curtain Installation',
            'Snagging',
            'Furniture Installation',
            'Receipts & Aftercare',
            'Review',
            'Photoshoot',
        ];

        DB::transaction(function () use ($phaseName, $milestones): void {
            // Clear existing templates (if any) to keep canonical order
            ProjectMilestoneTemplate::query()->delete();
            ProjectPhaseTemplate::query()->delete();

            $phase = new ProjectPhaseTemplate();
            $phase->name = $phaseName;
            $phase->position = 1;
            $phase->save();

            foreach ($milestones as $idx => $name) {
                $mt = new ProjectMilestoneTemplate();
                $mt->project_phase_template_id = $phase->id;
                $mt->name = $name;
                $mt->is_milestone = true;
                $mt->due_offset_days = null; // leave unspecified; dates set per-project later
                $mt->position = $idx + 1;
                $mt->save();
            }
        });
    }
}
