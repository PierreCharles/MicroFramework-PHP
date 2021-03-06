<?php

namespace ModelTest\DataMapperTest;

use Model\DataBase\DatabaseConnection;
use Model\DataMapper\StatusMapper;
use Model\Entity\Status;
use TestCase;
use PDO;

class StatusMapperTest extends TestCase
{
    private $connection;

    public function setUp()
    {
        $this->connection = new DatabaseConnection('sqlite::memory:');
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection->exec(<<<SQL
CREATE TABLE IF NOT EXISTS STATUSES (
  status_id INT PRIMARY KEY,
  status_message VARCHAR(140) NOT NULL,
  status_user_name VARCHAR(100),
  status_date DATETIME NOT NULL
);
SQL
        );
    }

    public function testPersist()
    {
        $mapper = new StatusMapper($this->connection);
        $rows = $this->connection->query('SELECT COUNT(*) FROM STATUSES')->fetch(PDO::FETCH_NUM);
        $this->assertEquals(0, $rows[0]);
        $status = new Status(null, 'picharles', 'message', date('Y-m-d H:i:s'));
        $mapper->persist($status);
        $rows = $this->connection->query('SELECT COUNT(*) FROM STATUSES')->fetch(PDO::FETCH_NUM);
        $this->assertEquals(1, $rows[0]);
    }

    public function testRemove()
    {
        $mapper = new statusMapper($this->connection);
        $status = new Status('1', 'picharles', 'message', date('Y-m-d H:i:s'));
        $this->assertEquals(1, $mapper->persist($status));
        $this->assertEquals(1,$mapper->remove($status->getId()));
    }
}
