<?php

namespace App\Console\Commands;

use App\Enums\LocationConfidence;
use App\Enums\LocationEvidenceType;
use App\Enums\LocationStatus;
use App\Models\Area;
use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProjectLocationDiscover extends Command
{
    protected $signature = 'projects:location-discover {--json : Output as JSON} {--apply-candidates : Apply the discovered candidates as pending_review}';

    protected $description = 'Discover local SEO evidence for projects by scanning metadata and media';

    public function handle(): int
    {
        $areas = Area::pluck('id', 'name')->toArray();
        $projects = Project::with('media')->whereNull('location_status')->orWhere('location_status', 'draft')->get();

        $results = [];
        $stats = [
            'total' => $projects->count(),
            'strong' => 0,
            'medium' => 0,
            'conflict' => 0,
            'no_evidence' => 0,
        ];

        // Find shared media to exclude them from location evidence
        $sharedMediaIds = DB::table('project_media')
            ->select('media_id')
            ->groupBy('media_id')
            ->havingRaw('COUNT(project_id) > 1')
            ->pluck('media_id')
            ->toArray();

        foreach ($projects as $project) {
            $matches = []; // area_id => ['confidence' => X, 'source' => Y, 'type' => Z, 'ref' => W]

            // 1. Scan Project Text (Medium Confidence = 2)
            $projectText = implode(' ', array_filter([
                $project->title,
                $project->summary,
                $project->source_ref,
                $project->challenge,
                $project->site_condition,
                $project->outcome,
                $project->client_problem,
                $project->client_benefit,
                $project->execution_difference,
            ]));

            foreach ($areas as $areaName => $areaId) {
                if (mb_strpos($projectText, $areaName) !== false) {
                    $matches[$areaId] = [
                        'confidence' => LocationConfidence::ClientProvided, // 2
                        'source' => 'project metadata',
                        'type' => LocationEvidenceType::ManualRecord,
                        'ref' => 'Matched in project text',
                    ];
                }
            }

            // 2. Scan Media Text (Strong Confidence = 3)
            foreach ($project->media as $media) {
                if (in_array($media->id, $sharedMediaIds)) {
                    continue; // Skip shared media
                }

                $mediaText = implode(' ', array_filter([
                    $media->original_filename,
                    $media->alt_text,
                    $media->verified_description,
                    $media->source_group,
                ]));

                foreach ($areas as $areaName => $areaId) {
                    if (mb_strpos($mediaText, $areaName) !== false) {
                        // If we already have a match for this area, upgrade it to strong if it's currently medium
                        if (! isset($matches[$areaId]) || $matches[$areaId]['confidence']->value < 3) {
                            $matches[$areaId] = [
                                'confidence' => LocationConfidence::MediaEvidence, // 3
                                'source' => 'media metadata',
                                'type' => LocationEvidenceType::PhotoMetadata,
                                'ref' => $media->original_filename ?: "media-{$media->id}",
                            ];
                        }
                    }
                }
            }

            $candidateAreaId = null;
            $status = 'no_evidence';
            $matchDetails = null;

            if (count($matches) === 0) {
                $status = 'no_evidence';
                $stats['no_evidence']++;
            } elseif (count($matches) > 1) {
                $status = 'conflict';
                $stats['conflict']++;
            } else {
                // Exactly one area matched
                $candidateAreaId = array_key_first($matches);
                $matchDetails = $matches[$candidateAreaId];

                if ($matchDetails['confidence']->value >= 3) {
                    $status = 'strong';
                    $stats['strong']++;
                } else {
                    $status = 'medium';
                    $stats['medium']++;
                }
            }

            $results[] = [
                'project_id' => $project->id,
                'title' => $project->title,
                'candidate_area_id' => $candidateAreaId,
                'candidate_area_name' => $candidateAreaId ? array_search($candidateAreaId, $areas) : null,
                'status' => $status,
                'evidence' => $matchDetails,
                'all_matches' => count($matches) > 1 ? array_map(fn ($id) => array_search($id, $areas), array_keys($matches)) : [],
            ];

            if ($this->option('apply-candidates') && $candidateAreaId && $status !== 'conflict') {
                $project->area_id = $candidateAreaId;
                $project->location_evidence_type = $matchDetails['type'];
                $project->location_evidence_reference = $matchDetails['ref'];
                $project->location_confidence = $matchDetails['confidence'];
                $project->location_status = LocationStatus::PendingReview;
                // Save without triggering updated_at if we want, but normal save is fine.
                // The prompt says "يضع status = pending_review فقط, لا يضع verified".
                $project->save();
            }
        }

        if ($this->option('json')) {
            $this->line(json_encode([
                'stats' => $stats,
                'candidates' => $results,
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->info('Project Location Discovery Complete.');
        $this->table(
            ['Total', 'Strong', 'Medium', 'Conflict', 'No Evidence'],
            [[$stats['total'], $stats['strong'], $stats['medium'], $stats['conflict'], $stats['no_evidence']]]
        );

        return self::SUCCESS;
    }
}
