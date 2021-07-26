<?php


namespace App;

use Illuminate\Database\Eloquent\Model;

class IncentiveTool extends Model
{
    protected $table = "cm_incentive_tool_settings";
    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'is_display_insentives',
        'is_double_points_on',
        'double_points_start_date',
        'double_points_end_date',
        'is_points_per_prs',
        'points_per_prs',
        'is_promote_to_or_higher',
        'promote_to_or_higher_points',
        'rank_id',
        'is_has_new_representative',
        'new_representative_points',
        'new_representative_start_date',
        'new_representative_end_date',
        'new_representative_min_prs',
        'new_representative_first_n_days',
        'is_double_points_new_representative',
        'double_points_new_representative_start_date',
        'double_points_new_representative_end_date',
        'double_points_new_representative_first_n_days'
    ];
}