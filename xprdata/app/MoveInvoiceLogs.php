<?php
/**
 * Created by PhpStorm.
 * User: Vienzent
 * Date: 7/17/2019
 * Time: 10:23 AM
 */

namespace App;
use Illuminate\Database\Eloquent\Model;

class MoveInvoiceLogs extends Model
{
    protected $table = "cm_move_invoice_logs";

    protected $fillable = [
        'transaction_id',
        'new_user_id',
        'new_sponsor_id',
        'old_user_id',
        'old_sponsor_id',
        'changed_by_id',
        'old_transaction_date',
        'new_transaction_date',
    ];

}