<?php

namespace App\Models;

use PDO;

class InternRepository
{
    /**
     * Белый список колонок, которые разрешено писать через API.
     * id, record_created_at, updated_at, updated_by_user_id управляются
     * сервером и сюда не входят.
     */
    private const WRITABLE_FIELDS = [
        'full_name', 'email', 'phone',
        'university', 'faculty', 'specialty', 'study_year',
        'direction_id', 'section_id',
        'internship_status', 'start_date', 'end_date',
        'mentor', 'internship_type', 'recommendation_source', 'hiring_potential',
        'is_active',
    ];

    public function __construct(private PDO $db)
    {
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM interns WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * @param array $filters опционально: direction_id, section_id, university,
     *                       specialty, date_from, date_to
     */
    public function list(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['direction_id'])) {
            $where[] = 'interns.direction_id = :direction_id';
            $params['direction_id'] = $filters['direction_id'];
        }
        if (!empty($filters['section_id'])) {
            $where[] = 'interns.section_id = :section_id';
            $params['section_id'] = $filters['section_id'];
        }
        if (!empty($filters['university'])) {
            $where[] = 'interns.university ILIKE :university';
            $params['university'] = '%' . $filters['university'] . '%';
        }
        if (!empty($filters['specialty'])) {
            $where[] = 'interns.specialty ILIKE :specialty';
            $params['specialty'] = '%' . $filters['specialty'] . '%';
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'interns.start_date >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'interns.end_date <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }
        // isset(), не empty() — иначе нельзя было бы явно запросить is_active=false
        if (isset($filters['is_active'])) {
            $where[] = 'interns.is_active = :is_active';
            $params['is_active'] = filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        $sql = 'SELECT interns.*, directions.name AS direction_name, sections.name AS section_name
                FROM interns
                JOIN directions ON directions.id = interns.direction_id
                JOIN sections ON sections.id = interns.section_id';

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY interns.id';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Вставляет только те поля, что реально пришли в $data —
     * остальные колонки заполнятся значениями DEFAULT из схемы БД.
     */
    public function create(array $data, int $userId): array
    {
        $fields = array_intersect_key($data, array_flip(self::WRITABLE_FIELDS));
        $fields['updated_by_user_id'] = $userId;

        // PDO по умолчанию биндит bool как строку, а (string) false === "" —
        // Postgres такое не примет для колонки типа boolean. Приводим к 0/1.
        foreach ($fields as $key => $value) {
            if (is_bool($value)) {
                $fields[$key] = (int) $value;
            }
        }

        $columns = array_keys($fields);
        $placeholders = array_map(fn($c) => ":{$c}", $columns);

        $sql = sprintf(
            'INSERT INTO interns (%s) VALUES (%s) RETURNING *',
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = $this->db->prepare($sql);
        $stmt->execute($fields);

        return $stmt->fetch();
    }

    /**
     * Обновляет только переданные поля (частичный update).
     * updated_at и updated_by_user_id проставляются всегда.
     */
    public function update(int $id, array $data, int $userId): array
    {
        $fields = array_intersect_key($data, array_flip(self::WRITABLE_FIELDS));
        $fields['updated_by_user_id'] = $userId;
        $fields['updated_at'] = date('c');

        foreach ($fields as $key => $value) {
            if (is_bool($value)) {
                $fields[$key] = (int) $value;
            }
        }

        $setParts = array_map(fn($c) => "{$c} = :{$c}", array_keys($fields));

        $sql = sprintf(
            'UPDATE interns SET %s WHERE id = :id RETURNING *',
            implode(', ', $setParts)
        );

        $fields['id'] = $id;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($fields);

        return $stmt->fetch();
    }
}