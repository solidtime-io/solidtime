<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlannerCanonicalTemplateSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            DB::table('milestone_templates')->delete();
            DB::table('phase_templates')->delete();

            $position = 1;
            $phases = [
                'Phase 1 – Concept Design' => [
                    'Initial Site Visit',
                    'Welcome Email',
                    'Concepts / Layouts Sent',
                    'Concept Meeting 1',
                    'Revisions Sent',
                    'Concept Meeting 2',
                    'Phase 1 Signed Off',
                ],
                'Phase 2 – Design Development' => [
                    'Joinery & Elec Drawings Sent',
                    'DD Presentation Sent',
                    'DD Meeting 1',
                    'Revisions Sent',
                    'DD Meeting 2',
                    'Drawing & Design Updates Sent',
                    'Phase 2 Signed off',
                ],
                'Phase 3 – Specification' => [
                    'Budget Summary Sent',
                    'Spec Meeting 1 (Budget Review)',
                    'Spec Presentation Sent',
                    'Samples Meeting',
                    'Finishes Schedule Sent',
                    'Joinery Review Meeting',
                    'Final Drawing Pack Issued',
                    'Spec Review Meeting',
                    'Phase 3 Signed Off',
                ],
                'Phase 4 – Implementation (key coordination checkpoints)' => [
                    'Procurement Agreement Signed',
                    'Coordination Agreement Signed',
                    'Handover Meeting',
                    'Procurement Supply Agreed',
                    'Project Timelines Agreed',
                ],
                'Implementation (site and supplier schedule checkpoints)' => [
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
                ],
            ];

            foreach ($phases as $phaseName => $milestones) {
                $phaseId = (string) Str::uuid();
                DB::table('phase_templates')->insert([
                    'id' => $phaseId,
                    'name' => $phaseName,
                    'position' => $position++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $mPos = 1;
                foreach ($milestones as $name) {
                    DB::table('milestone_templates')->insert([
                        'id' => (string) Str::uuid(),
                        'phase_template_id' => $phaseId,
                        'name' => $name,
                        'position' => $mPos++,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }
}
