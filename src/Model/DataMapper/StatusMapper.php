<?php

namespace Model\DataMapper;

use Model\DataBase\DatabaseConnection;

class StatusMapper implements DataMapperInterface
{
    private $connection;

    public function __construct(DatabaseConnection $connection)
    {
        $this->connection = $connection;
    }

    public function persist($status)
    {
        $request = 'INSERT INTO STATUSES(status_message,status_user_name,status_date) VALUES (:message, :user, :date)';

        return $this->connection->prepareAndExecuteQuery($request, [
            'message' => $status->getMessage(),
            'user' => $status->getUser(),
            'date' => $status->getDate(),
        ]);
    }

    public function remove($id)
    {
        $request = 'DELETE FROM STATUSES WHERE status_id=:id';

        return $this->connection->prepareAndExecuteQuery($request, ['id' => $id]);
    }
}
