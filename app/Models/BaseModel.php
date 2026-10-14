<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Base Model providing database abstraction, soft-delete safety,
 * pagination, and sanitized query execution.
 */
abstract class BaseModel
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected bool $useSoftDeletes = false;

    public function getTable(): string
    {
        return $this->table;
    }

    public function find(int|string $id): ?array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id";
        if ($this->useSoftDeletes) {
            $sql .= " AND `deleted_at` IS NULL";
        }
        $sql .= " LIMIT 1";

        return Database::selectOne($sql, ['id' => $id]);
    }

    public function all(string $orderBy = 'id DESC'): array
    {
        $sql = "SELECT * FROM `{$this->table}`";
        if ($this->useSoftDeletes) {
            $sql .= " WHERE `deleted_at` IS NULL";
        }
        $sql .= " ORDER BY {$orderBy}";

        return Database::select($sql);
    }

    public function count(string $where = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) as total FROM `{$this->table}`";
        $conditions = [];

        if ($this->useSoftDeletes) {
            $conditions[] = "`deleted_at` IS NULL";
        }
        if ($where !== '') {
            $conditions[] = $where;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $row = Database::selectOne($sql, $params);
        return (int)($row['total'] ?? 0);
    }

    public function paginate(int $page = 1, int $perPage = 15, string $where = '', array $params = [], string $orderBy = 'id DESC'): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $totalRecords = $this->count($where, $params);
        $totalPages = (int)ceil($totalRecords / $perPage);

        $sql = "SELECT * FROM `{$this->table}`";
        $conditions = [];

        if ($this->useSoftDeletes) {
            $conditions[] = "`deleted_at` IS NULL";
        }
        if ($where !== '') {
            $conditions[] = $where;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$offset}";

        $records = Database::select($sql, $params);

        return [
            'data' => $records,
            'current_page' => $page,
            'per_page' => $perPage,
            'total_records' => $totalRecords,
            'total_pages' => $totalPages,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages,
        ];
    }

    public function insert(array $data): string
    {
        $columns = array_keys($data);
        $escapedColumns = array_map(fn($col) => "`{$col}`", $columns);
        $placeholders = array_map(fn($col) => ":{$col}", $columns);

        $sql = sprintf(
            "INSERT INTO `%s` (%s) VALUES (%s)",
            $this->table,
            implode(', ', $escapedColumns),
            implode(', ', $placeholders)
        );

        return Database::insert($sql, $data);
    }

    public function update(int|string $id, array $data): int
    {
        $assignments = [];
        $params = ['_id' => $id];

        foreach ($data as $column => $value) {
            $assignments[] = "`{$column}` = :{$column}";
            $params[$column] = $value;
        }

        $sql = sprintf(
            "UPDATE `%s` SET %s WHERE `%s` = :_id",
            $this->table,
            implode(', ', $assignments),
            $this->primaryKey
        );

        return Database::executeStatement($sql, $params);
    }

    /**
     * Soft delete a record with audit logging of user & reason
     */
    public function softDelete(int|string $id, ?int $deletedBy = null, ?string $reason = null): int
    {
        if (!$this->useSoftDeletes) {
            throw new \LogicException("Table '{$this->table}' does not support soft deletes.");
        }

        $sql = "UPDATE `{$this->table}` SET `deleted_at` = NOW(), `deleted_by` = :deleted_by, `deletion_reason` = :reason WHERE `{$this->primaryKey}` = :id AND `deleted_at` IS NULL";

        return Database::executeStatement($sql, [
            'id' => $id,
            'deleted_by' => $deletedBy,
            'reason' => $reason,
        ]);
    }

    /**
     * Restore a soft-deleted record (Admin only)
     */
    public function restore(int|string $id): int
    {
        if (!$this->useSoftDeletes) {
            throw new \LogicException("Table '{$this->table}' does not support soft deletes.");
        }

        $sql = "UPDATE `{$this->table}` SET `deleted_at` = NULL, `deleted_by` = NULL, `deletion_reason` = NULL WHERE `{$this->primaryKey}` = :id";

        return Database::executeStatement($sql, ['id' => $id]);
    }
}
