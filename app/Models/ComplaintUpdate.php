<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Complaint Update Model
 * Represents chronological updates, comments, and status transitions on a complaint.
 */
class ComplaintUpdate extends BaseModel
{
    protected string $table = 'complaint_updates';

    public const TYPES = [
        'comment'       => ['label' => 'Comment',       'icon' => 'bi-chat-left-text', 'badge' => 'bg-secondary'],
        'status_change' => ['label' => 'Status Change', 'icon' => 'bi-arrow-repeat',    'badge' => 'bg-info text-dark'],
        'assignment'    => ['label' => 'Assignment',    'icon' => 'bi-person-plus',    'badge' => 'bg-primary'],
        'reassignment'  => ['label' => 'Reassignment',  'icon' => 'bi-person-gear',    'badge' => 'bg-warning text-dark'],
        'hold'          => ['label' => 'On Hold',       'icon' => 'bi-pause-circle',   'badge' => 'bg-warning text-dark'],
        'reopen'        => ['label' => 'Reopened',      'icon' => 'bi-arrow-clockwise','badge' => 'bg-danger'],
        'resolution'    => ['label' => 'Resolution',    'icon' => 'bi-check2-circle',  'badge' => 'bg-success'],
        'closure'       => ['label' => 'Closed',        'icon' => 'bi-archive',        'badge' => 'bg-dark'],
    ];
}
